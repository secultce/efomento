<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\AgentStatus;
use App\Enums\InstrumentType;
use App\Enums\OpeningStatus;
use App\Enums\ProjectPhase;
use App\Enums\ReportStatus;
use App\Http\Resources\ProjectResource;
use App\Models\Notice;
use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\User;
use App\Services\ProjectDocumentService;
use App\Services\ProjectStageService;
use App\Services\ProjectSupervisorService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request, Notice $notice)
    {
        $query = $notice->projects()
            ->with(['agent', 'category', 'opening', 'opening.supervisors', 'documents'])
            ->withCount('openings')
            ->filterPhase($request->phase)
            ->search($request->search);

        return Inertia::render('Projects', [
            'notice' => $notice,
            'projects' => ProjectResource::collection(
                $query->get()
            )->resolve(),
            'filters' => $request->only(['phase', 'search']),
            'instrumentTypes' => InstrumentType::values(),
            'phases' => collect(ProjectPhase::cases())->map(fn ($phase) => [
                'value' => $phase->value,
                'title' => $phase->label(),
                'total' => $phase->count($query),
            ]),
            'supervisorsAvailable' => User::role(['monitoring', 'coord_monitoring'])
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
            'agent.latestSnapshot',
            'stages',
        ]);

        $availableSupervisors = User::role(['monitoring', 'coord_monitoring'])
            ->select('id', 'name', 'registration_number')
            ->get();

        return Inertia::render('ProjectDetails', [
            'project' => (new ProjectResource($project))->resolve(),
            'supervisorsAvailable' => $availableSupervisors,
            'agentStatus' => AgentStatus::options(),
            'accountType' => AccountType::options(),
            'reportStatus' => ReportStatus::options(),
            'openingStatus' => OpeningStatus::options(),

        ]);
    }

    public function tramitProject(
        Request $request,
        ProjectStageService $service
    ) {
        $data = $request->validate([
            'stage_id' => ['required', 'exists:project_stages,id'],
        ]);

        $stage = ProjectStage::with('project')->findOrFail($data['stage_id']);

        try {
            $service->advance($stage, $request->user());

            return back()->with('success', 'Processo tramitado com sucesso!');
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'message' => 'Erro ao tramitar processo.',
            ]);
        }
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
