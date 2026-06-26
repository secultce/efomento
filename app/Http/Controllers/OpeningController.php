<?php

namespace App\Http\Controllers;

use App\Http\Requests\Opening\OpeningUpdateRequest;
use App\Models\Opening;
use App\Models\Project;
use App\Services\OpeningUpdateService;
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
        Opening $opening,
        OpeningUpdateService $service
    ) {
        if ($opening->project_id !== $project->id) {
            abort(404);
        }

        try {

            $service->handle(
                $project,
                $opening,
                $request->validated()
            );

            return back()->with(
                'success',
                'Abertura atualizada com sucesso.'
            );

        } catch (\Throwable $e) {

            report($e);

            return back()->withErrors([
                'message' => 'Erro ao atualizar abertura: '.$e->getMessage(),
            ]);
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
