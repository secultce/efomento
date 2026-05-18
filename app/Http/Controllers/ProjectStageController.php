<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReturnStageRequest;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\User;
use App\Services\ProjectStageService;
use App\Support\Notify;
use Illuminate\Auth\Access\AuthorizationException;

class ProjectStageController extends Controller
{
    public function __construct(
        private ProjectStageService $stageService,
        private Notify $notify
    ) {}

    public function return(ReturnStageRequest $request, Project $project, ProjectStage $stage)
    {
        try {
            $previousStage = $this->stageService->returnStage(
                $stage,
                $request->validated('reason'),
                $request->user()
            );

            $previousStageUsers = User::role($previousStage->responsible_sector)->get();
            $this->notify->users($previousStageUsers)->info(
                'O processo "'.$project->title_project.'" foi devolvido para sua etapa.',
                'Processo devolvido'
            );

            return back()->with('success', 'Processo devolvido com sucesso.');
        } catch (AuthorizationException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
