<?php

namespace Tests\Feature\Supervisor;

use App\Models\Opening;
use App\Models\OpeningSupervisor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpeningSupervisorRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisors_returns_all_supervisors_of_opening(): void
    {
        $opening = Opening::factory()->create();

        OpeningSupervisor::factory()->count(2)->create(['opening_id' => $opening->id]);
        OpeningSupervisor::factory()->inactive()->create(['opening_id' => $opening->id]);

        $this->assertCount(3, $opening->supervisors);
    }

    public function test_active_supervisor_returns_only_active_one(): void
    {
        $opening = Opening::factory()->create();

        OpeningSupervisor::factory()->inactive()->create(['opening_id' => $opening->id]);
        $active = OpeningSupervisor::factory()->create(['opening_id' => $opening->id]);

        $this->assertNotNull($opening->activeSupervisor);
        $this->assertEquals($active->id, $opening->activeSupervisor->id);
    }

    public function test_active_supervisor_returns_null_when_none_is_active(): void
    {
        $opening = Opening::factory()->create();

        OpeningSupervisor::factory()->inactive()->count(2)->create(['opening_id' => $opening->id]);

        $this->assertNull($opening->activeSupervisor);
    }

    public function test_active_supervisor_returns_latest_when_multiple_active(): void
    {
        $opening = Opening::factory()->create();

        OpeningSupervisor::factory()->create([
            'opening_id' => $opening->id,
            'assigned_at' => now()->subDays(5),
        ]);

        $latest = OpeningSupervisor::factory()->create([
            'opening_id' => $opening->id,
            'assigned_at' => now()->subDay(),
        ]);

        $this->assertEquals($latest->id, $opening->activeSupervisor->id);
    }

    public function test_deactivated_supervisor_not_returned_as_active(): void
    {
        $opening = Opening::factory()->create();

        OpeningSupervisor::factory()->inactive()->create(['opening_id' => $opening->id]);

        $this->assertNull($opening->activeSupervisor);
    }

    public function test_supervisor_assignments_returns_openings_where_user_is_supervisor(): void
    {
        $user = User::factory()->create();

        OpeningSupervisor::factory()->count(3)->create(['user_id' => $user->id]);
        OpeningSupervisor::factory()->create(); // de outro user

        $this->assertCount(3, $user->supervisorAssignments);
    }

    public function test_assigned_supervisors_returns_records_assigned_by_user(): void
    {
        $assigner = User::factory()->create();

        OpeningSupervisor::factory()->count(2)->create(['assigned_by' => $assigner->id]);
        OpeningSupervisor::factory()->create(); // atribuído por outro

        $this->assertCount(2, $assigner->assignedSupervisors);
    }
}
