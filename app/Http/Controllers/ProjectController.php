<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Notice;
use App\Enums\ProjectPhase;
use App\Enums\InstrumentType;

class ProjectController extends Controller
{
    public function index(Request $request, Notice $notice)
    {
        $query = $notice->projects()
            ->with(['agent', 'category', 'opening'])
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
        ]);
    }
    
    public function assignProjectSupervisor(Request $request, Notice $notice)
    {
        // Implement the logic to assign a supervisor to a project
        //it will receive an array of project ids and an array of supervisor ids, and it will assign each supervisor to each project
        $request->validate([
            'project_ids' => 'required|array',
            'supervisor_ids' => 'required|array',
        ]);
        $projectIds = $request->input('project_ids');
        $supervisorIds = $request->input('supervisor_ids');
        foreach ($projectIds as $projectId) {
            foreach ($supervisorIds as $supervisorId) {
              
                ProjectSupervisor::create([
                    'project_id' => $projectId,
                    'supervisor_id' => $supervisorId,
                    'assigned_by' => Auth::id(),
                    'assigned_at' => now(),
                ]);
            }
        }
        return redirect()->back()->with('success', 'Supervisores atribuídos com sucesso!');
    }

}