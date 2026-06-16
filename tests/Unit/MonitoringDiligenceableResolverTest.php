<?php

namespace Tests\Unit;

use App\Models\Monitoring;
use App\Models\Project;
use App\Services\Diligenceable\MonitoringDiligenceableResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringDiligenceableResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_returns_the_project_monitoring(): void
    {
        // CRIANDO PROJETOS PARA TESTES
        $project = Project::factory()->create();
        $monitoring = Monitoring::factory()->for($project)->create();

        $resolved = (new MonitoringDiligenceableResolver)->resolve($project);

        $this->assertTrue($monitoring->is($resolved));
    }

    public function test_resolve_returns_null_when_project_has_no_monitoring(): void
    {
        $project = Project::factory()->create();

        $this->assertNull((new MonitoringDiligenceableResolver)->resolve($project));
    }
}
