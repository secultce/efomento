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

        $stage->approve();
    }

    public function reject(ProjectStage $stage, string $reason): void
    {
        if ($stage->status !== ProjectStageStatus::EM_ANDAMENTO) {
            throw new InvalidArgumentException(
                'A etapa precisa estar em andamento para ser rejeitada.'
            );
        }

        $stage->reject($reason);
    }
}
