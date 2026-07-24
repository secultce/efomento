<?php

namespace Tests\Feature\Controllers;

use App\Models\Budget;
use App\Models\Installment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallmentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_remarks_endpoint_does_not_update_notice_installment_number(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $budget = Budget::factory()->create([
            'project_id' => $project->id,
        ]);
        $installment = Installment::factory()->create([
            'budget_id' => $budget->id,
            'installment_number' => 1,
            'notice_installment_number' => 2,
            'remarks' => null,
        ]);

        $response = $this->actingAs($user)->patch(
            route('projects.installments.remarks.update', [
                'project' => $project,
                'installment' => $installment->installment_number,
            ]),
            [
                'remarks' => 'Observação informada no pagamento.',
                'notice_installment_number' => 99,
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('installments', [
            'id' => $installment->id,
            'notice_installment_number' => 2,
            'remarks' => 'Observação informada no pagamento.',
        ]);
    }
}
