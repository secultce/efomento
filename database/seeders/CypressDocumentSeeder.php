<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Project;
use Illuminate\Database\Seeder;

class CypressDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $projects = json_decode(
            file_get_contents(base_path('cypress/fixtures/projects.json')),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        $registrationIds = collect($projects)
            ->pluck('registrationId')
            ->filter()
            ->all();

        if (empty($registrationIds)) {
            return;
        }

        $projectIds = Project::query()
            ->whereIn('registration_id', $registrationIds)
            ->pluck('id');

        if ($projectIds->isEmpty()) {
            return;
        }

        Document::withTrashed()
            ->whereIn('project_id', $projectIds)
            ->forceDelete();
    }
}
