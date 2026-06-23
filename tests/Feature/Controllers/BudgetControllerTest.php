<?php

namespace Tests\Feature\Controllers;

use App\Models\Budget;
use App\Models\Installment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_budget_and_first_installment(): void
    {
        $user = User::factory()->create();

        $project = Project::factory()->create([
            'current_installment_cycle' => 1,
        ]);

        $this->actingAs($user);

        $response = $this->post(
            route('projects.budgets.store', $project),
            [
                'processing_date_for_codip' => '2026-01-10',
                'processing_date_for_coafi' => '2026-01-15',

                'installment_amount' => 1000.50,
                'installment_request_date' => '2026-01-20',
                'installment_justification' => 'Justificativa teste',
                'installment_observations' => 'Observações teste',
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('budgets', [
            'project_id' => $project->id,
            'created_by' => $user->id,
        ]);

        $budget = Budget::first();

        $this->assertEquals(
            '2026-01-10',
            $budget->processing_date_for_codip->format('Y-m-d')
        );

        $this->assertEquals(
            '2026-01-15',
            $budget->processing_date_for_coafi->format('Y-m-d')
        );

        $this->assertDatabaseHas('installments', [
            'budget_id' => $budget->id,
            'amount' => 1000.50,
            'installment_number' => 1,
            'created_by' => $user->id,
        ]);
    }

    public function test_update_updates_budget_and_existing_installment_when_cycle_is_one(): void
    {
        $user = User::factory()->create();

        $project = Project::factory()->create([
            'current_installment_cycle' => 1,
        ]);

        $budget = Budget::factory()->create([
            'project_id' => $project->id,
            'processing_date_for_codip' => '2026-01-01',
            'processing_date_for_coafi' => '2026-01-02',
        ]);

        $installment = Installment::factory()->create([
            'budget_id' => $budget->id,
            'installment_number' => 1,
            'amount' => 500,
        ]);

        $this->actingAs($user);

        $response = $this->patch(
            route('projects.budgets.update', [
                'project' => $project,
                'budget' => $budget,
            ]),
            [
                'processing_date_for_codip' => '2026-02-10',
                'processing_date_for_coafi' => '2026-02-15',

                'installment_amount' => 1500,
                'installment_request_date' => '2026-02-20',
                'installment_justification' => 'Nova justificativa',
                'installment_observations' => 'Nova observação',
            ]
        );

        $response->assertRedirect();

        $budget->refresh();
        $installment->refresh();

        $this->assertEquals('2026-02-10', $budget->processing_date_for_codip->format('Y-m-d'));
        $this->assertEquals('2026-02-15', $budget->processing_date_for_coafi->format('Y-m-d'));

        $this->assertEquals(1500, $installment->amount);
        $this->assertEquals('Nova justificativa', $installment->justification);
        $this->assertEquals('Nova observação', $installment->observations);
    }

    public function test_update_does_not_change_budget_dates_when_cycle_is_greater_than_one(): void
    {
        $user = User::factory()->create();

        $project = Project::factory()->create([
            'current_installment_cycle' => 2,
        ]);

        $budget = Budget::factory()->create([
            'project_id' => $project->id,
            'processing_date_for_codip' => '2026-01-01',
            'processing_date_for_coafi' => '2026-01-02',
        ]);

        $this->actingAs($user);

        $this->patch(
            route('projects.budgets.update', [
                'project' => $project,
                'budget' => $budget,
            ]),
            [
                'processing_date_for_codip' => '2026-12-01',
                'processing_date_for_coafi' => '2026-12-02',
            ]
        );

        $budget->refresh();

        $this->assertEquals(
            '2026-01-01',
            $budget->processing_date_for_codip->format('Y-m-d')
        );

        $this->assertEquals(
            '2026-01-02',
            $budget->processing_date_for_coafi->format('Y-m-d')
        );
    }

    public function test_update_creates_new_installment_for_current_cycle_when_not_exists(): void
    {
        $user = User::factory()->create();

        $project = Project::factory()->create([
            'current_installment_cycle' => 2,
        ]);

        $budget = Budget::factory()->create([
            'project_id' => $project->id,
        ]);

        Installment::factory()->create([
            'budget_id' => $budget->id,
            'installment_number' => 1,
        ]);

        $this->actingAs($user);

        $response = $this->patch(
            route('projects.budgets.update', [
                'project' => $project,
                'budget' => $budget,
            ]),
            [
                'installment_amount' => 2000,
                'installment_request_date' => '2026-03-01',
                'installment_justification' => 'Segunda parcela',
                'installment_observations' => 'Observação segunda parcela',
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('installments', [
            'budget_id' => $budget->id,
            'installment_number' => 2,
            'amount' => 2000,
            'created_by' => $user->id,
        ]);
    }

    public function test_update_updates_existing_installment_for_current_cycle(): void
    {
        $user = User::factory()->create();

        $project = Project::factory()->create([
            'current_installment_cycle' => 2,
        ]);

        $budget = Budget::factory()->create([
            'project_id' => $project->id,
        ]);

        $installment = Installment::factory()->create([
            'budget_id' => $budget->id,
            'installment_number' => 2,
            'amount' => 100,
        ]);

        $this->actingAs($user);

        $this->patch(
            route('projects.budgets.update', [
                'project' => $project,
                'budget' => $budget,
            ]),
            [
                'installment_amount' => 9999,
                'installment_request_date' => '2026-04-01',
                'installment_justification' => 'Atualizada',
                'installment_observations' => 'Atualizada',
            ]
        );

        $installment->refresh();

        $this->assertEquals(9999, $installment->amount);
        $this->assertEquals('Atualizada', $installment->justification);
        $this->assertEquals('Atualizada', $installment->observations);
    }
}
