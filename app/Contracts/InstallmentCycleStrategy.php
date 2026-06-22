<?php

namespace App\Contracts;

use App\Enums\ProjectStageSlug;

interface InstallmentCycleStrategy
{
    /** @return array<ProjectStageSlug> etapas que serão resetadas para BLOQUEADO */
    public function stagesToReset(): array;

    /** etapa que inicia o novo ciclo (recebe status EM_ANDAMENTO) */
    public function activationStage(): ProjectStageSlug;
}
