<?php

namespace App\Services;

use App\Enums\ProjectStageStatus;
use App\Models\ProjectStage;
use InvalidArgumentException;

class ProjectStageService
{
    public function advance(ProjectStage $stage): void
    {
        if ($stage->status !== ProjectStageStatus::EM_ANDAMENTO) {
            throw new InvalidArgumentException(
                'A etapa precisa estar em andamento para ser aprovada.'
            );
        }

        if (! $stage->canAdvance()) {
            throw new InvalidArgumentException(
                'Não é possível avançar: existem etapas anteriores não aprovadas.'
            );
        }

        $stage->markApproved();

        $next = $stage->getNextStage();
        if ($next) {
            $next->update([
                'status' => ProjectStageStatus::EM_ANDAMENTO,
                'started_at' => now(),
            ]);
        }
    }

    public function reject(ProjectStage $stage, string $reason): void
    {
        if ($stage->status !== ProjectStageStatus::EM_ANDAMENTO) {
            throw new InvalidArgumentException(
                'A etapa precisa estar em andamento para ser rejeitada.'
            );
        }

        $stage->markRejected($reason);

        $stage->project->stages()
            ->where('order', '>', $stage->order)
            ->update(['status' => ProjectStageStatus::BLOQUEADO->value]);
    }
}
