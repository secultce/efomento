<?php

namespace Tests\Feature\Supervisor;

use App\Models\Opening;
use App\Models\OpeningSupervisor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpeningSupervisorModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_active_is_cast_to_boolean(): void
    {
        $supervisor = OpeningSupervisor::factory()->create(['is_active' => true]);

        $this->assertIsBool($supervisor->is_active);
        $this->assertTrue($supervisor->is_active);
    }

    public function test_assigned_at_is_cast_to_carbon(): void
    {
        $supervisor = OpeningSupervisor::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $supervisor->assigned_at);
    }

    public function test_removed_at_is_nullable_and_cast_to_carbon(): void
    {
        $active   = OpeningSupervisor::factory()->create(['removed_at' => null]);
        $inactive = OpeningSupervisor::factory()->inactive()->create();

        $this->assertNull($active->removed_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $inactive->removed_at);
    }

    public function test_assigned_by_is_nullable(): void
    {
        $supervisor = OpeningSupervisor::factory()->create(['assigned_by' => null]);

        $this->assertNull($supervisor->assigned_by);
        $this->assertNull($supervisor->assignedBy);
    }

    public function test_opening_relationship_returns_opening_instance(): void
    {
        $opening    = Opening::factory()->create();
        $supervisor = OpeningSupervisor::factory()->create(['opening_id' => $opening->id]);

        $this->assertInstanceOf(Opening::class, $supervisor->opening);
        $this->assertEquals($opening->id, $supervisor->opening->id);
    }

    public function test_user_relationship_returns_the_supervisor_user(): void
    {
        $user       = User::factory()->create();
        $supervisor = OpeningSupervisor::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $supervisor->user);
        $this->assertEquals($user->id, $supervisor->user->id);
    }

    public function test_assigned_by_relationship_returns_assigner_user(): void
    {
        $assigner   = User::factory()->create();
        $supervisor = OpeningSupervisor::factory()->create(['assigned_by' => $assigner->id]);

        $this->assertInstanceOf(User::class, $supervisor->assignedBy);
        $this->assertEquals($assigner->id, $supervisor->assignedBy->id);
    }

    public function test_cascade_delete_removes_supervisor_when_opening_is_deleted(): void
    {
        $opening    = Opening::factory()->create();
        $supervisor = OpeningSupervisor::factory()->create(['opening_id' => $opening->id]);

        $opening->forceDelete();

        $this->assertDatabaseMissing('opening_supervisor', ['id' => $supervisor->id]);
    }
}
