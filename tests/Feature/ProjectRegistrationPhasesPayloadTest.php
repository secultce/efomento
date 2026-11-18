<?php

namespace Tests\Feature;

use App\Jobs\SyncProjectPhasesProjectJob;
use App\Models\Agent;
use App\Models\Notice;
use App\Models\Project;
use App\Models\ProjectRegistrationPhase;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectRegistrationPhasesPayloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['monitoring', 'coord_monitoring', 'super_admin', 'legal_analysis', 'coord_legal'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    private function phase(Project $project, int $order, int $registrationId): ProjectRegistrationPhase
    {
        return ProjectRegistrationPhase::create([
            'project_id' => $project->id,
            'registration_id' => $registrationId,
            'phase_order' => $order,
            'is_first' => $order === 1,
            'status' => 10,
            'data' => ['order' => $order, 'sections' => []],
        ]);
    }

    public function test_show_exposes_registration_phases_ordered_by_phase_order(): void
    {
        $project = Project::factory()->create();
        $this->phase($project, 2, 200);
        $this->phase($project, 1, 100);

        $this->actingAs(User::factory()->create())
            ->get(route('notices.projects.show', [$project->notice, $project]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('project.registration_phases', 2)
                ->where('project.registration_phases.0.phase_order', 1)
                ->where('project.registration_phases.1.phase_order', 2)
            );
    }

    public function test_index_does_not_expose_registration_phases(): void
    {
        $notice = Notice::factory()->create();
        $project = Project::factory()->create(['notice_id' => $notice->id]);
        $this->phase($project, 1, 100);

        $this->actingAs(User::factory()->create())
            ->get(route('notices.projects', $notice))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('projects', 1)
                ->missing('projects.0.registration_phases')
            );
    }

    public function test_new_project_dispatches_phase_sync(): void
    {
        Queue::fake();

        $project = app(ProjectService::class)->createFromRegistrationIfMissing(
            registrationId: 123,
            registration: [],
            details: ['registration' => ['number' => 'on-123']],
            agentId: Agent::factory()->create()->id,
            noticeId: Notice::factory()->create()->id,
            categoryId: null,
        );

        Queue::assertPushed(
            SyncProjectPhasesProjectJob::class,
            fn (SyncProjectPhasesProjectJob $job) => $job->projectId === $project->id
        );
    }

    public function test_existing_project_does_not_dispatch_phase_sync(): void
    {
        Queue::fake();
        $project = Project::factory()->create(['registration_id' => '123']);

        app(ProjectService::class)->createFromRegistrationIfMissing(
            registrationId: 123,
            registration: [],
            details: [],
            agentId: $project->agent_id,
            noticeId: $project->notice_id,
            categoryId: null,
        );

        Queue::assertNotPushed(SyncProjectPhasesProjectJob::class);
    }
}
