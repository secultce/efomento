<?php

namespace App\Observers;

use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Models\Project;

class ProjectObserver
{
    public function created(Project $project): void
    {
        $stages = [
            [
                'slug' => ProjectStageSlug::ABERTURA,
                'order' => 1,
                'responsible_sector' => ['fomentation', 'coord_fomentation'],
                'status' => ProjectStageStatus::EM_ANDAMENTO,
                'started_at' => now(),
            ],
            [
                'slug' => ProjectStageSlug::ANALISE_JURIDICA,
                'order' => 2,
                'responsible_sector' => ['financial', 'coord_financial', 'legal_analysis', 'coord_legal'],
                'status' => ProjectStageStatus::PENDENTE,
            ],
            [
                'slug' => ProjectStageSlug::FORMALIZACAO,
                'order' => 3,
                'responsible_sector' => ['legal_analysis', 'coord_legal'],
                'status' => ProjectStageStatus::PENDENTE,
            ],
            [
                'slug' => ProjectStageSlug::ORCAMENTO,
                'order' => 4,
                'responsible_sector' => ['budgetary', 'coord_budgetary'],
                'status' => ProjectStageStatus::PENDENTE,
            ],
            [
                'slug' => ProjectStageSlug::PAGAMENTO,
                'order' => 5,
                'responsible_sector' => ['financial', 'coord_financial'],
                'status' => ProjectStageStatus::PENDENTE,
            ],
            [
                'slug' => ProjectStageSlug::MONITORAMENTO,
                'order' => 6,
                'responsible_sector' => ['monitoring', 'coord_monitoring'],
                'status' => ProjectStageStatus::PENDENTE,
            ],
        ];

        foreach ($stages as $stage) {
            $project->stages()->create($stage);
        }
    }
}
