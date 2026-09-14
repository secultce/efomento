<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\TrustedDeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TrustedDeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    private function device(User $user)
    {
        return $user->trustedDevices()->create([
            'selector' => Str::random(40), 'token_hash' => hash('sha256', Str::random(64)),
            'user_agent' => 'Firefox/120.0', 'ip_address' => '192.0.2.1',
            'last_used_at' => now()->subDay(), 'expires_at' => now()->addDays(30),
        ]);
    }

    public function test_profile_only_exposes_owned_devices_without_secrets(): void
    {
        $user = User::factory()->create();
        $device = $this->device($user);
        $this->device(User::factory()->create());
        $this->actingAs($user)->get('/profile')->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Edit')->has('trustedDevices', 1)
            ->where('trustedDevices.0.id', $device->id)
            ->where('trustedDevices.0.ip_address', '192.0.2.1')
            ->missing('trustedDevices.0.selector')->missing('trustedDevices.0.token_hash')
        );
        $this->assertTrue($device->fresh()->last_used_at->equalTo($device->last_used_at));
    }

    public function test_user_can_remove_one_device_with_audit(): void
    {
        $user = User::factory()->create();
        $device = $this->device($user);
        $other = $this->device($user);
        $this->actingAs($user)->delete(route('profile.trusted-devices.destroy', $device->id))->assertRedirect('/profile');
        $this->assertModelMissing($device);
        $this->assertModelExists($other);
        $audit = $user->audits()->where('event', 'trusted_device_revoked')->sole();
        $this->assertSame(['trusted_device_id' => $device->id], $audit->old_values);
        $this->assertStringNotContainsString($device->selector, $audit->toJson());
        $this->assertStringNotContainsString($device->token_hash, $audit->toJson());
    }

    public function test_user_cannot_remove_another_accounts_device(): void
    {
        $device = $this->device(User::factory()->create());
        $this->actingAs(User::factory()->create())->delete(route('profile.trusted-devices.destroy', $device->id))->assertNotFound();
        $this->assertModelExists($device);
    }

    public function test_remove_all_only_revokes_own_devices_and_preserves_session(): void
    {
        $user = User::factory()->create();
        $this->device($user);
        $this->device($user);
        $other = $this->device(User::factory()->create());
        $this->actingAs($user)->delete(route('profile.trusted-devices.destroy-all'))
            ->assertRedirect('/profile')->assertCookieExpired('trusted_device');
        $this->assertSame(0, $user->trustedDevices()->count());
        $this->assertModelExists($other);
        $this->assertAuthenticatedAs($user);
        $this->assertSame(2, $user->audits()->where('event', 'trusted_devices_revoked')->sole()->old_values['trusted_devices_count']);
    }

    public function test_current_device_is_identified_without_recording_a_login_and_cookie_is_removed(): void
    {
        $user = User::factory()->create();
        app(TrustedDeviceService::class)->trustDevice(request(), $user);
        $cookie = Cookie::queued('trusted_device')->getValue();
        Cookie::unqueue('trusted_device');
        $device = $user->trustedDevices()->sole();
        $this->travel(1)->hours();
        $this->actingAs($user)->withCookie('trusted_device', $cookie)->get('/profile')
            ->assertInertia(fn (Assert $page) => $page->where('trustedDevices.0.is_current', true));
        $this->assertTrue($device->fresh()->last_used_at->equalTo($device->last_used_at));
        $this->delete(route('profile.trusted-devices.destroy', $device->id))->assertCookieExpired('trusted_device');
        $this->assertModelMissing($device);
        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_cannot_manage_devices(): void
    {
        $device = $this->device(User::factory()->create());
        $this->delete(route('profile.trusted-devices.destroy', $device->id))->assertRedirect('/login');
        $this->delete(route('profile.trusted-devices.destroy-all'))->assertRedirect('/login');
        $this->assertModelExists($device);
    }
}
