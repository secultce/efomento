<?php

namespace Tests\Feature\Models;

use App\Models\Payment;
use App\Models\Project;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_a_payment_with_valid_data()
    {
        $payment = Payment::factory()->create();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
        ]);
    }

    /** @test */
    public function it_belongs_to_a_project()
    {
        $project = Project::factory()->create();

        $payment = Payment::factory()->create([
            'project_id' => $project->id,
        ]);

        $this->assertInstanceOf(Project::class, $payment->project);
        $this->assertEquals($project->id, $payment->project->id);
    }

    /** @test */
    public function it_requires_a_project_id()
    {
        $this->expectException(QueryException::class);

        Payment::factory()->create([
            'project_id' => null,
        ]);
    }
}
