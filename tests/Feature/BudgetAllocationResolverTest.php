<?php

namespace Tests\Feature;

use App\Enums\ExpenseNature;
use App\Models\BudgetAllocation;
use App\Models\ProfileSnapshot;
use App\Models\Project;
use App\Services\BudgetAllocationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetAllocationResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_matches_the_allocation_by_expense_nature_and_macroregion(): void
    {
        $project = $this->projectInCariri(ExpenseNature::CONTRIBUICOES_COM_FINS_LUCRATIVOS);

        foreach (ExpenseNature::cases() as $nature) {
            foreach (['01 – CARIRI', '02 – CENTRO SUL'] as $macroregion) {
                $this->allocation($project, $nature, $macroregion);
            }
        }

        $allocation = app(BudgetAllocationResolver::class)->resolveAvailable($project);

        $this->assertSame(ExpenseNature::CONTRIBUICOES_COM_FINS_LUCRATIVOS, $allocation->expense_nature);
        $this->assertSame('01 – CARIRI', $allocation->planning_macroregion);
    }

    public function test_it_does_not_fall_back_to_another_expense_nature(): void
    {
        $project = $this->projectInCariri(ExpenseNature::CONTRIBUICOES_SEM_FINS_LUCRATIVOS);

        $this->allocation($project, ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA, '01 – CARIRI');

        $this->assertNull(app(BudgetAllocationResolver::class)->resolveAvailable($project));
    }

    public function test_without_expense_nature_on_the_opening_it_matches_only_by_macroregion(): void
    {
        $project = $this->projectInCariri(null);

        $this->allocation($project, ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA, '02 – CENTRO SUL');
        $match = $this->allocation($project, ExpenseNature::CONTRIBUICOES_COM_FINS_LUCRATIVOS, '01 – CARIRI');

        $this->assertTrue(app(BudgetAllocationResolver::class)->resolveAvailable($project)->is($match));
    }

    public function test_budget_opinion_without_linked_allocation_respects_the_opening_expense_nature(): void
    {
        $project = $this->projectInCariri(ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA);

        $match = $this->allocation($project, ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA, '01 – CARIRI');
        $this->allocation($project, ExpenseNature::CONTRIBUICOES_COM_FINS_LUCRATIVOS, '01 – CARIRI');

        $this->assertTrue(app(BudgetAllocationResolver::class)->resolveForBudgetOpinion($project)->is($match));
    }

    public function test_budget_opinion_falls_back_to_the_newest_allocation_of_the_same_nature(): void
    {
        $project = $this->projectInCariri(ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA);

        $this->allocation($project, ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA, '02 – CENTRO SUL');
        $newestSameNature = $this->allocation($project, ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA, '03 – GRANDE FORTALEZA');
        $this->allocation($project, ExpenseNature::CONTRIBUICOES_COM_FINS_LUCRATIVOS, '04 – LITORAL LESTE');

        $this->assertTrue(app(BudgetAllocationResolver::class)->resolveForBudgetOpinion($project)->is($newestSameNature));
    }

    public function test_legacy_allocations_without_expense_nature_still_match_by_macroregion(): void
    {
        $project = $this->projectInCariri(ExpenseNature::AUXILIO_FINANCEIRO_PESSOA_FISICA);

        $this->allocation($project, null, '02 – CENTRO SUL');
        $match = $this->allocation($project, null, '01 – CARIRI');

        $this->assertTrue(app(BudgetAllocationResolver::class)->resolveAvailable($project)->is($match));
    }

    private function projectInCariri(?ExpenseNature $nature): Project
    {
        $project = Project::factory()->create();
        $project->opening->update(['expense_nature' => $nature]);

        ProfileSnapshot::factory()->create([
            'object_id' => $project->agent_id,
            'object_type' => 'agent',
            'city' => 'Crato',
        ]);

        return $project->fresh();
    }

    private function allocation(Project $project, ?ExpenseNature $nature, string $macroregion): BudgetAllocation
    {
        return BudgetAllocation::factory()->create([
            'notice_id' => $project->notice_id,
            'expense_element' => $nature?->code(),
            'expense_nature' => $nature,
            'planning_macroregion' => $macroregion,
        ]);
    }
}
