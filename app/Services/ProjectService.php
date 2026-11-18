<?php

namespace App\Services;

use App\Jobs\SyncProjectPhasesProjectJob;
use App\Models\Project;

class ProjectService
{
    public function createFromRegistrationIfMissing(
        int $registrationId,
        array $registration,
        array $details,
        int $agentId,
        int $noticeId,
        ?int $categoryId
    ): Project {
        $project = Project::query()->firstOrCreate(
            [
                'registration_id' => $registrationId,
            ],
            [
                'number' => data_get($details, 'registration.number'),
                'category_id' => $categoryId,
                'agent_id' => $agentId,
                'notice_id' => $noticeId,
                'create_timestamp' => data_get($registration, 'createTimestamp.date'),
                'sent_timestamp' => data_get($registration, 'sentTimestamp.date'),
                'consolidated_result' => data_get($details, 'registration.consolidatedResult'),
            ]
        );

        // Sem isso o projeto novo só ganharia fases no disparo diário das 04:00.
        if ($project->wasRecentlyCreated) {
            SyncProjectPhasesProjectJob::dispatch($project->id)->afterCommit();
        }

        return $project;
    }
}
