<?php

namespace App\Models;

use App\Enums\ProfileSnapshotSource;
use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Support\NameAndNupSearch;
use App\Traits\HasCreatedBy;
use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Project extends Model implements Auditable
{
    use AuditableTrait, HasCreatedBy, HasFactory, HasFiles, SoftDeletes;

    protected $fillable = [
        'registration_id',
        'number',
        'category_id',
        'agent_id',
        'notice_id',
        'current_installment_cycle',
        'create_timestamp',
        'sent_timestamp',
        'consolidated_result',
        'created_by',
        'title_project',
    ];

    protected $casts = [
        'create_timestamp' => 'datetime',
        'sent_timestamp' => 'datetime',
        'current_installment_cycle' => 'integer',
        'last_checked_at' => 'datetime',
        'last_changed_at' => 'datetime',
        'next_check_at' => 'datetime',
        'sync_closed_at' => 'datetime',
    ];

    protected $appends = ['phase', 'phase_slug', 'opening_nup'];

    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function registrationPhases(): HasMany
    {
        return $this->hasMany(ProjectRegistrationPhase::class);
    }

    /**
     * Arquivos avaliados na Análise Jurídica: os do próprio projeto (fase 1 baixada pelo fluxo antigo) e os
     * das fases da inscrição (SyncPhaseFilesJob). Fonte única para listar, servir e avaliar. É uma query, não uma relação: não acessar como propriedade.
     */
    public function analysisFilesQuery(): Builder
    {
        return File::query()->where(function (Builder $query) {
            $query->where(fn (Builder $q) => $q->where('object_type', 'project')->where('object_id', $this->id))
                ->orWhere(fn (Builder $q) => $q->where('object_type', 'project_registration_phase')
                    ->whereIn('object_id', ProjectRegistrationPhase::query()->where('project_id', $this->id)->select('id')));
        });
    }

    public function openings(): HasMany
    {
        return $this->hasMany(Opening::class);
    }

    public function opening(): HasOne
    {
        return $this->hasOne(Opening::class)
            ->latestOfMany('created_at');
    }

    /**
     * Bloqueia (SELECT ... FOR UPDATE) as aberturas do projeto e recarrega a atual.
     * Serializa a troca da natureza da despesa com o vínculo de dotação às parcelas:
     * quem chega depois espera e enxerga o estado já gravado. Chamar dentro de
     * DB::transaction — fora de transação o bloqueio é liberado na hora.
     */
    public function lockOpeningForUpdate(): ?Opening
    {
        Opening::query()
            ->where('project_id', $this->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);

        $opening = $this->opening()->first();
        $this->setRelation('opening', $opening);

        return $opening;
    }

    public function getPhaseAttribute(): string
    {
        return $this->currentStage?->slug?->label() ?? 'Não Iniciado';
    }

    public function getPhaseSlugAttribute(): ?string
    {
        return $this->currentStage?->slug?->value;
    }

    public function getOpeningNupAttribute()
    {
        return $this->opening?->opening_nup;
    }

    public function scopeFilterPhase(Builder $query, ?string $phase): Builder
    {
        if (! $phase) {
            return $query;
        }

        $slug = ProjectStageSlug::tryFrom($phase);

        if (! $slug) {
            return $query;
        }

        return $query->whereHas('stages', function ($q) use ($slug) {
            $q->where('slug', $slug)
                ->where('status', ProjectStageStatus::EM_ANDAMENTO);
        });
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = new NameAndNupSearch($search);

        if ($search->isEmpty) {
            return $query;
        }

        $nameWords = $search->words;
        $digits = $search->digits;

        if ($nameWords === [] && $digits === '') {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $query) use ($nameWords, $digits) {
            if ($nameWords !== []) {
                $query->whereHas('agent', function (Builder $agentQuery) use ($nameWords) {
                    foreach ($nameWords as $word) {
                        $agentQuery->whereRaw('LOWER(unaccent(name)) LIKE LOWER(unaccent(?))', ["%{$word}%"]);
                    }
                });
            }

            if ($digits !== '') {
                $query->whereHas('openings', function (Builder $openingQuery) use ($digits) {
                    $openingQuery->whereRaw(
                        "REPLACE(REPLACE(REPLACE(REPLACE(opening_nup, '.', ''), '/', ''), '-', ''), ' ', '') LIKE ?",
                        ["%{$digits}%"],
                    );
                });
            }
        });
    }

    public function formalizations(): HasOne
    {
        return $this->hasOne(Formalization::class);
    }

    public function legalAnalysis(): HasOne
    {
        return $this->hasOne(LegalAnalysis::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function budgets(): HasOne
    {
        return $this->hasOne(Budget::class);
    }

    public function monitoring(): HasOne
    {
        return $this->hasOne(Monitoring::class);
    }

    public function monitorings(): HasMany
    {
        return $this->hasMany(Monitoring::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function profileSnapshots(): MorphMany
    {
        return $this->morphMany(ProfileSnapshot::class, 'object');
    }

    public function latestSnapshot(): MorphOne
    {
        return $this->morphOne(ProfileSnapshot::class, 'object')->latestOfMany('recorded_at');
    }

    public function monitoringSnapshot(): MorphOne
    {
        return $this->morphOne(ProfileSnapshot::class, 'object')
            ->where('source', ProfileSnapshotSource::MONITORING)
            ->latestOfMany('recorded_at');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ProjectStage::class)->orderBy('order');
    }

    public function currentStage(): HasOne
    {
        return $this->hasOne(ProjectStage::class)
            ->where('status', ProjectStageStatus::EM_ANDAMENTO);
    }

    public function getCurrentStageName(): ?string
    {
        return $this->currentStage?->slug?->label();
    }

    public function hasCompletedPayment(): bool
    {
        if ($this->current_installment_cycle > 1) {
            return true;
        }

        $paymentStage = $this->stages->firstWhere('slug', ProjectStageSlug::PAGAMENTO);

        if ($paymentStage?->status === ProjectStageStatus::APROVADO) {
            return true;
        }

        return $this->stages->contains(
            fn (ProjectStage $stage) => in_array($stage->slug, [
                ProjectStageSlug::MONITORAMENTO,
                ProjectStageSlug::PRESTACAO_DE_CONTAS,
            ], true) && $stage->status !== ProjectStageStatus::PENDENTE
        );
    }

    public function getProgressPercentage(): int
    {
        $stages = $this->stages;
        $total = $stages->count();

        if ($total === 0) {
            return 0;
        }

        $approved = $stages->where('status', ProjectStageStatus::APROVADO)->count();

        return (int) round(($approved / $total) * 100);
    }
}
