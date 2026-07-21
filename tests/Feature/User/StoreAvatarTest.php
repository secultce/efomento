<?php

namespace Tests\Feature\User;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreAvatarTest extends TestCase
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

        Storage::fake(config('efomento.file_disk', 'public'));
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

    public function test_avatar_is_stored_when_photo_is_provided(): void
    {
        $photo = UploadedFile::fake()->image('avatar.jpg');

        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validPayload(['photo' => $photo]))
            ->assertSessionHasNoErrors();

        $user = User::where('email', 'maria.teste@example.com')->firstOrFail();

        $this->assertDatabaseHas('files', [
            'object_type' => 'user',
            'object_id' => $user->id,
            'grp' => 'avatar',
        ]);

        Storage::disk(config('efomento.file_disk', 'public'))
            ->assertExists("user/{$user->id}/avatar.jpg");
    }

    public function test_user_is_created_without_photo(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validPayload())
            ->assertSessionHasNoErrors();

        $user = User::where('email', 'maria.teste@example.com')->firstOrFail();

        $this->assertDatabaseMissing('files', [
            'object_type' => 'user',
            'object_id' => $user->id,
        ]);
    }

    public function test_photo_must_be_an_image(): void
    {
        $document = UploadedFile::fake()->create('doc.pdf', 100);

        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validPayload(['photo' => $document]))
            ->assertSessionHasErrors('photo');
    }

    public function test_photo_exceeding_max_size_is_rejected(): void
    {
        $bigPhoto = UploadedFile::fake()->image('big.jpg')->size(6000);

        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->validPayload(['photo' => $bigPhoto]))
            ->assertSessionHasErrors('photo');
    }
}
