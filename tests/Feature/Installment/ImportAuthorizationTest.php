<?php

namespace Tests\Feature\Installment;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'financial', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'coord_financial', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'fomentation', 'guard_name' => 'web']);
    }

    public function test_user_without_payment_role_cannot_import_installments(): void
    {
        $user = User::factory()->create();
        $user->assignRole('fomentation');

        $this->actingAs($user)
            ->post(route('installments.import'), [
                'file' => UploadedFile::fake()->create('planilha.xlsx', 100),
            ])
            ->assertForbidden();
    }

    public function test_user_without_any_role_cannot_import_installments(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('installments.import'), [
                'file' => UploadedFile::fake()->create('planilha.xlsx', 100),
            ])
            ->assertForbidden();
    }

    public function test_financial_role_can_import_installments(): void
    {
        $user = User::factory()->create();
        $user->assignRole('financial');

        $this->actingAs($user)
            ->post(route('installments.import'), [
                'file' => UploadedFile::fake()->create('planilha.xlsx', 100),
            ])
            ->assertRedirect();
    }

    public function test_coord_financial_role_can_import_installments(): void
    {
        $user = User::factory()->create();
        $user->assignRole('coord_financial');

        $this->actingAs($user)
            ->post(route('installments.import'), [
                'file' => UploadedFile::fake()->create('planilha.xlsx', 100),
            ])
            ->assertRedirect();
    }

    public function test_super_admin_role_can_import_installments(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->post(route('installments.import'), [
                'file' => UploadedFile::fake()->create('planilha.xlsx', 100),
            ])
            ->assertRedirect();
    }
}
