<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\ProfileSnapshot;
use App\Support\DocumentNumber;

class AgentService
{
    public function updateOrCreatedByDocument(
        ?string $document,
        ?string $name = null,
    ): ?Agent {
        $document = DocumentNumber::normalize($document);

        if (! $document) {
            return null;
        }

        $agentId = ProfileSnapshot::query()
            ->where('cpf_cnpj', $document)
            ->where('object_type', (new Agent)->getMorphClass())
            ->orderByDesc('recorded_at')
            ->value('object_id');

        if ($agentId) {
            $agent = Agent::find($agentId);

            if ($agent) {
                $agent->update(['name' => trim((string) $name) ?: 'Nome não informado']);

                return $agent;
            }
        }

        return Agent::create([
            'name' => trim((string) $name) ?: 'Nome não informado',
        ]);
    }
}
