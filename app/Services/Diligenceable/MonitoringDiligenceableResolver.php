<?php

namespace App\Services\Diligenceable;

use App\Contracts\DiligenceableResolver;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

class MonitoringDiligenceableResolver implements DiligenceableResolver
{
    public function resolve(Project $project): ?Model
    {
        return $project->monitoring ?? $project->monitoring()->create([
            'created_by' => auth()->id(),
        ]);
    }
}
