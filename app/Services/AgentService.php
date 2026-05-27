<?php

namespace App\Services;

use App\Models\Agent;
use App\Support\DocumentNumber;

class AgentService
{
    public function updateOrCreatedByDocument(
        ?string $cpf,
        ?string $name = null,
    ): ?Agent {
        $cpf = DocumentNumber::normalize($cpf);

        if (! $cpf) {
            return null;
        }

        return Agent::updateOrCreate(
            ['cpf' => $cpf],
            [
                'name' => trim((string) $name) ?: 'Nome não informado',
            ]
        );
    }
}
