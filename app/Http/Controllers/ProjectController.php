<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Notice;
use App\Models\User;
use App\Models\Project;
use App\Models\OpeningSupervisor;
use App\Enums\ProjectPhase;
use App\Enums\InstrumentType;
use App\Services\ProjectSupervisorService;
use App\Services\DocumentService;

class ProjectController extends Controller
{
    public function index(Request $request, Notice $notice)
    {
        $query = $notice->projects()
            ->with(['agent', 'category', 'opening', 'opening.supervisors'])
            ->withCount('openings')
            ->filterPhase($request->phase)
            ->search($request->search);

        return Inertia::render('Projects', [
            'notice' => $notice,
            'projects' => $query->get(),
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
    
    public function createCI(Request $request, DocumentService $service)
    {
        $data = $request->validate([
            'selected_projects' => 'required|array',
            'selected_projects.*' => 'exists:projects,id',
            'content' => 'required|string',
        ]);

        try {
            $service->createCI(
                $data['selected_projects'],
                $data['content']
            );
            return back()->with('success', 'Comunicações internas criadas com sucesso!');
        } catch (\Throwable $e) {
            dd($e);
            report($e);
            return back()->withErrors([
                'message' => 'Erro ao criar comunicações internas. Tente novamente.',
            ]);
        }
    }
}