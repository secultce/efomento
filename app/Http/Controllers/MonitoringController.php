<?php

namespace App\Http\Controllers;

use App\Http\Requests\Monitoring\MonitoringStoreRequest;
use App\Http\Requests\Monitoring\MonitoringUpdateRequest;
use App\Models\Monitoring;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class MonitoringController extends Controller
{
    public function store(MonitoringStoreRequest $request, Project $project): RedirectResponse
    {
        $project->monitoring()->updateOrCreate(
            ['project_id' => $project->id],
            [
                ...$request->validated(),
                'created_by' => auth()->id(),
            ]
        );

        return back();
    }

    public function update(
        MonitoringUpdateRequest $request,
        Project $project,
        Monitoring $monitoring
    ): RedirectResponse {
        $monitoring->update($request->validated());

        return back();
    }
}
