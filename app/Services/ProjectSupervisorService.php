<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectSupervisorService
{
    public function assign(array $projectIds, array $supervisorIds): void
    {
        $projects = Project::with('opening')
            ->whereIn('id', $projectIds)
            ->get();

        DB::transaction(function () use ($projects, $supervisorIds) {
            foreach ($projects as $project) {
                if (! $project->opening) {
                    throw new \Exception("Projeto {$project->id} não possui abertura.");
                }

                $project->opening->assignSupervisors($supervisorIds);
            }
        });
    }
}
