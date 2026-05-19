<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReturnStageRequest;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Services\NotificationService;
use App\Services\ProjectStageService;
use Illuminate\Auth\Access\AuthorizationException;

class ProjectStageController extends Controller
{
    public function __construct(
        private ProjectStageService $stageService,
        private NotificationService $notificationService
    ) {}

    public function return(ReturnStageRequest $request, Project $project, ProjectStage $stage)
    {
        try {
            $this->stageService->returnStage(
                $stage,
                $request->validated('reason'),
                $request->user()
            );

            $this->notificationService->notifyProcessReturned(
                $project,
                $request->validated('reason'),
                ['fomentation', 'coord_fomentation']
            );

            return back()->with('success', 'Processo devolvido com sucesso.');
        } catch (AuthorizationException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
