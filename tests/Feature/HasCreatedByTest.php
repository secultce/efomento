<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Notice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasCreatedByTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_relationship_returns_correct_user(): void
    {
        $user = User::factory()->create();
        $budget = Budget::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $budget->creator);
        $this->assertEquals($user->id, $budget->creator->id);
    }

    public function test_created_by_is_filled_automatically_when_authenticated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budget = Budget::factory()->create(['created_by' => null]);

        $this->assertEquals($user->id, $budget->created_by);
    }

    public function test_created_by_is_null_when_not_authenticated(): void
    {
        // Notice tem created_by nullable — verifica que a trait não preenche sem auth
        $notice = Notice::factory()->create(['created_by' => null]);

        $this->assertNull($notice->created_by);
    }

    public function test_created_by_is_not_overridden_when_already_set(): void
    {
        $authUser = User::factory()->create();
        $explicitUser = User::factory()->create();
        $this->actingAs($authUser);

        $budget = Budget::factory()->create(['created_by' => $explicitUser->id]);

        $this->assertEquals($explicitUser->id, $budget->created_by);
    }

    public function test_trait_works_across_different_models(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $project = Project::factory()->create(['created_by' => null]);

        $this->assertEquals($user->id, $project->created_by);
        $this->assertInstanceOf(User::class, $project->creator);
    }
}
