<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    public function create(Project $project, array $data): Budget
    {
        return DB::transaction(function () use ($project, $data) {
            $userId = auth()->id();

            $budget = Budget::create([
                'project_id' => $project->id,
                'created_by' => $userId,
                ...$this->budgetData($data),
            ]);

            $budget->installments()->create([
                ...$this->installmentData($data),
                'installment_number' => $project->current_installment_cycle,
                'created_by' => $userId,
            ]);

            return $budget;
        });
    }

    public function update(Project $project, Budget $budget, array $data): Budget
    {
        return DB::transaction(function () use ($project, $budget, $data) {
            $this->updateBudgetDataIfFirstCycle($budget, $project, $data);
            $this->upsertInstallment($budget, $project, $data);

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

    private function upsertInstallment(Budget $budget, Project $project, array $data): void
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
}
