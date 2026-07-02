<?php

namespace Tests\Feature\Supervisor;

use App\Models\Opening;
use App\Models\OpeningSupervisor;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectSupervisorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MassSupervisorAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private ProjectSupervisorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProjectSupervisorService::class);
    }

    public function test_first_supervisor_is_assigned_as_principal(): void
    {
        $project = Project::factory()->has(Opening::factory())->create();
        $principal = User::factory()->create();
        $alternate = User::factory()->create();

        $this->service->assign([$project->id], [$principal->id, $alternate->id]);

        $this->assertDatabaseHas('opening_supervisor', [
            'opening_id' => $project->opening->id,
            'user_id' => $principal->id,
            'type' => OpeningSupervisor::TYPE_PRINCIPAL,
            'is_active' => true,
        ]);
    }

    public function test_second_supervisor_is_assigned_as_alternate(): void
    {
        $project = Project::factory()->has(Opening::factory())->create();
        $principal = User::factory()->create();
        $alternate = User::factory()->create();

        $this->service->assign([$project->id], [$principal->id, $alternate->id]);

        $this->assertDatabaseHas('opening_supervisor', [
            'opening_id' => $project->opening->id,
            'user_id' => $alternate->id,
            'type' => OpeningSupervisor::TYPE_ALTERNATE,
            'is_active' => true,
        ]);
    }

    public function test_single_supervisor_is_assigned_as_principal_only(): void
    {
        $project = Project::factory()->has(Opening::factory())->create();
        $principal = User::factory()->create();

        $this->service->assign([$project->id], [$principal->id]);

        $this->assertDatabaseCount('opening_supervisor', 1);
        $this->assertDatabaseHas('opening_supervisor', [
            'opening_id' => $project->opening->id,
            'user_id' => $principal->id,
            'type' => OpeningSupervisor::TYPE_PRINCIPAL,
            'is_active' => true,
        ]);
    }

    public function test_supervisors_are_assigned_to_all_selected_projects(): void
    {
        $projects = Project::factory()
            ->count(3)
            ->has(Opening::factory())
            ->create();

        $principal = User::factory()->create();
        $alternate = User::factory()->create();

        $this->service->assign($projects->pluck('id')->toArray(), [$principal->id, $alternate->id]);

        foreach ($projects as $project) {
            $this->assertDatabaseHas('opening_supervisor', [
                'opening_id' => $project->opening->id,
                'user_id' => $principal->id,
                'type' => OpeningSupervisor::TYPE_PRINCIPAL,
                'is_active' => true,
            ]);

            $this->assertDatabaseHas('opening_supervisor', [
                'opening_id' => $project->opening->id,
                'user_id' => $alternate->id,
                'type' => OpeningSupervisor::TYPE_ALTERNATE,
                'is_active' => true,
            ]);
        }
    }

    public function test_previous_active_supervisors_are_deactivated_on_mass_assignment(): void
    {
        $project = Project::factory()->has(Opening::factory())->create();
        $old = User::factory()->create();

        OpeningSupervisor::factory()->principal()->create([
            'opening_id' => $project->opening->id,
            'user_id' => $old->id,
        ]);

        $new = User::factory()->create();

        $this->service->assign([$project->id], [$new->id]);

        $this->assertDatabaseHas('opening_supervisor', [
            'opening_id' => $project->opening->id,
            'user_id' => $old->id,
            'is_active' => false,
        ]);

        $this->assertNotNull(
            OpeningSupervisor::where('opening_id', $project->opening->id)
                ->where('user_id', $old->id)
                ->whereNotNull('removed_at')
                ->first()
        );
    }

    public function test_new_supervisors_remain_active_after_mass_assignment(): void
    {
        $project = Project::factory()->has(Opening::factory())->create();
        $principal = User::factory()->create();
        $alternate = User::factory()->create();

        $this->service->assign([$project->id], [$principal->id, $alternate->id]);

        $active = OpeningSupervisor::where('opening_id', $project->opening->id)
            ->where('is_active', true)
            ->get();

        $this->assertCount(2, $active);
    }

    public function test_throws_exception_when_project_has_no_opening(): void
    {
        $project = Project::factory()->create();
        $project->opening->forceDelete();
        $user = User::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/não possui abertura/');

        $this->service->assign([$project->id], [$user->id]);
    }

    public function test_rolls_back_all_assignments_when_one_project_has_no_opening(): void
    {
        $validProject = Project::factory()->has(Opening::factory())->create();
        $invalidProject = Project::factory()->create();
        $invalidProject->opening->forceDelete();
        $user = User::factory()->create();

        $exceptionThrown = false;
        try {
            $this->service->assign(
                [$validProject->id, $invalidProject->id],
                [$user->id]
            );
        } catch (\Exception) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown, 'Expected exception was not thrown');
        $this->assertDatabaseCount('opening_supervisor', 0);
    }
}
