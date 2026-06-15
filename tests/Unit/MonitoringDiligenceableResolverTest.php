<?php

namespace Tests\Unit;

use App\Models\Monitoring;
use App\Models\Project;
use App\Models\User;
use App\Services\Diligenceable\MonitoringDiligenceableResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonitoringDiligenceableResolverTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function resolve_returns_the_project_monitoring(): void
    {
        $project = Project::factory()->create();
        $monitoring = Monitoring::factory()->for($project)->create();

        $resolved = (new MonitoringDiligenceableResolver)->resolve($project);

        $this->assertTrue($monitoring->is($resolved));
    }

    #[Test]
    public function resolve_creates_monitoring_when_project_has_none(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $project = Project::factory()->create();

        $this->assertNull($project->monitoring);

        $resolved = (new MonitoringDiligenceableResolver)->resolve($project);

        $this->assertInstanceOf(Monitoring::class, $resolved);
        $this->assertEquals($project->id, $resolved->project_id);
        $this->assertDatabaseHas('monitorings', ['project_id' => $project->id]);
    }

    #[Test]
    public function resolve_does_not_duplicate_monitoring_on_repeated_calls(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $project = Project::factory()->create();
        $resolver = new MonitoringDiligenceableResolver;

        $resolver->resolve($project);
        $resolver->resolve($project->fresh());

        $this->assertDatabaseCount('monitorings', 1);
    }
}
