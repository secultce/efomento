<?php

namespace App\Services;

use App\Enums\ProfileSnapshotSource;
use App\Models\Opening;
use App\Models\Project;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpeningUpdateService
{
    public function handle(Project $project, Opening $opening, array $data): void
    {
        try {
            DB::transaction(function () use ($project, $opening, $data) {

                $openingData = $data['opening'] ?? [];
                $formalizationData = $data['formalization'] ?? [];
                $agentData = $data['agent'] ?? [];
                $supervisors = $openingData['supervisors'] ?? [];

                unset($openingData['supervisors']);

                $this->updateOpening($opening, $openingData);

                $this->updateFormalization($project, $formalizationData);

                $this->updateAgentSnapshot($project, $agentData);

                $this->syncSupervisors($project, $supervisors);
            });
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function updateOpening(Opening $opening, array $data): void
    {
        $opening->update($data);
    }

    protected function updateFormalization(Project $project, array $data): void
    {
        if (empty($data)) {
            return;
        }

        $project->formalizations()->updateOrCreate(
            ['project_id' => $project->id],
            $data
        );
    }

    protected function updateAgentSnapshot(Project $project, array $agentData): void
    {
        if (! $project->agent) {
            return;
        }

        $agent = $project->agent;

        app(ProfileSnapshotService::class)->recordIfChanged(
            $agent,
            [
                'source' => ProfileSnapshotSource::AGENT_UPDATE,

                'name' => $agentData['name'] ?? $agent->name,
                'cpf_cnpj' => $agentData['cpf_cnpj'] ?? null,
                'director_position' => $agentData['director_position'] ?? $agent->director_position,
                'director_email' => $agentData['director_email'] ?? $agent->director_email,

                ...$agentData,

                'recorded_at' => now(),
            ],
            ProfileSnapshotSource::AGENT_UPDATE
        );
    }

    public function ensureCanAdvance(Project $project): void
    {
        $opening = $project->opening;

        if (! $opening) {
            throw ValidationException::withMessages([
                'opening' => 'Preencha e salve os dados da abertura antes de tramitar.',
            ]);
        }

        $requiredOpeningFields = [
            'opening_nup' => 'Número do processo',
            'opening_date' => 'Data de abertura do processo',
            'opened_by' => 'Responsável por abrir o processo',
            'agent_status' => 'Status do agente cultural',
            'bank' => 'Banco',
            'account_type' => 'Tipo de conta',
            'branch' => 'Agência',
            'account' => 'Conta',
        ];

        $missingFields = collect($requiredOpeningFields)
            ->filter(fn ($label, $field) => blank($opening->{$field}))
            ->values();

        $hasPrincipalSupervisor = $opening->principalSupervisor()->exists();

        if (! $hasPrincipalSupervisor) {
            $missingFields->push('Fiscal titular');
        }

        $formalization = $project->formalizations;

        $requiredFormalizationFields = [
            'report_status' => 'Regularidade e inadimplência',
            'eparcerias_certificate_date' => 'Data da certidão',
        ];

        $missingFormalizationFields = collect($requiredFormalizationFields)
            ->filter(fn ($label, $field) => ! $formalization || blank($formalization->{$field}))
            ->values();

        $missingFields = $missingFields->merge($missingFormalizationFields);

        if ($missingFields->isNotEmpty()) {
            throw ValidationException::withMessages([
                'opening' => 'Preencha e salve os campos obrigatórios antes de tramitar: '
                    .$missingFields->join(', ').'.',
            ]);
        }
    }

    protected function syncSupervisors(Project $project, array $supervisors): void
    {
        $typed = collect($supervisors)
            ->filter(fn ($s) => ! empty($s['id']))
            ->map(fn ($s) => ['id' => $s['id'], 'type' => $s['type'] ?? null])
            ->values()
            ->toArray();

        $project->opening->assignSupervisors($typed);

        $supervisorIds = collect($typed)->pluck('id');

        $users = User::whereIn('id', $supervisorIds)
            ->get()
            ->keyBy('id');

        foreach ($supervisors as $supervisor) {
            if (! isset($supervisor['id'], $supervisor['registration_number'])) {
                continue;
            }

            $user = $users->get($supervisor['id']);

            if (! $user) {
                continue;
            }

            $user->update([
                'registration_number' => $supervisor['registration_number'],
            ]);
        }
    }
}
