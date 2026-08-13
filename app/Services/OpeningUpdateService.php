<?php

namespace App\Services;

use App\Contracts\StageValidatorInterface;
use App\Enums\ProfileSnapshotSource;
use App\Models\Opening;
use App\Models\Project;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpeningUpdateService implements StageValidatorInterface
{
    public function __construct(
        protected ProfileSnapshotService $snapshotService
    ) {}

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
        $latestSnapshot = $agent->latestSnapshot;

        $previousData = $latestSnapshot ? $latestSnapshot->makeVisible($latestSnapshot->getHidden())->toArray() : [];
        $mergedData = array_merge($previousData, $agentData);

        $this->snapshotService->recordIfChanged(
            $agent,
            $mergedData,
            ProfileSnapshotSource::AGENT_UPDATE,
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
            'creditor_number' => 'Número do cadastro do credor',
            'bank' => 'Banco',
            'account_type' => 'Tipo de conta',
            'branch' => 'Agência',
            'account' => 'Conta',
        ];

        $missingFields = collect($requiredOpeningFields)
            ->filter(fn ($label, $field) => blank($opening->{$field}))
            ->values();

        if (strlen(preg_replace('/\D/', '', (string) $opening->opening_nup)) !== 17) {
            $missingFields->prepend('Número do processo (deve conter 17 dígitos)');
        }

        if (! $opening->principalSupervisor()->exists()) {
            $missingFields->push('Fiscal titular');
        }

        if (! $opening->alternateSupervisor()->exists()) {
            $missingFields->push('Fiscal suplente');
        }

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
