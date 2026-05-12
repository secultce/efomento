<?php

namespace App\Services;

use App\Models\Project;

class ProjectService
{
    public function updateOrCreateFromRegistration(
        int $registrationId,
        array $registration,
        array $details,
        int $agentId,
        int $noticeId,
        ?int $categoryId
    ): Project {
        return Project::updateOrCreate(
            ['registration_id' => $registrationId],
            [
                'number' => $details['registration']['number'] ?? null,
                'category_id' => $categoryId,
                'agent_id' => $agentId,
                'notice_id' => $noticeId,
                'create_timestamp' => data_get($registration, 'createTimestamp.date'),
                'sent_timestamp' => data_get($registration, 'sentTimestamp.date'),
                'consolidated_result' => $details['registration']['consolidatedResult'] ?? null,
                'data_registration' => $details,
            ]
        );
    }
}
