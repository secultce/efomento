<?php

namespace App\Observers;

use App\Models\Project;
use App\Services\ProjectStageService;

class ProjectObserver
{
    public function __construct(private ProjectStageService $stageService) {}

    /**
     * Handle the Project "created" event.
     * Cria automaticamente as 6 etapas do fluxo para o projeto.
     */
    public function created(Project $project): void
    {
        $this->stageService->initializeForProject($project);
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
