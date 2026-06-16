<?php

namespace Tests\Unit;

use App\Models\Monitoring;
use App\Models\Project;
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
    public function resolve_returns_null_when_project_has_no_monitoring(): void
    {
        $project = Project::factory()->create();

        $resolved = (new MonitoringDiligenceableResolver)->resolve($project);

        $this->assertNull($resolved);
        $this->assertDatabaseCount('monitorings', 0);
    }
}
