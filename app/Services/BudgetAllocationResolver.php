<?php

namespace App\Services;

use App\Models\BudgetAllocation;
use App\Models\Notice;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
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
        $allocations = $this->candidatesFor($project);

        if ($allocations->isEmpty()) {
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

    public function resolveForBudgetOpinion(?Project $project, ?Notice $notice = null): ?BudgetAllocation
    {
        if (! $project) {
            $notice?->loadMissing('budgetAllocations');

            return $notice?->budgetAllocations?->sortByDesc('id')->first();
        }

        $project->loadMissing([
            'budgets.installments.budgetAllocation',
        ]);

        $currentInstallment = $project->budgets?->installments
            ?->firstWhere('installment_number', $project->current_installment_cycle);

        if ($currentInstallment?->budgetAllocation) {
            return $currentInstallment->budgetAllocation;
        }

        // Sem dotação vinculada: mesma regra da associação (natureza + macrorregião) e,
        // se ainda for ambíguo, a dotação mais recente da natureza da Abertura.
        return $this->resolveAvailable($project)
            ?? $this->candidatesFor($project)->sortByDesc('id')->first();
    }

    /**
     * Dotações do edital elegíveis para o projeto. Se a Abertura tem natureza da
     * despesa, só valem as dotações dessa natureza; sem natureza (projetos anteriores
     * ao campo ou editais sem natureza nas dotações), associa só pela macrorregião.
     *
     * @return Collection<int, BudgetAllocation>
     */
    private function candidatesFor(Project $project): Collection
    {
        $project->loadMissing([
            'agent.latestSnapshot',
            'notice.budgetAllocations',
            'opening',
        ]);

        $allocations = $project->notice?->budgetAllocations ?? new Collection;
        $expenseNature = $project->opening?->expense_nature;

        $noticeHasNatures = $allocations->contains(
            fn (BudgetAllocation $allocation) => $allocation->expense_nature !== null
        );

        if (! $expenseNature || ! $noticeHasNatures) {
            return $allocations;
        }

        return $allocations
            ->filter(fn (BudgetAllocation $allocation) => $allocation->expense_nature === $expenseNature)
            ->values();
    }

    private function normalizeMacroregion(mixed $value): ?string
    {
        $normalized = Str::ascii((string) $value);
        $normalized = preg_replace('/^\s*\d+\s*[-–—]?\s*/u', '', $normalized);
        $normalized = preg_replace('/[^a-zA-Z0-9]+/', '', $normalized);

        return $normalized !== '' ? strtolower($normalized) : null;
    }
}
