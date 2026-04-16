<?php

namespace App\Services;

use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Enums\ResponsibleSector;
use App\Models\Project;
use App\Models\ProjectStage;
use InvalidArgumentException;

class ProjectStageService
{
    /**
     * Definição canônica das 6 etapas do fluxo.
     */
    private function stageDefinitions(): array
    {
        return [
            [
                'slug'               => ProjectStageSlug::ABERTURA->value,
                'order'              => 1,
                'responsible_sector' => ResponsibleSector::C_FINALISTICA->value,
                'status'             => ProjectStageStatus::EM_ANDAMENTO->value,
                'started_at'         => now(),
            ],
            [
                'slug'               => ProjectStageSlug::ANALISE_JURIDICA->value,
                'order'              => 2,
                'responsible_sector' => ResponsibleSector::ASJUR->value,
                'status'             => ProjectStageStatus::PENDENTE->value,
            ],
            [
                'slug'               => ProjectStageSlug::FORMALIZACAO->value,
                'order'              => 3,
                'responsible_sector' => ResponsibleSector::C_FINALISTICA->value,
                'status'             => ProjectStageStatus::PENDENTE->value,
            ],
            [
                'slug'               => ProjectStageSlug::ORCAMENTO->value,
                'order'              => 4,
                'responsible_sector' => ResponsibleSector::CODIP->value,
                'status'             => ProjectStageStatus::PENDENTE->value,
            ],
            [
                'slug'               => ProjectStageSlug::PAGAMENTO->value,
                'order'              => 5,
                'responsible_sector' => ResponsibleSector::CEGEF->value,
                'status'             => ProjectStageStatus::PENDENTE->value,
            ],
            [
                'slug'               => ProjectStageSlug::MONITORAMENTO->value,
                'order'              => 6,
                'responsible_sector' => ResponsibleSector::MONITORAMENTO->value,
                'status'             => ProjectStageStatus::PENDENTE->value,
            ],
        ];
    }

    /**
     * Inicializa as etapas de um projeto. Idempotente: não cria duplicatas.
     */
    public function initializeForProject(Project $project): void
    {
        if ($project->stages()->exists()) {
            return;
        }

        foreach ($this->stageDefinitions() as $definition) {
            $project->stages()->create($definition);
        }
    }

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
