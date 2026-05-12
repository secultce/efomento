<?php

namespace App\Models;

use App\Enums\ProjectPhase;
use App\Enums\ProjectStageStatus;
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
    use AuditableTrait, HasFactory, HasFiles, SoftDeletes;

    protected $fillable = [
        'registration_id',
        'number',
        'category_id',
        'agent_id',
        'notice_id',
        'create_timestamp',
        'sent_timestamp',
        'consolidated_result',
        'data_registration',
        'created_by',
        'title_project',
    ];

    protected $casts = [
        'create_timestamp' => 'datetime',
        'sent_timestamp' => 'datetime',
        'data_registration' => 'array',
    ];

    protected $appends = ['phase', 'opening_nup'];

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

    public function openings(): HasMany
    {
        return $this->hasMany(Opening::class);
    }

    public function opening(): HasOne
    {
        return $this->hasOne(Opening::class)
            ->latestOfMany('created_at');
    }

    public function getPhaseAttribute()
    {
        if (array_key_exists('openings_count', $this->attributes)) {
            return $this->openings_count > 0
                ? 'Abertura'
                : 'Não Iniciado';
        }

        return $this->openings()->exists()
            ? 'Abertura'
            : 'Não Iniciado';
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

        $phaseEnum = ProjectPhase::fromRequest($phase);

        if ($phaseEnum) {
            $phaseEnum->applyFilter($query);
        }

        return $query;
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        $search = strtolower($search);

        return $query->where(function ($q) use ($search) {
            $q->orWhereHas('agent', function ($q2) use ($search) {
                $q2->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
            });

            $q->orWhereHas('openings', function ($q3) use ($search) {
                $q3->whereRaw('LOWER(opening_nup) LIKE ?', ["%{$search}%"]);
            });
        });
    }

    public function formalization(): HasOne
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

    public function budget(): HasOne
    {
        return $this->hasOne(Budget::class);
    }

    public function monitoring(): HasOne
    {
        return $this->hasOne(Monitoring::class);
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
