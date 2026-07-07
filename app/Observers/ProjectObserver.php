<?php

namespace App\Observers;

use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Enums\Role;
use App\Models\Project;

class ProjectObserver
{
    public function created(Project $project): void
    {
        $stages = [
            [
                'slug' => ProjectStageSlug::ABERTURA,
                'order' => 1,
                'responsible_sector' => Role::fomentoRoles(),
                'status' => ProjectStageStatus::EM_ANDAMENTO,
                'started_at' => now(),
            ],
            [
                'slug' => ProjectStageSlug::ANALISE_JURIDICA,
                'order' => 2,
                'responsible_sector' => Role::legalAnalysisRoles(),
                'status' => ProjectStageStatus::PENDENTE,
            ],
            [
                'slug' => ProjectStageSlug::FORMALIZACAO,
                'order' => 3,
                'responsible_sector' => Role::formalizationRoles(),
                'status' => ProjectStageStatus::PENDENTE,
            ],
            [
                'slug' => ProjectStageSlug::ORCAMENTO,
                'order' => 4,
                'responsible_sector' => Role::budgetRoles(),
                'status' => ProjectStageStatus::PENDENTE,
            ],
            [
                'slug' => ProjectStageSlug::PAGAMENTO,
                'order' => 5,
                'responsible_sector' => Role::paymentRoles(),
                'status' => ProjectStageStatus::PENDENTE,
            ],
            [
                'slug' => ProjectStageSlug::MONITORAMENTO,
                'order' => 6,
                'responsible_sector' => Role::monitoringRoles(),
                'status' => ProjectStageStatus::PENDENTE,
            ],
            [
                'slug' => ProjectStageSlug::PRESTACAO_DE_CONTAS,
                'order' => 7,
                'responsible_sector' => Role::monitoringRoles(),
                'status' => ProjectStageStatus::PENDENTE,
            ],
        ];

        foreach ($stages as $stage) {
            $project->stages()->create($stage);
        }

        $project->openings()->create();
    }
}
