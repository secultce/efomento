<?php

namespace App\Http\Controllers;

use App\Enums\ProfileSnapshotSource;
use App\Http\Requests\Opening\OpeningUpdateRequest;
use App\Models\Agent;
use App\Models\Opening;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        try {
            return DB::transaction(function () use ($request, $project, $opening) {

                $data = $request->validated();
                
                $openingData = $data['opening'] ?? [];
                $formalizationData = $data['formalization'] ?? [];
                $agentData = $data['agent'] ?? [];
                $supervisors = $openingData['supervisors'] ?? [];

                $opening->update($openingData);

                if ($project->formalization) {
                    $project->formalization->update($formalizationData);
                }

                if ($project->agent) {
                    $project->agent->profileSnapshots()->updateOrCreate(
                        [
                            'source' => ProfileSnapshotSource::PROJECT_UPDATE,
                        ],
                        [
                            'name' => $agentData['name'] ?? $project->agent->name,
                            'cpf' => $agentData['cpf'] ?? $project->agent->cpf,
                            'director_position' => $agentData['director_position'] ?? $project->agent->director_position,
                            'director_email' => $agentData['director_email'] ?? $project->agent->director_email,
                            ...$agentData,
                            'recorded_at' => now(),
                        ]
                    );
                }

                if (is_array($supervisors)) {

                    $supervisorIds = collect($supervisors)
                        ->pluck('id')
                        ->filter()
                        ->values()
                        ->toArray();

                    $project->opening->assignSupervisors($supervisorIds);

                    foreach ($supervisors as $supervisor) {
                        if (!isset($supervisor['id'])) {
                            continue;
                        }

                        $user = User::find($supervisor['id']);

                        if ($user && isset($supervisor['registration_number'])) {
                            $user->update([
                                'registration_number' => $supervisor['registration_number'],
                            ]);
                        }
                    }
                }

                return back()->with('success', 'Abertura atualizada com sucesso.');
            });
        }catch (\Throwable $e) {
            return back()->with('error', 'Erro ao atualizar abertura: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Opening $opening)
    {
        //
    }
}
