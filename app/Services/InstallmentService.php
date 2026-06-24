<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Validation\ValidationException;

class InstallmentService
{
    public function update(Project $project, int $installment, ?string $remarks): void
    {
        $project->loadMissing('budgets.installments');

        $budget = $project->budgets;

        if (! $budget) {
            throw ValidationException::withMessages([
                'remarks' => 'Este projeto ainda não possui orçamento cadastrado.',
            ]);
        }

        $budgetInstallment = $budget->installments()
            ->where('installment_number', $installment)
            ->first();

        if (! $budgetInstallment) {
            throw ValidationException::withMessages([
                'remarks' => 'Esta parcela ainda não foi cadastrada para este orçamento.',
            ]);
        }

        $budgetInstallment->update([
            'remarks' => $remarks,
            'updated_by' => auth()->id(),
        ]);
    }
}
