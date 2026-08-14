<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class BudgetOpinionDocumentTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('budgetOpinionTypes')]
    public function test_budget_user_can_create_budget_opinion_linked_to_budget_phase(string $type): void
    {
        $user = $this->userWithRole(Role::BUDGETARY);
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->post(route('projects.create-document'), [
                'type' => $type,
                'selected_projects' => [$project->id],
                'content' => 'Conteúdo do parecer orçamentário.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('documents', [
            'project_id' => $project->id,
            'notice_id' => $project->notice_id,
            'type' => $type,
            'phase' => 'budget',
            'created_by' => $user->id,
        ]);
    }

    #[DataProvider('budgetRoles')]
    public function test_each_budget_role_can_create_initial_opinion(string $role): void
    {
        $user = $this->userWithRole(Role::from($role));
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->post(route('projects.create-document'), [
                'type' => 'pi',
                'selected_projects' => [$project->id],
                'content' => 'Conteúdo do PI.',
            ])
            ->assertSessionHasNoErrors();
    }

    #[DataProvider('budgetOpinionTypes')]
    public function test_user_without_budget_role_cannot_create_budget_opinion(string $type): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->post(route('projects.create-document'), [
                'type' => $type,
                'selected_projects' => [$project->id],
                'content' => 'Conteúdo sem permissão.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('documents', [
            'project_id' => $project->id,
            'type' => $type,
        ]);
    }

    public function test_user_without_budget_role_cannot_create_budget_opinion_through_api(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/documents', [
                'type' => 'pi',
                'phase' => 'budget',
                'notice_id' => $project->notice_id,
                'project_id' => $project->id,
                'body' => 'Conteúdo sem permissão.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('documents', [
            'project_id' => $project->id,
            'type' => 'pi',
        ]);
    }

    public static function budgetOpinionTypes(): array
    {
        return [
            'PI' => ['pi'],
            'PF' => ['pf'],
        ];
    }

    public static function budgetRoles(): array
    {
        return [
            'budgetary' => [Role::BUDGETARY->value],
            'coord_budgetary' => [Role::COORD_BUDGETARY->value],
            'super_admin' => [Role::SUPER_ADMIN->value],
        ];
    }

    private function userWithRole(Role $role): User
    {
        SpatieRole::firstOrCreate([
            'name' => $role->value,
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }
}
