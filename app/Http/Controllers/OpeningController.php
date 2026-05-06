<?php

namespace App\Http\Controllers;

use App\Enums\ProfileSnapshotSource;
use App\Http\Requests\Opening\OpeningUpdateRequest;
use App\Models\Agent;
use App\Models\Opening;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OpeningController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Opening $opening)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        OpeningUpdateRequest $request,
        Project $project,
        Opening $opening
    ) {
        if ($opening->project_id !== $project->id) {
            abort(404);
        }

        $data = $request->validated();

        // 🧠 Split payload safely
        $openingData = $data['opening'] ?? [];
        $formalizationData = $data['formalization'] ?? [];
        $agentData = $data['agent'] ?? [];
        $supervisors = $openingData['supervisors'] ?? [];
        
        // 🔹 Update opening
        $opening->update($openingData);

        // 🔹 Update formalization (if exists)
        if ($project->formalization) {
            $project->formalization->update($formalizationData);
        }

        if ($project->agent) {
            // Let Eloquent handle 'object_id' and 'object_type' automatically
            $project->agent->profileSnapshots()->updateOrCreate(
                [
                    // Search criteria: Find a snapshot with this source for this agent
                    'source' => ProfileSnapshotSource::PROJECT_UPDATE,
                ],
                [
                    // Data to update or create
                    'name' => $agentData['name'] ?? $project->agent->name,
                    'cpf' => $agentData['cpf'] ?? $project->agent->cpf,
                    'director_position' => $agentData['director_position'] ?? $project->agent->director_position,
                    'director_email' => $agentData['director_email'] ?? $project->agent->director_email,
                    ...$agentData,
                    'recorded_at' => now(),
                ]
            );
        }

        // 🔹 Handle supervisors (this is the tricky part)
        //$this->syncSupervisors($opening, $supervisors);

        return back()->with('success', 'Abertura atualizada com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Opening $opening)
    {
        //
    }
}
