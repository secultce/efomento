<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Notice;
use App\Models\User;
use App\Models\Project;
use App\Models\OpeningSupervisor;
use App\Enums\ProjectPhase;
use App\Enums\InstrumentType;

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
            'supervisors_available' => User::select('id', 'name')->get(),
        ]);
    }
    
    public function assignProjectSupervisor(Request $request)
    {
        $data = $request->validate([
            'selected_projects' => 'required|array',
            'selected_projects.*' => 'exists:projects,id',

            'selected_supervisors' => 'required|array',
            'selected_supervisors.*' => 'exists:users,id',
        ]);

        $projects = Project::with('opening')->whereIn('id', $data['selected_projects'])->get();

        foreach ($projects as $project) {
            if(!$project->opening) {
                continue; 
            }
            OpeningSupervisor::where('opening_id', $project->opening->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'removed_at' => now(),
                ]);

            foreach ($data['selected_supervisors'] as $supervisorId) {
                OpeningSupervisor::create([
                    'opening_id' => $project->opening->id,
                    'user_id' => $supervisorId,
                    'assigned_by' => Auth::id(),
                    'assigned_at' => now(),
                    'is_active' => true,
                ]);
            }
        }

        return back()->with('success', 'Supervisores atribuídos com sucesso!');
    }

}