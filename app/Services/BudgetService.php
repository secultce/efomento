<?php

namespace App\Services;

use App\Contracts\StageValidatorInterface;
use App\Enums\DocumentPhase;
use App\Enums\DocumentType;
use App\Models\Budget;
use App\Models\Installment;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BudgetService implements StageValidatorInterface
{
    public function __construct(
        private readonly BudgetAllocationResolver $budgetAllocationResolver,
    ) {}

    public function create(Project $project, array $data): Budget
    {
        return DB::transaction(function () use ($project, $data) {
            $userId = auth()->id();

            $budget = Budget::create([
                'project_id' => $project->id,
                'created_by' => $userId,
                ...$this->budgetData($data),
            ]);

            $installment = $budget->installments()->create([
                ...$this->installmentData($data),
                'installment_number' => $project->current_installment_cycle,
                'created_by' => $userId,
            ]);

            $this->linkBudgetAllocation($project, $installment);

            return $budget;
        });
    }

    public function update(Project $project, Budget $budget, array $data): Budget
    {
        return DB::transaction(function () use ($project, $budget, $data) {
            $this->updateBudgetDataIfFirstCycle($budget, $project, $data);
            $installment = $this->upsertInstallment($budget, $project, $data);
            $this->linkBudgetAllocation($project, $installment);

            return $budget;
        });
    }

    private function updateBudgetDataIfFirstCycle(Budget $budget, Project $project, array $data): void
    {
        if ($project->current_installment_cycle !== 1) {
            return;
        }

        $budget->update(
            $this->budgetData($data)
        );
    }

    private function upsertInstallment(Budget $budget, Project $project, array $data): Installment
    {
        $installment = $budget->installments()->firstOrNew([
            'installment_number' => $project->current_installment_cycle,
        ]);

        $installment->fill(
            $this->installmentData($data)
        );

        if (! $installment->exists) {
            $installment->created_by = auth()->id();
        }

        $installment->save();

        return $installment;
    }

    private function linkBudgetAllocation(Project $project, Installment $installment): void
    {
        if ($installment->budget_allocation_id) {
            return;
        }

        $allocation = $this->budgetAllocationResolver->resolveAvailable($project);

        if (! $allocation) {
            return;
        }

        $installment->budgetAllocation()->associate($allocation);
        $installment->save();
    }

    private function budgetData(array $data): array
    {
        return [
            'processing_date_for_codip' => $data['processing_date_for_codip'] ?? null,
            'processing_date_for_coafi' => $data['processing_date_for_coafi'] ?? null,
        ];
    }

    private function installmentData(array $data): array
    {
        return [
            'notice_installment_number' => $data['notice_installment_number'] ?? null,
            'amount' => $data['installment_amount'] ?? null,
            'request_date' => $data['installment_request_date'] ?? null,
            'justification' => $data['installment_justification'] ?? null,
            'observations' => $data['installment_observations'] ?? null,
        ];
    }

    public function ensureCanAdvance(Project $project): void
    {
        $budget = $project->budgets;

        if (! $budget) {
            throw ValidationException::withMessages([
                'budget' => 'Preencha e salve os dados do orçamento antes de tramitar.',
            ]);
        }

        $this->validateRequiredFields($project, $budget);
        $this->validateRequiredDocuments($project);
    }

    private function validateRequiredFields(Project $project, Budget $budget): void
    {
        $currentInstallment = $budget->installments()
            ->where('installment_number', $project->current_installment_cycle)
            ->first();

        $missingFields = collect();

        if (blank($currentInstallment?->notice_installment_number)) {
            $missingFields->push('Nº da parcela no edital');
        }

        if (blank($currentInstallment?->amount)) {
            $missingFields->push('Valor da parcela');
        }

        if ($missingFields->isNotEmpty()) {
            throw ValidationException::withMessages([
                'budget' => 'Preencha todos os campos obrigatórios antes de tramitar: '
                    .$missingFields->join(', ')
                    .'.',
            ]);
        }
    }

    private function validateRequiredDocuments(Project $project): void
    {
        $hasBudgetOpinion = $project->documents()
            ->where('phase', DocumentPhase::BUDGET->value ?? 'budget')
            ->where('type', DocumentType::DO->value ?? 'do')
            ->exists();

        if (! $hasBudgetOpinion) {
            throw ValidationException::withMessages([
                'documents' => 'O despacho orçamentário precisa ser gerado antes da tramitação.',
            ]);
        }
    }
}
