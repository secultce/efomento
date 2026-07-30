<?php

namespace Tests\Feature\Supervisor;

use App\Models\Opening;
use App\Models\OpeningSupervisor;
use App\Models\Project;
use App\Models\User;
use App\Services\OpeningUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndividualSupervisorAssignmentTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Opening::assignSupervisors()
    // -------------------------------------------------------------------------

    public function test_assign_supervisors_creates_records_with_correct_types(): void
    {
        $opening = Opening::factory()->create();
        $principal = User::factory()->create();
        $alternate = User::factory()->create();

        $opening->assignSupervisors([
            ['id' => $principal->id, 'type' => OpeningSupervisor::TYPE_PRINCIPAL],
            ['id' => $alternate->id, 'type' => OpeningSupervisor::TYPE_ALTERNATE],
        ]);

        $this->assertDatabaseHas('opening_supervisor', [
            'opening_id' => $opening->id,
            'user_id' => $principal->id,
            'type' => OpeningSupervisor::TYPE_PRINCIPAL,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('opening_supervisor', [
            'opening_id' => $opening->id,
            'user_id' => $alternate->id,
            'type' => OpeningSupervisor::TYPE_ALTERNATE,
            'is_active' => true,
        ]);
    }

    public function test_assign_supervisors_deactivates_previous_active_supervisors(): void
    {
        $opening = Opening::factory()->create();
        $old = User::factory()->create();

        OpeningSupervisor::factory()->principal()->create([
            'opening_id' => $opening->id,
            'user_id' => $old->id,
        ]);

        $new = User::factory()->create();
        $opening->assignSupervisors([
            ['id' => $new->id, 'type' => OpeningSupervisor::TYPE_PRINCIPAL],
        ]);

        $this->assertDatabaseHas('opening_supervisor', [
            'opening_id' => $opening->id,
            'user_id' => $old->id,
            'is_active' => false,
        ]);

        $this->assertNotNull(
            OpeningSupervisor::where('opening_id', $opening->id)
                ->where('user_id', $old->id)
                ->whereNotNull('removed_at')
                ->first()
        );
    }

    public function test_assign_supervisors_only_creates_records_for_provided_supervisors(): void
    {
        $opening = Opening::factory()->create();
        $principal = User::factory()->create();

        $opening->assignSupervisors([
            ['id' => $principal->id, 'type' => OpeningSupervisor::TYPE_PRINCIPAL],
        ]);

        $this->assertDatabaseCount('opening_supervisor', 1);
        $this->assertDatabaseHas('opening_supervisor', [
            'user_id' => $principal->id,
            'type' => OpeningSupervisor::TYPE_PRINCIPAL,
            'is_active' => true,
        ]);
    }

    public function test_assign_supervisors_ignores_entries_without_type(): void
    {
        $opening = Opening::factory()->create();
        $user = User::factory()->create();

        $opening->assignSupervisors([
            ['id' => $user->id, 'type' => null],
        ]);

        $this->assertDatabaseCount('opening_supervisor', 0);
    }

    public function test_assign_supervisors_does_not_create_records_when_list_is_empty(): void
    {
        $opening = Opening::factory()->create();

        $opening->assignSupervisors([]);

        $this->assertDatabaseCount('opening_supervisor', 0);
    }

    // -------------------------------------------------------------------------
    // OpeningUpdateService::handle() — sincronização individual via formulário
    // -------------------------------------------------------------------------

    public function test_update_service_stores_typed_supervisors(): void
    {
        $project = Project::factory()->has(Opening::factory())->create();
        $principal = User::factory()->create();
        $alternate = User::factory()->create();

        $service = app(OpeningUpdateService::class);

        $service->handle($project, $project->opening, [
            'opening' => [
                'supervisors' => [
                    ['id' => $principal->id, 'type' => OpeningSupervisor::TYPE_PRINCIPAL, 'registration_number' => 'MAT-001'],
                    ['id' => $alternate->id, 'type' => OpeningSupervisor::TYPE_ALTERNATE, 'registration_number' => 'MAT-002'],
                ],
            ],
            'formalization' => [],
            'agent' => [],
        ]);

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

    public function test_update_service_updates_registration_number_on_user(): void
    {
        $project = Project::factory()->has(Opening::factory())->create();
        $user = User::factory()->create(['registration_number' => null]);

        $service = app(OpeningUpdateService::class);

        $service->handle($project, $project->opening, [
            'opening' => [
                'supervisors' => [
                    ['id' => $user->id, 'type' => OpeningSupervisor::TYPE_PRINCIPAL, 'registration_number' => 'REG-999'],
                ],
            ],
            'formalization' => [],
            'agent' => [],
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'registration_number' => 'REG-999',
        ]);
    }

    public function test_update_service_skips_supervisor_entries_with_null_id(): void
    {
        $project = Project::factory()->has(Opening::factory())->create();

        $service = app(OpeningUpdateService::class);

        $service->handle($project, $project->opening, [
            'opening' => [
                'supervisors' => [
                    ['id' => null, 'type' => OpeningSupervisor::TYPE_PRINCIPAL, 'registration_number' => ''],
                    ['id' => null, 'type' => OpeningSupervisor::TYPE_ALTERNATE, 'registration_number' => ''],
                ],
            ],
            'formalization' => [],
            'agent' => [],
        ]);

        $this->assertDatabaseCount('opening_supervisor', 0);
    }

    public function test_update_service_replaces_previous_supervisors_on_reassignment(): void
    {
        $project = Project::factory()->has(Opening::factory())->create();
        $old = User::factory()->create();

        OpeningSupervisor::factory()->principal()->create([
            'opening_id' => $project->opening->id,
            'user_id' => $old->id,
        ]);

        $new = User::factory()->create();
        $service = app(OpeningUpdateService::class);

        $service->handle($project, $project->opening, [
            'opening' => [
                'supervisors' => [
                    ['id' => $new->id, 'type' => OpeningSupervisor::TYPE_PRINCIPAL, 'registration_number' => ''],
                ],
            ],
            'formalization' => [],
            'agent' => [],
        ]);

        $this->assertDatabaseHas('opening_supervisor', [
            'user_id' => $old->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('opening_supervisor', [
            'user_id' => $new->id,
            'type' => OpeningSupervisor::TYPE_PRINCIPAL,
            'is_active' => true,
        ]);
    }
}
