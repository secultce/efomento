<?php

namespace App\Services;

use App\Enums\ProfileSnapshotSource;
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

        $agent = Agent::create([
            'name' => trim((string) $name) ?: 'Nome não informado',
        ]);

        $agent->profileSnapshots()->create([
            'cpf_cnpj' => $document,
            'source' => ProfileSnapshotSource::AGENT_UPDATE,
            'recorded_at' => now(),
        ]);

        return $agent;
    }
}
