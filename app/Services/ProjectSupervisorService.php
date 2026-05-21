<?php

namespace App\Services;

use App\Models\OpeningSupervisor;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectSupervisorService
{
    public function assign(array $projectIds, array $supervisorIds): void
    {
        $types = [OpeningSupervisor::TYPE_PRINCIPAL, OpeningSupervisor::TYPE_ALTERNATE];

        $supervisors = collect($supervisorIds)
            ->values()
            ->map(fn ($id, $index) => ['id' => $id, 'type' => $types[$index] ?? null])
            ->toArray();

        $projects = Project::with('opening')
            ->whereIn('id', $projectIds)
            ->get();

        DB::transaction(function () use ($projects, $supervisors) {
            foreach ($projects as $project) {
                if (! $project->opening) {
                    throw new \Exception("Projeto {$project->id} não possui abertura.");
                }

                $project->opening->assignSupervisors($supervisors);
            }
        });
    }
}
