<?php

namespace App\Services;

use App\Models\BudgetAllocation;
use App\Models\Project;
use Illuminate\Support\Str;

class BudgetAllocationResolver
{
    public function resolve(Project $project): ?BudgetAllocation
    {
        $project->loadMissing([
            'budgets.installments.budgetAllocation',
        ]);

        $installment = $project->budgets?->installments
            ?->firstWhere('installment_number', $project->current_installment_cycle);

        return $installment
            ? $installment->budgetAllocation
            : $this->resolveAvailable($project);
    }

    public function resolveAvailable(Project $project): ?BudgetAllocation
    {
        $project->loadMissing([
            'agent.latestSnapshot',
            'notice.budgetAllocations',
        ]);

        $allocations = $project->notice?->budgetAllocations;

        if (! $allocations || $allocations->isEmpty()) {
            return null;
        }

        $macroregion = $this->normalizeMacroregion(
            $project->agent?->latestSnapshot?->macroregion
        );

        if ($macroregion) {
            $match = $allocations->first(
                fn (BudgetAllocation $allocation) => $this->normalizeMacroregion(
                    $allocation->planning_macroregion
                ) === $macroregion
            );

            if ($match) {
                return $match;
            }
        }

        return $allocations->count() === 1
            ? $allocations->first()
            : null;
    }

    private function normalizeMacroregion(mixed $value): ?string
    {
        $normalized = Str::ascii((string) $value);
        $normalized = preg_replace('/^\s*\d+\s*[-–—]?\s*/u', '', $normalized);
        $normalized = preg_replace('/[^a-zA-Z0-9]+/', '', $normalized);

        return $normalized !== '' ? strtolower($normalized) : null;
    }
}
