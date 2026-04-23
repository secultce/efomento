<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Installment;
use Illuminate\Database\Eloquent\Factories\Sequence;
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
        $this->assertNotNull($budget->createdBy);
    }

    public function test_it_can_have_installments()
    {
        $budget = $this->createBudgetWithInstallments(3);

        $this->assertCount(3, $budget->installments()->get());
    }

    public function test_deleting_budget_deletes_installments()
    {
        $budget = $this->createBudgetWithInstallments(3);
        $budgetId = $budget->id;

        $budget->delete();

        $this->assertSoftDeleted('budgets', [
            'id' => $budgetId
        ]);
    }

    public function test_a_budget_with_installments_is_persisted_correctly()
    {
        $budget = $this->createBudgetWithInstallments(5);

        $this->assertDatabaseCount('budgets', 1);
        $this->assertDatabaseCount('installments', 5);

        $this->assertEquals(5, $budget->installments()->count());
    }

    private function createBudgetWithInstallments(int $installmentCount = 3): Budget
    {
        $budget = Budget::factory()->create();

        Installment::factory()
            ->count($installmentCount)
            ->state(new Sequence(
                fn($sequence) => [
                    'budget_id' => $budget->id,
                    'installment_number' => $sequence->index + 1,
                ]
            ))
            ->create();

        return $budget;
    }
}
