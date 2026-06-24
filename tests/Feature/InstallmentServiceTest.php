<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Installment;
use App\Models\Project;
use App\Services\InstallmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InstallmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private InstallmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(InstallmentService::class);
    }

    public function test_it_updates_installment_remarks(): void
    {
        $project = Project::factory()->create();

        $budget = Budget::factory()->create([
            'project_id' => $project->id,
        ]);

        $installment = Installment::factory()->create([
            'budget_id' => $budget->id,
            'installment_number' => 1,
            'remarks' => null,
        ]);

        $this->service->update(
            project: $project,
            installment: 1,
            remarks: 'Nova observação da parcela.',
        );

        $this->assertDatabaseHas('installments', [
            'id' => $installment->id,
            'remarks' => 'Nova observação da parcela.',
        ]);
    }

    public function test_it_can_clear_installment_remarks(): void
    {
        $project = Project::factory()->create();

        $budget = Budget::factory()->create([
            'project_id' => $project->id,
        ]);

        $installment = Installment::factory()->create([
            'budget_id' => $budget->id,
            'installment_number' => 1,
            'remarks' => 'Observação antiga.',
        ]);

        $this->service->update(
            project: $project,
            installment: 1,
            remarks: null,
        );

        $this->assertDatabaseHas('installments', [
            'id' => $installment->id,
            'remarks' => null,
        ]);
    }

    public function test_it_throws_validation_exception_when_project_has_no_budget(): void
    {
        $project = Project::factory()->create();

        $this->expectException(ValidationException::class);

        try {
            $this->service->update(
                project: $project,
                installment: 1,
                remarks: 'Qualquer observação.',
            );
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Este projeto ainda não possui orçamento cadastrado.',
                $exception->errors()['remarks'][0]
            );

            throw $exception;
        }
    }

    public function test_it_throws_validation_exception_when_installment_does_not_exist(): void
    {
        $project = Project::factory()->create();

        Budget::factory()->create([
            'project_id' => $project->id,
        ]);

        $this->expectException(ValidationException::class);

        try {
            $this->service->update(
                project: $project,
                installment: 1,
                remarks: 'Qualquer observação.',
            );
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Esta parcela ainda não foi cadastrada para este orçamento.',
                $exception->errors()['remarks'][0]
            );

            throw $exception;
        }
    }
}
