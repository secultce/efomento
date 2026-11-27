<?php

namespace App\Services;

use App\Contracts\StageValidatorInterface;
use App\Enums\DocumentType;
use App\Enums\ExpenseNature;
use App\Enums\ProfileSnapshotSource;
use App\Models\Opening;
use App\Models\Project;
use App\Models\User;
use BackedEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpeningUpdateService implements StageValidatorInterface
{
    public function __construct(
        protected ProfileSnapshotService $snapshotService,
        protected ProjectExpenseNatureService $expenseNatureService,
    ) {}

    public function handle(Project $project, Opening $opening, array $data): void
    {
        $openingData = $data['opening'] ?? [];
        $formalizationData = $data['formalization'] ?? [];
        $agentData = $data['agent'] ?? [];
        $supervisors = $openingData['supervisors'] ?? [];

        unset($openingData['supervisors']);

        DB::transaction(function () use ($project, $opening, $openingData, $formalizationData, $agentData, $supervisors) {
            if (array_key_exists('expense_nature', $openingData)) {
                $this->ensureExpenseNatureCanChange(
                    $project,
                    ExpenseNature::tryFrom((string) $openingData['expense_nature'])
                );
            }

            $this->updateOpening($opening, $openingData);

            $this->updateFormalization($project, $formalizationData);

            $this->updateAgentSnapshot($project, $agentData);

            $this->syncSupervisors($project, $supervisors);
        });
    }

    /**
     * Mesmas regras da atribuição em massa: não troca natureza com parcela vinculada
     * a outra natureza, e só aceita naturezas presentes na planilha de dotação do edital.
     */
    protected function ensureExpenseNatureCanChange(Project $project, ?ExpenseNature $nature): void
    {
        // Com a abertura bloqueada e as parcelas recarregadas, a verificação não pode
        // ser invalidada por um vínculo de dotação gravado em paralelo.
        $current = $project->lockOpeningForUpdate()?->expense_nature;
        $project->unsetRelation('budgets');

        $this->expenseNatureService->ensureCanChange($project, $nature);

        if ($nature && $nature !== $current && $project->notice) {
            $this->expenseNatureService->ensureNoticeHasNature($project->notice, $nature);
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
            'bank_id' => 'Banco',
            'account_type' => 'Tipo de conta',
            'branch' => 'Agência',
            'account' => 'Conta',
            'expense_nature' => 'Natureza da despesa',
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

        if (blank($opening->expense_nature)
            && $project->notice
            && $this->expenseNatureService->availableOptions($project->notice) === []) {
            throw ValidationException::withMessages([
                'opening' => 'A natureza da despesa depende da planilha de dotação orçamentária, que ainda não foi '
                    .'importada para este edital. Solicite ao setor orçamentário o upload da planilha antes de tramitar.',
            ]);
        }

        if ($missingFields->isNotEmpty()) {
            throw ValidationException::withMessages([
                'opening' => 'Preencha e salve os campos obrigatórios antes de tramitar: '
                    .$missingFields->join(', ').'.',
            ]);
        }

        $this->validateRequiredDocuments($project);
    }

    private function validateRequiredDocuments(Project $project): void
    {
        $requiredDocuments = collect(
            DocumentType::requiredForOpeningAdvance()
        );

        $requiredDocumentTypes = $requiredDocuments
            ->map(fn (DocumentType $type) => $type->value)
            ->all();

        $generatedDocumentTypes = $project->documents()
            ->whereIn('type', $requiredDocumentTypes)
            ->pluck('type')
            ->map(
                fn ($type) => $type instanceof BackedEnum
                    ? $type->value
                    : trim((string) $type)
            )
            ->unique();

        $missingDocuments = $requiredDocuments
            ->reject(
                fn (DocumentType $type) => $generatedDocumentTypes
                    ->containsStrict($type->value)
            )
            ->map(fn (DocumentType $type) => $type->fullLabel())
            ->values();

        if ($missingDocuments->isEmpty()) {
            return;
        }

        throw ValidationException::withMessages([
            'documents' => 'Gere os documentos obrigatórios antes de tramitar: '
                .$missingDocuments->join(', ')
                .'.',
        ]);
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
