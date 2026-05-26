<?php

namespace App\Services;

use App\Models\Agent;
use App\Support\Cpf;

class AgentService
{
    public function updateOrCreateByCpf(
        ?string $cpf,
        ?string $name = null,
    ): ?Agent {
        $cpf = Cpf::normalize($cpf);

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
