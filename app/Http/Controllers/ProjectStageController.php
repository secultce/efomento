<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStageSlug;
use App\Enums\Role;
use App\Exceptions\AppException;
use App\Http\Requests\Stages\ReturnStageRequest;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Services\FormalizationService;
use App\Services\NotificationService;
use App\Services\OpeningUpdateService;
use App\Services\ProjectStageService;
use Illuminate\Http\Request;

class ProjectStageController extends Controller
{
    public function __construct(
        private ProjectStageService $stageService,
        private NotificationService $notificationService,
        private FormalizationService $formalizationService,
        private OpeningUpdateService $openingUpdateService,
    ) {}

    public function advance(
        Request $request,
        Project $project,
        ProjectStage $stage
    ) {
        $stage->load('project');

        if ($stage->slug === ProjectStageSlug::ABERTURA) {
            $this->openingUpdateService->ensureCanAdvance($project);
        }

        if ($stage->slug === ProjectStageSlug::FORMALIZACAO) {
            $this->formalizationService->ensureCanAdvance($project);
        }

        $nextStage = $this->stageService->advance($stage, $request->user());

        $this->notificationService->notifyStageAdvanced($stage, $nextStage, $request->user());

        return back()->with('success', 'Processo tramitado com sucesso!');
    }

    public function requestNextInstallment(Request $request, Project $project)
    {
        $this->stageService->requestNextInstallment($project, $request->user());

        return back();
    }

    public function return(
        ReturnStageRequest $request,
        Project $project,
        ProjectStage $stage,
    ) {
        try {
            $this->stageService->returnStage(
                $stage,
                $request->validated('reason'),
                $request->user()
            );

            $this->notificationService->notifyProcessReturned(
                $project,
                $request->validated('reason'),
                Role::fomentoRoles(),
                $request->user()
            );

            return back()->with('success', 'O processo foi devolvido aos responsáveis!');
        } catch (AppException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
