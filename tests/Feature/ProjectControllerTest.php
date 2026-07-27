<?php

namespace Tests\Feature;

use App\Models\Monitoring;
use App\Models\Notice;
use App\Models\Opening;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'monitoring', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'coord_monitoring', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_returns_200_for_authenticated_user(): void
    {
        $notice = Notice::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('notices.projects', $notice));

        $response->assertStatus(200);
    }

    public function test_index_redirects_unauthenticated_user(): void
    {
        $notice = Notice::factory()->create();

        $response = $this->get(route('notices.projects', $notice));

        $response->assertRedirect(route('login'));
    }

    public function test_index_returns_projects_for_the_given_notice(): void
    {
        $notice = Notice::factory()->create();
        Project::factory()->count(3)->create(['notice_id' => $notice->id]);
        Project::factory()->create(); // outro edital, não deve aparecer

        $response = $this->actingAs($this->user)
            ->get(route('notices.projects', $notice));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('projects', 3)
        );
    }

    public function test_index_accepts_search_and_phase_filters(): void
    {
        $notice = Notice::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('notices.projects', $notice).'?search=foo&phase=opening');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('filters.search', 'foo')
            ->where('filters.phase', 'opening')
        );
    }

    public function test_index_returns_monitoring_reports_count_for_the_given_notice(): void
    {
        $notice = Notice::factory()->create();
        $projectsWithMonitoring = Project::factory()->count(2)->create(['notice_id' => $notice->id]);
        Project::factory()->create(['notice_id' => $notice->id]); // sem monitoring, não deve contar

        foreach ($projectsWithMonitoring as $project) {
            Monitoring::factory()->create(['project_id' => $project->id]);
        }

        $response = $this->actingAs($this->user)
            ->get(route('notices.projects', $notice));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('monitoringReportsCount', 2)
        );
    }

    public function test_index_monitoring_reports_count_ignores_other_notices(): void
    {
        $notice = Notice::factory()->create();
        $otherNotice = Notice::factory()->create();

        $project = Project::factory()->create(['notice_id' => $notice->id]);
        Monitoring::factory()->create(['project_id' => $project->id]);

        $otherProject = Project::factory()->create(['notice_id' => $otherNotice->id]);
        Monitoring::factory()->create(['project_id' => $otherProject->id]);

        $response = $this->actingAs($this->user)
            ->get(route('notices.projects', $notice));

        $response->assertInertia(fn ($page) => $page
            ->where('monitoringReportsCount', 1)
        );
    }

    public function test_index_monitoring_reports_count_is_not_affected_by_phase_filter(): void
    {
        $notice = Notice::factory()->create();
        $project = Project::factory()->create(['notice_id' => $notice->id]);
        Monitoring::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($this->user)
            ->get(route('notices.projects', $notice).'?phase=opening');

        $response->assertInertia(fn ($page) => $page
            ->where('monitoringReportsCount', 1)
        );
    }

    // -------------------------------------------------------------------------
    // projectDetail
    // -------------------------------------------------------------------------

    public function test_project_detail_returns_200_for_authenticated_user(): void
    {
        $notice = Notice::factory()->create();
        $project = Project::factory()->create(['notice_id' => $notice->id]);

        $response = $this->actingAs($this->user)
            ->get(route('notices.projects.show', [$notice, $project]));

        $response->assertStatus(200);
    }

    public function test_project_detail_renders_correct_inertia_component(): void
    {
        $notice = Notice::factory()->create();
        $project = Project::factory()->create(['notice_id' => $notice->id]);

        $response = $this->actingAs($this->user)
            ->get(route('notices.projects.show', [$notice, $project]));

        $response->assertInertia(fn ($page) => $page
            ->has('project')
            ->has('supervisorsAvailable')
            ->has('currentStage')
        );
    }

    public function test_project_detail_defaults_to_opening_tab(): void
    {
        $notice = Notice::factory()->create();
        $project = Project::factory()->create(['notice_id' => $notice->id]);

        $response = $this->actingAs($this->user)
            ->get(route('notices.projects.show', [$notice, $project]));

        $response->assertInertia(fn ($page) => $page
            ->where('initialTab', 'opening')
        );
    }

    public function test_project_detail_accepts_custom_tab(): void
    {
        $notice = Notice::factory()->create();
        $project = Project::factory()->create(['notice_id' => $notice->id]);

        $response = $this->actingAs($this->user)
            ->get(route('notices.projects.show', [$notice, $project]).'?tab=monitoring');

        $response->assertInertia(fn ($page) => $page
            ->where('initialTab', 'monitoring')
        );
    }

    public function test_project_detail_can_advance_is_true_when_user_has_current_stage_role(): void
    {
        Role::firstOrCreate(['name' => 'fomentation', 'guard_name' => 'web']);

        $notice = Notice::factory()->create();
        $project = Project::factory()->create(['notice_id' => $notice->id]);
        $this->user->assignRole('fomentation');

        $response = $this->actingAs($this->user)
            ->get(route('notices.projects.show', [$notice, $project]));

        $response->assertInertia(fn ($page) => $page
            ->where('canAdvance', true)
        );
    }

    public function test_project_detail_can_advance_is_false_when_user_lacks_current_stage_role(): void
    {
        $notice = Notice::factory()->create();
        $project = Project::factory()->create(['notice_id' => $notice->id]);

        $response = $this->actingAs($this->user)
            ->get(route('notices.projects.show', [$notice, $project]));

        $response->assertInertia(fn ($page) => $page
            ->where('canAdvance', false)
        );
    }

    // -------------------------------------------------------------------------
    // assignProjectSupervisor
    // -------------------------------------------------------------------------

    public function test_assign_supervisor_requires_selected_projects(): void
    {
        $supervisor = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('projects.assign-supervisors'), [
                'selected_supervisors' => [$supervisor->id],
            ]);

        $response->assertSessionHasErrors('selected_projects');
    }

    public function test_assign_supervisor_requires_selected_supervisors(): void
    {
        $project = Project::factory()->has(Opening::factory())->create();

        $response = $this->actingAs($this->user)
            ->post(route('projects.assign-supervisors'), [
                'selected_projects' => [$project->id],
            ]);

        $response->assertSessionHasErrors('selected_supervisors');
    }

    public function test_assign_supervisor_returns_422_for_nonexistent_project(): void
    {
        $supervisor = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('projects.assign-supervisors'), [
                'selected_projects' => [99999],
                'selected_supervisors' => [$supervisor->id],
            ]);

        $response->assertSessionHasErrors('selected_projects.0');
    }

    public function test_assign_supervisor_succeeds_with_valid_data(): void
    {
        $project = Project::factory()->has(Opening::factory())->create();
        $supervisor = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('projects.assign-supervisors'), [
                'selected_projects' => [$project->id],
                'selected_supervisors' => [$supervisor->id],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
    }

    public function test_assign_supervisor_returns_error_when_project_has_no_opening(): void
    {
        $project = Project::factory()->create();
        $project->opening->forceDelete();
        $supervisor = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('projects.assign-supervisors'), [
                'selected_projects' => [$project->id],
                'selected_supervisors' => [$supervisor->id],
            ]);

        $response->assertSessionHasErrors('message');
    }

    // -------------------------------------------------------------------------
    // createDocument
    // -------------------------------------------------------------------------

    public function test_create_document_requires_type(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('projects.create-document'), [
                'selected_projects' => [$project->id],
                'content' => 'Conteúdo',
            ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_create_document_rejects_invalid_type(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('projects.create-document'), [
                'type' => 'invalido',
                'selected_projects' => [$project->id],
                'content' => 'Conteúdo',
            ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_create_document_requires_selected_projects(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('projects.create-document'), [
                'type' => 'ci',
                'content' => 'Conteúdo',
            ]);

        $response->assertSessionHasErrors('selected_projects');
    }

    public function test_create_document_requires_content(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('projects.create-document'), [
                'type' => 'ci',
                'selected_projects' => [$project->id],
            ]);

        $response->assertSessionHasErrors('content');
    }

    public function test_create_document_succeeds_with_valid_data(): void
    {
        $project = Project::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('projects.create-document'), [
                'type' => 'ci',
                'selected_projects' => [$project->id],
                'content' => 'Conteúdo da CI.',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('documents', [
            'project_id' => $project->id,
            'type' => 'ci',
            'phase' => 'opening',
        ]);
    }

    public function test_create_document_does_not_create_record_for_nonexistent_project(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('projects.create-document'), [
                'type' => 'ci',
                'selected_projects' => [99999],
                'content' => 'Conteúdo válido',
            ]);

        $response->assertSessionHasErrors('selected_projects.0');
        $this->assertDatabaseCount('documents', 0);
    }
}
