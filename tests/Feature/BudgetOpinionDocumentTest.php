<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Document;
use App\Models\Notice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class BudgetOpinionDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_user_can_create_initial_opinion_for_the_notice_without_a_project(): void
    {
        $user = $this->userWithRole(Role::BUDGETARY);
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->post(route('projects.create-document'), [
                'type' => 'pi',
                'notice_id' => $project->notice_id,
                'content' => 'Conteúdo do parecer orçamentário.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('documents', [
            'project_id' => null,
            'notice_id' => $project->notice_id,
            'type' => 'pi',
            'phase' => 'budget',
            'created_by' => $user->id,
        ]);
    }

    public function test_budget_user_can_create_final_opinion_for_selected_projects(): void
    {
        $user = $this->userWithRole(Role::BUDGETARY);
        $project = Project::factory()->create();

        $this->actingAs($user)
            ->post(route('projects.create-document'), [
                'type' => 'pf',
                'selected_projects' => [$project->id],
                'content' => 'Conteúdo do parecer orçamentário.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('documents', [
            'project_id' => $project->id,
            'notice_id' => $project->notice_id,
            'type' => 'pf',
            'phase' => 'budget',
            'created_by' => $user->id,
        ]);
    }

    public function test_budget_user_can_create_initial_opinion_without_a_project_through_api(): void
    {
        $user = $this->userWithRole(Role::BUDGETARY);
        $notice = Notice::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/documents', [
                'type' => 'pi',
                'phase' => 'budget',
                'notice_id' => $notice->id,
                'body' => 'Conteúdo do parecer orçamentário.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.project_id', null);

        $this->assertDatabaseHas('documents', [
            'notice_id' => $notice->id,
            'project_id' => null,
            'type' => 'pi',
            'phase' => 'budget',
        ]);
    }

    public function test_api_rejects_a_duplicate_notice_level_initial_opinion(): void
    {
        $user = $this->userWithRole(Role::BUDGETARY);
        $notice = Notice::factory()->create();
        $payload = [
            'type' => 'pi',
            'phase' => 'budget',
            'notice_id' => $notice->id,
            'body' => 'Conteúdo do parecer orçamentário.',
        ];

        $this->actingAs($user)->postJson('/api/documents', $payload)->assertCreated();

        $this->actingAs($user)
            ->postJson('/api/documents', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('notice_id');

        $this->assertDatabaseCount('documents', 1);
    }

    public function test_guest_cannot_delete_a_budget_opinion(): void
    {
        $document = Document::factory()->create([
            'project_id' => null,
            'type' => 'pi',
            'phase' => 'budget',
        ]);

        $this->deleteJson("/api/documents/{$document->id}")->assertForbidden();

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'deleted_at' => null,
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
                'notice_id' => $project->notice_id,
                'content' => 'Conteúdo do PI.',
            ])
            ->assertRedirect()
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
                ...($type === 'pi'
                    ? ['notice_id' => $project->notice_id]
                    : ['selected_projects' => [$project->id]]),
                'content' => 'Conteúdo sem permissão.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('documents', [
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
