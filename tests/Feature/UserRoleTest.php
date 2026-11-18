<?php

namespace Tests\Feature;

use App\Enums\Role as RoleEnum;
use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (RoleEnum::values() as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create();
        $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    }

    public function test_assign_role_to_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('users.assign-role', ['user' => $user->id, 'role' => RoleEnum::FOMENTATION->value]));

        $response->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->hasRole(RoleEnum::FOMENTATION->value));
    }

    public function test_assign_invalid_role_returns_validation_error(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('users.assign-role', ['user' => $user->id, 'role' => 'funcao-inexistente']));

        $response->assertRedirect()->assertSessionHasErrors('role');
        $this->assertCount(0, $user->fresh()->roles);
    }

    public function test_remove_role_from_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::FOMENTATION->value);

        $response = $this->actingAs($this->admin)
            ->delete(route('users.remove-role', ['user' => $user->id, 'role' => RoleEnum::FOMENTATION->value]));

        $response->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->hasRole(RoleEnum::FOMENTATION->value));
    }

    public function test_remove_invalid_role_returns_validation_error(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('users.remove-role', ['user' => $user->id, 'role' => 'funcao-inexistente']));

        $response->assertRedirect()->assertSessionHasErrors('role');
    }

    public function test_user_without_super_admin_cannot_assign_role(): void
    {
        $notAdmin = User::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($notAdmin)
            ->post(route('users.assign-role', ['user' => $user->id, 'role' => RoleEnum::FOMENTATION->value]));

        $response->assertForbidden();
        $this->assertCount(0, $user->fresh()->roles);
    }

    public function test_user_without_super_admin_cannot_remove_role(): void
    {
        $notAdmin = User::factory()->create();
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::FOMENTATION->value);

        $response = $this->actingAs($notAdmin)
            ->delete(route('users.remove-role', ['user' => $user->id, 'role' => RoleEnum::FOMENTATION->value]));

        $response->assertForbidden();
        $this->assertTrue($user->fresh()->hasRole(RoleEnum::FOMENTATION->value));
    }

    public function test_super_admin_can_access_groups_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('groups.index'));

        $response->assertOk();
    }

    public function test_user_without_super_admin_cannot_access_groups_page(): void
    {
        $notAdmin = User::factory()->create();

        $response = $this->actingAs($notAdmin)->get(route('groups.index'));

        $response->assertForbidden();
    }

    public function test_assign_role_to_missing_user_returns_404(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('users.assign-role', ['user' => 999999, 'role' => RoleEnum::FOMENTATION->value]));

        $response->assertNotFound();
    }

    public function test_groups_index_does_not_n_plus_one_query_avatar_files(): void
    {
        $users = User::factory()->count(5)->create();

        foreach ($users as $user) {
            File::factory()->create([
                'object_type' => 'user',
                'object_id' => $user->id,
                'grp' => 'avatar',
            ]);
        }

        DB::enableQueryLog();

        $response = $this->actingAs($this->admin)->get(route('groups.index'));

        $filesQueries = collect(DB::getQueryLog())
            ->filter(fn ($query) => str_contains($query['query'], '"files"') || str_contains($query['query'], '`files`'))
            ->count();

        DB::disableQueryLog();

        $response->assertOk();
        $this->assertSame(1, $filesQueries, 'A listagem deve buscar os avatares em uma única consulta, sem N+1.');
    }
}
