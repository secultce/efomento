<?php

namespace Tests\Feature\User;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreValidationTest extends TestCase
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

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Maria Teste',
            'email' => 'maria.teste@example.com',
            'password' => 'senha123',
            'password_confirmation' => 'senha123',
            'role' => RoleEnum::FOMENTATION->value,
        ], $overrides);
    }

    public function test_name_is_required(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validPayload(['name' => '']))
            ->assertSessionHasErrors('name');
    }

    public function test_email_is_required(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validPayload(['email' => '']))
            ->assertSessionHasErrors('email');
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'maria.teste@example.com']);

        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validPayload())
            ->assertSessionHasErrors('email');
    }

    public function test_password_is_required(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validPayload(['password' => '', 'password_confirmation' => '']))
            ->assertSessionHasErrors('password');
    }

    public function test_password_confirmation_must_match(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validPayload(['password_confirmation' => 'outra-senha']))
            ->assertSessionHasErrors('password');
    }

    public function test_role_is_required(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validPayload(['role' => '']))
            ->assertSessionHasErrors('role');
    }

    public function test_role_must_be_a_valid_role(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validPayload(['role' => 'funcao-inexistente']))
            ->assertSessionHasErrors('role');
    }

    public function test_cpf_must_be_unique_when_provided(): void
    {
        User::factory()->create(['cpf' => '11122233344']);

        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validPayload(['cpf' => '11122233344']))
            ->assertSessionHasErrors('cpf');
    }

    public function test_registration_number_must_be_unique_when_provided(): void
    {
        User::factory()->create(['registration_number' => 'REG-001']);

        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validPayload(['registration_number' => 'REG-001']))
            ->assertSessionHasErrors('registration_number');
    }

    public function test_cpf_and_registration_number_are_optional(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validPayload())
            ->assertSessionHasNoErrors();
    }
}
