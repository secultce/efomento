<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\AgentStatus;
use App\Enums\DeliberationType;
use App\Enums\InstrumentType;
use App\Enums\OpeningStatus;
use App\Enums\ProjectStageSlug;
use App\Enums\ProjectStageStatus;
use App\Enums\ReportStatus;
use App\Enums\TermStatus;
use App\Http\Resources\ProjectResource;
use App\Models\Notice;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectDocumentService;
use App\Services\ProjectSupervisorService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request, Notice $notice)
    {
        $projectsQuery = $notice->projects()
            ->with([
                'agent',
                'category',
                'opening',
                'opening.supervisors',
                'documents',
                'currentStage',
            ])
            ->search($request->search);

        $phaseCountQuery = $notice->projects()
            ->search($request->search);

        $projectsQuery->filterPhase($request->phase);

        return Inertia::render('Projects', [
            'notice' => $notice,

            'projects' => ProjectResource::collection(
                $projectsQuery->get()
            )->resolve(),

            'filters' => $request->only([
                'phase',
                'search',
            ]),

            'instrumentTypes' => InstrumentType::values(),

            'phases' => collect(ProjectStageSlug::cases())
                ->map(function ($stage) use ($phaseCountQuery) {
                    return [
                        'value' => $stage->value,

                        'title' => $stage->label(),

                        'total' => (clone $phaseCountQuery)
                            ->whereHas('stages', function ($q) use ($stage) {
                                $q->where('slug', $stage)
                                    ->where(
                                        'status',
                                        ProjectStageStatus::EM_ANDAMENTO
                                    );
                            })
                            ->count(),
                    ];
                }),

            'supervisorsAvailable' => User::role([
                'monitoring',
                'coord_monitoring',
            ])
                ->select('id', 'name')
                ->get(),
        ]);
    }

    public function projectDetail(Notice $notice, Project $project)
    {
        $project->load([
            'notice',
            'agent',
            'category',
            'opening',
            'opening.supervisors' => function ($q) {
                $q->whereNull('removed_at');
            },
            'opening.supervisors.user',
            'documents',
            'budget',
            'budget.installments',
            'formalization',
            'formalization.files',
            'agent.latestSnapshot',
            'stages',
        ]);

        $availableSupervisors = User::role(['monitoring', 'coord_monitoring'])
            ->select('id', 'name', 'registration_number')
            ->get();

        $currentStage = $project->currentStage;

        return Inertia::render('ProjectDetails', [
            'project' => (new ProjectResource($project))->resolve(),
            'supervisorsAvailable' => $availableSupervisors,
            'agentStatus' => AgentStatus::options(),
            'accountType' => AccountType::options(),
            'reportStatus' => ReportStatus::options(),
            'termStatus' => TermStatus::options(),
            'deliberation' => DeliberationType::options(),
            'openingStatus' => OpeningStatus::options(),
            'currentStage' => $currentStage,
            'canReturn' => $currentStage
                ? ($currentStage->order > 1 && auth()->user()->hasAnyRole($currentStage->responsible_sector))
                : false,
        ]);
    }

    public function assignProjectSupervisor(Request $request, ProjectSupervisorService $service)
    {
        $data = $request->validate([
            'selected_projects' => 'required|array',
            'selected_projects.*' => 'exists:projects,id',

            'selected_supervisors' => 'required|array',
            'selected_supervisors.*' => 'exists:users,id',
        ]);

        try {
            $service->assign(
                $data['selected_projects'],
                $data['selected_supervisors']
            );

            return back()->with('success', 'Fiscais atribuídos com sucesso!');
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'message' => 'Erro ao atribuir fiscais. Tente novamente.',
            ]);
        }
    }

    public function createCI(Request $request, ProjectDocumentService $service)
    {
        $data = $request->validate([
            'selected_projects' => 'required|array',
            'selected_projects.*' => 'exists:projects,id',
            'content' => 'required|string',
        ]);

        try {
            $service->createDocumentCI(
                $data['selected_projects'],
                $data['content']
            );

            return back()->with('success', 'Comunicações internas criadas com sucesso!');
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'message' => $e->getMessage() ?: 'Erro ao criar comunicações internas. Tente novamente.',
            ]);
        }
    }
}
