<?php

namespace Tests\Feature\Supervisor;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSupervisorFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cpf_and_registration_number_are_saved(): void
    {
        $user = User::factory()->create([
            'cpf'                 => '123.456.789-00',
            'registration_number' => 'MAT-2024-001',
        ]);

        $this->assertDatabaseHas('users', [
            'id'                  => $user->id,
            'cpf'                 => '123.456.789-00',
            'registration_number' => 'MAT-2024-001',
        ]);
    }

    public function test_cpf_and_registration_number_are_nullable(): void
    {
        $user = User::factory()->create([
            'cpf'                 => null,
            'registration_number' => null,
        ]);

        $this->assertNull($user->cpf);
        $this->assertNull($user->registration_number);
    }

    public function test_cpf_must_be_unique(): void
    {
        $this->expectException(UniqueConstraintViolationException::class);

        User::factory()->create(['cpf' => '111.111.111-11']);
        User::factory()->create(['cpf' => '111.111.111-11']);
    }

    public function test_registration_number_must_be_unique(): void
    {
        $this->expectException(UniqueConstraintViolationException::class);

        User::factory()->create(['registration_number' => 'MAT-DUPLICADA']);
        User::factory()->create(['registration_number' => 'MAT-DUPLICADA']);
    }
}
