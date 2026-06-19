<?php

namespace App\Services\Strategies;

use App\Contracts\InstallmentCycleStrategy;
use App\Enums\ProjectStageSlug;

class StandardInstallmentCycleStrategy implements InstallmentCycleStrategy
{
    public function stagesToReset(): array
    {
        return [
            ProjectStageSlug::ORCAMENTO,
            ProjectStageSlug::PAGAMENTO,
            ProjectStageSlug::MONITORAMENTO,
        ];
    }

    public function activationStage(): ProjectStageSlug
    {
        return ProjectStageSlug::ORCAMENTO;
    }
}
