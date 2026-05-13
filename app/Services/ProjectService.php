<?php

namespace App\Services;

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
        return Project::query()->firstOrCreate(
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
                'data_registration' => $details,
            ]
        );
    }
}
