<?php

namespace Tests\Feature\Project;

use App\Http\Resources\ProjectResource;
use App\Models\Monitoring;
use App\Models\Notice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectMonitoringIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'monitoring', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'coord_monitoring', 'guard_name' => 'web']);
    }

    #[Test]
    public function index_includes_monitoring_in_response(): void
    {
        $user = User::factory()->create();
        $notice = Notice::factory()->create();
        $project = Project::factory()->create(['notice_id' => $notice->id]);
        Monitoring::factory()->create(['project_id' => $project->id, 'created_by' => $user->id]);

        $response = $this->actingAs($user)
            ->get(route('notices.projects', $notice));

        $response->assertStatus(200);

        $projects = $response->original->getData()['page']['props']['projects'];
        $found = collect($projects)->firstWhere('id', $project->id);

        $this->assertArrayHasKey('monitoring', $found);
        $this->assertEquals($project->id, $found['monitoring']['project_id']);
    }

    #[Test]
    public function project_resource_includes_monitoring_when_relation_loaded(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $monitoring = Monitoring::factory()->create(['project_id' => $project->id, 'created_by' => $user->id]);

        $project->load('monitoring');

        $resource = new ProjectResource($project);
        $data = $resource->toArray(Request::create('/'));

        $this->assertArrayHasKey('monitoring', $data);
        $this->assertEquals($monitoring->id, $data['monitoring']->id);
    }

    #[Test]
    public function project_resource_omits_monitoring_when_relation_not_loaded(): void
    {
        $project = Project::factory()->create();

        $resource = new ProjectResource($project);
        $data = $resource->toArray(Request::create('/'));

        $this->assertArrayNotHasKey('monitoring', $data);
    }
}
