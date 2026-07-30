<?php

namespace App\Exceptions\Domain;

use App\Enums\ProjectStageStatus;
use App\Exceptions\AppException;
use App\Models\ProjectStage;

final class StageTransitionException extends AppException
{
    public static function invalidStatus(ProjectStage $stage, ProjectStageStatus $required): self
    {
        return new self(
            "A etapa \"{$stage->slug->label()}\" precisa estar \"{$required->label()}\" para ser tramitada.",
            httpStatus: 422,
            context: [
                'stage_id' => $stage->id,
                'slug' => $stage->slug->value,
                'status' => $stage->status->value,
            ],
        );
    }

    public function shouldReport(): bool
    {
        return false;
    }
}
