<?php

namespace Tests\Feature\Project;

use App\Models\Monitoring;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonitoringControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
    }

    #[Test]
    public function guest_cannot_store_monitoring(): void
    {
        $this->postJson(route('projects.monitorings.store', $this->project))
            ->assertUnauthorized();
    }

    #[Test]
    public function authenticated_user_can_create_monitoring(): void
    {
        $this->actingAs($this->user)
            ->post(route('projects.monitorings.store', $this->project), [
                'technical_opinions' => [
                    ['suite_number' => '1234', 'processing_date' => '2024-03-15'],
                ],
                'observations' => 'Observação de teste',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('monitorings', [
            'project_id' => $this->project->id,
            'observations' => 'Observação de teste',
            'created_by' => $this->user->id,
        ]);

        $monitoring = $this->project->fresh()->monitoring;
        $this->assertCount(1, $monitoring->technical_opinions);
        $this->assertEquals('1234', $monitoring->technical_opinions[0]['suite_number']);
    }

    #[Test]
    public function store_uses_update_or_create_when_monitoring_already_exists(): void
    {
        Monitoring::factory()->for($this->project)->create([
            'observations' => 'Observação antiga',
        ]);

        $this->actingAs($this->user)
            ->post(route('projects.monitorings.store', $this->project), [
                'technical_opinions' => [],
                'observations' => 'Observação nova',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('monitorings', 1);
        $this->assertDatabaseHas('monitorings', [
            'project_id' => $this->project->id,
            'observations' => 'Observação nova',
        ]);
    }

    #[Test]
    public function authenticated_user_can_update_monitoring(): void
    {
        $monitoring = Monitoring::factory()->for($this->project)->create([
            'technical_opinions' => [['suite_number' => '0001', 'processing_date' => '2024-01-10']],
            'observations' => 'Antes',
        ]);

        $this->actingAs($this->user)
            ->patch(route('projects.monitorings.update', [$this->project, $monitoring]), [
                'technical_opinions' => [
                    ['suite_number' => '0001', 'processing_date' => '2024-01-10'],
                    ['suite_number' => '0002', 'processing_date' => '2024-06-20'],
                ],
                'observations' => 'Depois',
            ])
            ->assertRedirect();

        $fresh = $monitoring->fresh();
        $this->assertCount(2, $fresh->technical_opinions);
        $this->assertEquals('Depois', $fresh->observations);
    }

    #[Test]
    public function store_validates_suite_number_is_required_when_opinions_present(): void
    {
        $this->actingAs($this->user)
            ->post(route('projects.monitorings.store', $this->project), [
                'technical_opinions' => [
                    ['suite_number' => '', 'processing_date' => '2024-03-15'],
                ],
            ])
            ->assertSessionHasErrors('technical_opinions.0.suite_number');
    }

    #[Test]
    public function store_accepts_null_observations(): void
    {
        $this->actingAs($this->user)
            ->post(route('projects.monitorings.store', $this->project), [
                'technical_opinions' => [],
                'observations' => null,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('monitorings', [
            'project_id' => $this->project->id,
            'observations' => null,
        ]);
    }
}
