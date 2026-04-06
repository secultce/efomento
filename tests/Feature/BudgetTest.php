<?php

namespace Tests\Feature;

use App\Models\Budget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_a_budget()
    {
        $budget = Budget::factory()->create();

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
        ]);
    }

    public function test_it_belongs_to_a_project_and_user()
    {
        $budget = Budget::factory()->create();

        $this->assertNotNull($budget->project);
        $this->assertNotNull($budget->responsibleUser);
    }

    public function test_it_can_have_installments()
    {
        $budget = Budget::factory()
            ->hasInstallments(3)
            ->create();

        $this->assertCount(3, $budget->installments);
    }

    public function test_deleting_budget_deletes_installments()
    {
        $budget = Budget::factory()
            ->hasInstallments(3)
            ->create();

        $budgetId = $budget->id;

        $budget->delete();

        $this->assertSoftDeleted('budgets', [
            'id' => $budgetId
        ]);

        $this->assertSoftDeleted('installments', [
            'budget_id' => $budgetId
        ]);
    }

    public function test_a_budget_with_installments_is_persisted_correctly()
    {
        $budget = Budget::factory()
            ->hasInstallments(5)
            ->create();

        $this->assertDatabaseCount('budgets', 1);
        $this->assertDatabaseCount('installments', 5);

        $this->assertEquals(5, $budget->installments()->count());
    }
}
