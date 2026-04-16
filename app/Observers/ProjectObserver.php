<?php

namespace App\Observers;

use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Enums\ResponsibleSector;
use App\Models\Project;

class ProjectObserver
{
    /**
     * Handle the Project "created" event.
     * Cria automaticamente as 6 etapas do fluxo para o projeto.
     */
    public function created(Project $project): void
    {
        $stages = [
            [
                'slug' => ProjectStageSlug::ABERTURA->value,
                'order' => 1,
                'responsible_sector' => ResponsibleSector::C_FINALISTICA->value,
                'status' => ProjectStageStatus::EM_ANDAMENTO->value,
                'started_at' => now(),
            ],
            [
                'slug' => ProjectStageSlug::ANALISE_JURIDICA->value,
                'order' => 2,
                'responsible_sector' => ResponsibleSector::ASJUR->value,
                'status' => ProjectStageStatus::PENDENTE->value,
            ],
            [
                'slug' => ProjectStageSlug::FORMALIZACAO->value,
                'order' => 3,
                'responsible_sector' => ResponsibleSector::C_FINALISTICA->value,
                'status' => ProjectStageStatus::PENDENTE->value,
            ],
            [
                'slug' => ProjectStageSlug::ORCAMENTO->value,
                'order' => 4,
                'responsible_sector' => ResponsibleSector::CODIP->value,
                'status' => ProjectStageStatus::PENDENTE->value,
            ],
            [
                'slug' => ProjectStageSlug::PAGAMENTO->value,
                'order' => 5,
                'responsible_sector' => ResponsibleSector::CEGEF->value,
                'status' => ProjectStageStatus::PENDENTE->value,
            ],
            [
                'slug' => ProjectStageSlug::MONITORAMENTO->value,
                'order' => 6,
                'responsible_sector' => ResponsibleSector::MONITORAMENTO->value,
                'status' => ProjectStageStatus::PENDENTE->value,
            ],
        ];

        foreach ($stages as $stage) {
            $project->stages()->create($stage);
        }
    }

    /**
     * Handle the Project "updated" event.
     */
    public function updated(Project $project): void {}

    /**
     * Handle the Project "deleted" event.
     */
    public function deleted(Project $project): void {}

    /**
     * Handle the Project "restored" event.
     */
    public function restored(Project $project): void {}

    /**
     * Handle the Project "force deleted" event.
     */
    public function forceDeleted(Project $project): void {}
}
