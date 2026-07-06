<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStageSlug;
use App\Enums\Role;
use App\Http\Requests\Stages\ReturnStageRequest;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Services\FormalizationService;
use App\Services\NotificationService;
use App\Services\OpeningUpdateService;
use App\Services\ProjectStageService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
        try {
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
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'message' => 'Erro ao tramitar processo: '.$e->getMessage(),
            ]);
        }
    }

    public function requestNextInstallment(Request $request, Project $project)
    {
        try {
            $this->stageService->requestNextInstallment($project);

            return back();
        } catch (\InvalidArgumentException $e) {
            report($e);

            return back()->withErrors(['message' => $e->getMessage()]);
        }
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

            return back()->with('success', 'Processo devolvido com sucesso.');
        } catch (AuthorizationException $e) {
            \Sentry\captureException($e);

            return back()->with('error', $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        }
    }
}
