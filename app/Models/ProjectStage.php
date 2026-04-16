<?php

namespace App\Models;

use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Enums\ResponsibleSector;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class ProjectStage extends Model implements Auditable
{
    use AuditableTrait, HasFactory;

    protected $fillable = [
        'project_id',
        'slug',
        'order',
        'responsible_sector',
        'status',
        'responsible_user_id',
        'started_at',
        'concluded_at',
        'deadline_at',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'slug' => ProjectStageSlug::class,
        'responsible_sector' => ResponsibleSector::class,
        'status' => ProjectStageStatus::class,
        'started_at' => 'datetime',
        'concluded_at' => 'datetime',
        'deadline_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function approve(): void
    {
        $this->update([
            'status' => ProjectStageStatus::APROVADO,
            'concluded_at' => now(),
        ]);

        $next = $this->getNextStage();
        if ($next) {
            $next->update([
                'status' => ProjectStageStatus::EM_ANDAMENTO,
                'started_at' => now(),
            ]);
        }
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status' => ProjectStageStatus::REJEITADO,
            'rejection_reason' => $reason,
            'concluded_at' => now(),
        ]);

        $this->project->stages()
            ->where('order', '>', $this->order)
            ->update(['status' => ProjectStageStatus::BLOQUEADO->value]);
    }

    public function canAdvance(): bool
    {
        if ($this->order === 1) {
            return true;
        }

        return $this->project->stages()
            ->where('order', '<', $this->order)
            ->where('status', '!=', ProjectStageStatus::APROVADO->value)
            ->doesntExist();
    }

    public function getNextStage(): ?self
    {
        return $this->project->stages()
            ->where('order', $this->order + 1)
            ->first();
    }

    public function getPreviousStage(): ?self
    {
        if ($this->order <= 1) {
            return null;
        }

        return $this->project->stages()
            ->where('order', $this->order - 1)
            ->first();
    }

    public function isActive(): bool
    {
        return $this->status === ProjectStageStatus::EM_ANDAMENTO;
    }
}
