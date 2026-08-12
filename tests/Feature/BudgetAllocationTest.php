<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetAllocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_a_budget_allocation(): void
    {
        $budgetAllocation = BudgetAllocation::factory()->create();

        $this->assertDatabaseHas('budget_allocations', [
            'id' => $budgetAllocation->id,
            'notice_id' => $budgetAllocation->notice_id,
            'allocation_code' => $budgetAllocation->allocation_code,
            'allocation_number' => $budgetAllocation->allocation_number,
        ]);
    }

    public function test_it_belongs_to_a_notice_and_creator(): void
    {
        $budgetAllocation = BudgetAllocation::factory()->create();

        $this->assertNotNull($budgetAllocation->notice);
        $this->assertNotNull($budgetAllocation->creator);
        $this->assertTrue($budgetAllocation->notice->budgetAllocations->contains($budgetAllocation));
    }

    public function test_a_budget_can_optionally_belong_to_an_allocation(): void
    {
        $budget = Budget::factory()->create();
        $budgetAllocation = BudgetAllocation::factory()->create([
            'notice_id' => $budget->project->notice_id,
        ]);
        $budget->budgetAllocation()->associate($budgetAllocation);
        $budget->save();

        $this->assertTrue($budget->budgetAllocation->is($budgetAllocation));
        $this->assertTrue($budgetAllocation->budgets->contains($budget));
    }
}
