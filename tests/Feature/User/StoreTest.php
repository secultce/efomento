<?php

namespace Tests\Feature\User;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreTest extends TestCase
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

    public function test_user_is_created_successfully(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Maria Teste',
            'email' => 'maria.teste@example.com',
            'password' => 'senha123',
            'password_confirmation' => 'senha123',
            'role' => RoleEnum::FOMENTATION->value,
            'cpf' => '11122233344',
            'registration_number' => 'REG-001',
        ]);

        $response->assertRedirect(route('groups.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'Maria Teste',
            'email' => 'maria.teste@example.com',
            'cpf' => '11122233344',
            'registration_number' => 'REG-001',
        ]);
    }

    public function test_role_is_assigned_to_created_user(): void
    {
        $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Maria Teste',
            'email' => 'maria.teste@example.com',
            'password' => 'senha123',
            'password_confirmation' => 'senha123',
            'role' => RoleEnum::FOMENTATION->value,
        ]);

        $user = User::where('email', 'maria.teste@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole(RoleEnum::FOMENTATION->value));
    }

    public function test_password_is_hashed(): void
    {
        $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Maria Teste',
            'email' => 'maria.teste@example.com',
            'password' => 'senha123',
            'password_confirmation' => 'senha123',
            'role' => RoleEnum::FOMENTATION->value,
        ]);

        $user = User::where('email', 'maria.teste@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('senha123', $user->password));
    }
}
