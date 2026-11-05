<?php

namespace Tests\Feature;

use App\Jobs\SyncNoticesJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NoticeSyncAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('accessCases')]
    public function test_menu_permission_and_endpoint_require_local_super_admin(string $environment, ?string $role, bool $allowed): void
    {
        Queue::fake();
        $this->app->instance('env', $environment);
        $user = User::factory()->create();
        if ($role !== null) {
            Role::findOrCreate($role, 'web');
            $user->assignRole($role);
        }

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('auth.canSyncNotices', $allowed));

        $response = $this->from('/profile')->post(route('notices.sync'));

        if ($allowed) {
            $response->assertRedirect('/profile')->assertSessionHas('success');
            Queue::assertPushed(SyncNoticesJob::class, 1);
        } else {
            $response->assertForbidden();
            Queue::assertNothingPushed();
        }
    }

    public static function accessCases(): array
    {
        return [
            'local super-admin' => ['local', 'super_admin', true],
            'local fomentation' => ['local', 'fomentation', false],
            'local coordinator' => ['local', 'coord_fomentation', false],
            'local without role' => ['local', null, false],
            'production super-admin' => ['production', 'super_admin', false],
            'staging super-admin' => ['staging', 'super_admin', false],
            'testing super-admin' => ['testing', 'super_admin', false],
        ];
    }

    public function test_guest_cannot_start_sync(): void
    {
        Queue::fake();
        $this->app->instance('env', 'local');

        $this->post(route('notices.sync'))->assertRedirect(route('login'));

        Queue::assertNothingPushed();
    }
}
