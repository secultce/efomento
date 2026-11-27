<?php

namespace App\Services;

use App\Enums\ExpenseNature;
use App\Exceptions\Domain\BusinessRuleException;
use App\Models\Notice;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectExpenseNatureService
{
    public function assign(array $projectIds, ExpenseNature $nature): void
    {
        $projects = Project::with(['opening', 'notice', 'budgets.installments.budgetAllocation'])
            ->whereIn('id', $projectIds)
            ->get();

        $projects->pluck('notice')
            ->filter()
            ->unique('id')
            ->each(fn (Notice $notice) => $this->ensureNoticeHasNature($notice, $nature));

        foreach ($projects as $project) {
            if (! $project->opening) {
                throw new BusinessRuleException("O projeto {$project->number} não possui abertura.");
            }

            $this->ensureCanChange($project, $nature);
        }

        DB::transaction(function () use ($projects, $nature) {
            foreach ($projects as $project) {
                $project->opening->update(['expense_nature' => $nature]);
            }
        });
    }

    public function availableOptions(Notice $notice): array
    {
        $imported = $notice->budgetAllocations()
            ->whereNotNull('expense_nature')
            ->reorder()
            ->distinct()
            ->pluck('expense_nature')
            ->map(fn ($nature) => $nature instanceof ExpenseNature ? $nature->value : $nature)
            ->all();

        return collect(ExpenseNature::options())
            ->filter(fn (array $option) => in_array($option['value'], $imported, true))
            ->values()
            ->all();
    }

    /**
     * Bloqueia a troca de natureza quando o projeto já tem parcela vinculada a uma
     * dotação de outra natureza: empenho, liquidação e pagamento foram feitos sobre
     * aquela dotação, então a correção precisa passar pelo suporte.
     */
    public function ensureCanChange(Project $project, ?ExpenseNature $nature): void
    {
        if ($nature === null || $project->opening?->expense_nature === $nature) {
            return;
        }

        $project->loadMissing('budgets.installments.budgetAllocation');

        $hasConflictingInstallment = collect($project->budgets?->installments)
            ->contains(function ($installment) use ($nature) {
                $allocationNature = $installment->budgetAllocation?->expense_nature;

                return $allocationNature !== null && $allocationNature !== $nature;
            });

        if ($hasConflictingInstallment) {
            throw new BusinessRuleException(
                "Não é possível alterar a natureza da despesa do projeto {$project->number}: "
                .'ele já possui parcela vinculada a uma dotação de outra natureza. '
                .'Essa alteração é crítica e deve ser feita pelo suporte; abra um chamado.'
            );
        }
    }

    private function ensureNoticeHasNature(Notice $notice, ExpenseNature $nature): void
    {
        if (! $notice->budgetAllocations()->exists()) {
            throw new BusinessRuleException(
                'A planilha de dotação orçamentária ainda não foi importada para este edital. '
                    .'Faça o upload das informações orçamentárias antes de atribuir a natureza da despesa.'
            );
        }

        if (! $notice->budgetAllocations()->where('expense_nature', $nature->value)->exists()) {
            throw new BusinessRuleException(
                'A planilha de dotação orçamentária do edital não possui dotações da natureza '
                    .$nature->label().'.'
            );
        }
    }
}
