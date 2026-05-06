<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\AgentStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Notice;
use App\Models\User;
use App\Enums\ProjectPhase;
use App\Enums\InstrumentType;
use App\Enums\OpeningStatus;
use App\Enums\ReportStatus;
use App\Services\ProjectSupervisorService;
use App\Services\ProjectDocumentService;
use App\Http\Resources\ProjectResource;
use App\Models\Project;

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
            'supervisors_available' => User::role(['monitoring', 'coord_monitoring'])
                ->select('id', 'name')
                ->get(),
            ]);
    }

    public function projectDetail(Notice $notice, Project $project)
    {
        $project->load(['notice', 'agent', 'category', 'opening', 'opening.supervisors', 'documents', 'budget','budget.installments', 'formalization', 'agent.latestSnapshot']);

        $availableSupervisors = User::role(['monitoring', 'coord_monitoring'])
        ->select('id', 'name')
        ->get();

        if ($project->opening && $project->opening->supervisors) {
            $currentSupervisorIds = $project->opening->supervisors
                ->whereNull('deleted_at') 
                ->pluck('id')
                ->filter();

            if ($currentSupervisorIds->isNotEmpty()) {
                $assignedSupervisors = User::whereIn('id', $currentSupervisorIds)
                    ->select('id', 'name')
                    ->get();

                $availableSupervisors = $availableSupervisors
                    ->merge($assignedSupervisors)
                    ->unique('id');
            }
        }
        return Inertia::render('ProjectDetails', [
            'project' => (new ProjectResource($project))->resolve(),
            'supervisorsAvailable' => $availableSupervisors,
            'agentStatus' => AgentStatus::options(),
            'accountType' => AccountType::options(),
            'reportStatus' => ReportStatus::options(),
            'openingStatus' => OpeningStatus::options(),

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