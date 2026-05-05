<?php

namespace App\Http\Controllers;

use App\Http\Requests\Opening\OpeningUpdateRequest;
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

        $opening->update($data);

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
