<?php

namespace Tests\Feature\Auth;

use App\Mail\LoginCodeMail;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function startLogin(User $user): string
    {
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/two-factor-challenge');
        $this->assertGuest();

        return Mail::queued(LoginCodeMail::class)->last()->code;
    }

    private function trustCookie(User $user): string
    {
        $code = $this->startLogin($user);
        $response = $this->post(route('two-factor.verify'), ['code' => $code, 'trust_device' => true])
            ->assertRedirect('/editais')
            ->assertCookie('trusted_device');
        $cookie = $response->getCookie('trusted_device')->getValue();
        $this->post('/logout');
        Mail::fake();

        return $cookie;
    }

    public function test_unknown_device_queues_code_on_high_and_exposes_challenge_settings(): void
    {
        $user = User::factory()->create();
        $this->startLogin($user);
        Mail::assertQueued(LoginCodeMail::class, fn ($mail) => $mail->hasTo($user->email)
            && $mail->queue === 'high' && $mail->ttlMinutes === 5);

        $this->get('/two-factor-challenge')->assertInertia(fn (Assert $page) => $page
            ->component('Auth/LoginCode')
            ->where('codeLength', 6)
            ->where('codeTtlMinutes', 5)
            ->where('trustedDeviceDays', 30)
            ->where('resendAvailableAt', now()->addSeconds(60)->timestamp)
        );
    }

    public function test_verification_without_checkbox_does_not_trust_device(): void
    {
        $code = $this->startLogin(User::factory()->create());
        $this->post(route('two-factor.verify'), ['code' => $code])
            ->assertRedirect('/editais')->assertCookieMissing('trusted_device');
        $this->assertDatabaseCount('trusted_devices', 0);
        $this->assertAuthenticated();
    }

    public function test_checkbox_creates_hashed_token_and_protected_cookie_for_thirty_days(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $code = $this->startLogin($user);
        $response = $this->post(route('two-factor.verify'), ['code' => $code, 'trust_device' => true]);
        $cookie = $response->getCookie('trusted_device');
        [$selector, $validator] = explode('|', $cookie->getValue());
        $device = $user->trustedDevices()->sole();

        $this->assertSame($selector, $device->selector);
        $this->assertSame(hash('sha256', $validator), $device->token_hash);
        $this->assertNotSame($validator, $device->token_hash);
        $this->assertSame(now()->addDays(30)->timestamp, $device->expires_at->timestamp);
        $this->assertSame(now()->addDays(30)->timestamp, $cookie->getExpiresTime());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertNotSame($cookie->getValue(), $response->getCookie('trusted_device', false)->getValue());
    }

    public function test_cookie_is_secure_in_production(): void
    {
        $code = $this->startLogin(User::factory()->create());
        $this->app->instance('env', 'production');
        $response = $this->post(route('two-factor.verify'), ['code' => $code, 'trust_device' => true]);
        $this->assertTrue($response->getCookie('trusted_device')->isSecure());
    }

    public function test_trusted_browser_skips_code_after_password_and_keeps_intended_destination(): void
    {
        $user = User::factory()->create();
        $cookie = $this->trustCookie($user);
        $expiresAt = $user->trustedDevices()->sole()->expires_at;
        $this->travel(1)->days();

        $this->withCookie('trusted_device', $cookie)->withSession(['url.intended' => '/profile'])
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/profile');
        $this->assertAuthenticatedAs($user);
        Mail::assertNothingOutgoing();
        $device = $user->trustedDevices()->sole();
        $this->assertTrue($device->expires_at->equalTo($expiresAt));
        $this->assertTrue($device->last_used_at->isToday());
    }

    public function test_trusted_cookie_still_requires_correct_password(): void
    {
        $user = User::factory()->create();
        $cookie = $this->trustCookie($user);
        $this->withCookie('trusted_device', $cookie)
            ->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        Mail::assertNothingOutgoing();
    }

    public function test_expired_cookie_requires_code_again(): void
    {
        $user = User::factory()->create();
        $cookie = $this->trustCookie($user);
        $this->travel(30)->days();
        $this->withCookie('trusted_device', $cookie);
        $this->startLogin($user);
    }

    public function test_cookie_from_another_account_cannot_skip_code(): void
    {
        $cookie = $this->trustCookie(User::factory()->create());
        $this->withCookie('trusted_device', $cookie);
        $this->startLogin(User::factory()->create());
    }

    public function test_modified_validator_cannot_skip_code(): void
    {
        $user = User::factory()->create();
        $cookie = $this->trustCookie($user);
        $this->travel(61)->seconds();
        $this->withCookie('trusted_device', explode('|', $cookie)[0].'|'.Str::random(64));
        $this->startLogin($user);
    }

    public function test_malformed_cookie_falls_back_to_challenge(): void
    {
        $this->withCookie('trusted_device', 'invalid');
        $this->startLogin(User::factory()->create());
    }

    public function test_invalid_code_never_creates_trusted_device(): void
    {
        $this->startLogin(User::factory()->create());
        $this->post(route('two-factor.verify'), ['code' => '000000', 'trust_device' => true])
            ->assertSessionHasErrors('code')->assertCookieMissing('trusted_device');
        $this->assertDatabaseCount('trusted_devices', 0);
        $this->assertGuest();
    }

    public function test_password_update_revokes_all_devices_and_old_cookie_requires_code(): void
    {
        $user = User::factory()->create();
        $cookie = $this->trustCookie($user);
        $this->travel(61)->seconds();
        $this->trustCookie($user);
        $this->assertDatabaseCount('trusted_devices', 2);

        $this->actingAs($user)->put('/password', [
            'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertSessionHasNoErrors()->assertCookieExpired('trusted_device');
        $this->assertDatabaseCount('trusted_devices', 0);
        $this->post('/logout');
        $this->travel(61)->seconds();
        $this->withCookie('trusted_device', $cookie)
            ->post('/login', ['email' => $user->email, 'password' => 'new-password'])
            ->assertRedirect('/two-factor-challenge');
        $this->assertGuest();
    }

    public function test_password_reset_revokes_trusted_devices(): void
    {
        $user = User::factory()->create();
        $this->trustCookie($user);
        $token = Password::createToken($user);
        $this->post('/reset-password', [
            'token' => $token, 'email' => $user->email,
            'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertSessionHasNoErrors()->assertRedirect('/login');
        $this->assertDatabaseCount('trusted_devices', 0);
    }

    public function test_legacy_code_url_redirects_to_challenge(): void
    {
        $this->get('/login/code')->assertRedirect('/two-factor-challenge');
        $this->get('/two-factor-challenge')->assertRedirect('/login');
    }

    public function test_revoked_cookie_requires_code_again(): void
    {
        $user = User::factory()->create();
        $cookie = $this->trustCookie($user);
        TrustedDevice::query()->delete();
        $this->travel(61)->seconds();
        $this->withCookie('trusted_device', $cookie);
        $this->startLogin($user);
    }
}
