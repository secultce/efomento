<?php

namespace Tests\Feature\Auth;

use App\Mail\LoginCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LoginCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function startLogin(?User $user = null): string
    {
        $user ??= User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.show'));

        return Mail::queued(LoginCodeMail::class)->last()->code;
    }

    public function test_password_alone_does_not_allow_access_to_protected_pages(): void
    {
        $this->startLogin();
        $this->assertGuest();
        $this->get('/editais')->assertRedirect('/login');
        $this->get(route('two-factor.show'))->assertOk();
    }

    public function test_invalid_password_does_not_send_code(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        Mail::assertNothingOutgoing();
        $this->assertGuest();
    }

    public function test_code_is_hashed_and_wrong_code_does_not_authenticate(): void
    {
        $code = $this->startLogin();
        $challenge = Cache::get('login-code:'.session('login_code'));
        $this->assertStringNotContainsString($code, json_encode($challenge));
        $this->post(route('two-factor.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_code_expires(): void
    {
        $code = $this->startLogin();
        $this->travel(6)->minutes();
        $this->post(route('two-factor.verify'), ['code' => $code])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_code_cannot_be_replayed_even_with_old_pending_session(): void
    {
        $code = $this->startLogin();
        $token = session('login_code');
        $this->post(route('two-factor.verify'), ['code' => $code])->assertSessionHasNoErrors()->assertRedirect('/editais');
        $this->assertAuthenticated();
        $this->post('/logout');
        $this->withSession(['login_code' => $token])->post(route('two-factor.verify'), ['code' => $code])
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_verification_requires_password_challenge(): void
    {
        $this->get(route('two-factor.show'))->assertRedirect(route('login'));
        $this->post(route('two-factor.verify'), ['code' => '123456'])->assertRedirect(route('login'));
        $this->post(route('two-factor.resend'))->assertRedirect(route('login'));
        Mail::assertNothingOutgoing();
        $this->assertGuest();
    }

    public function test_direct_code_visit_explains_why_login_is_required(): void
    {
        $this->get(route('two-factor.show'))->assertRedirect(route('login'));

        $this->get(route('login'))->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Login')
            ->where('status', 'O código expirou ou a solicitação não é mais válida. Entre novamente.')
        );

        $this->startLogin();
        $this->get(route('two-factor.show'))->assertInertia(fn (Assert $page) => $page
            ->component('Auth/LoginCode')
        );
        $this->assertGuest();
    }

    public function test_direct_code_visit_after_expiration_clears_pending_login(): void
    {
        $this->startLogin();
        $this->travel(6)->minutes();

        $this->get(route('two-factor.show'))
            ->assertRedirect(route('login'))
            ->assertSessionMissing('login_code');
        $this->assertGuest();
    }

    public function test_resending_invalidates_previous_code_and_has_cooldown(): void
    {
        $this->startLogin();
        $oldToken = session('login_code');
        $this->post(route('two-factor.resend'))->assertSessionHasErrors('email');
        Mail::assertQueuedCount(1);
        $this->travel(61)->seconds();
        $this->post(route('two-factor.resend'))->assertRedirect(route('two-factor.show'));
        Mail::assertQueuedCount(2);
        $this->assertNull(Cache::get('login-code:'.$oldToken));
        $code = Mail::queued(LoginCodeMail::class)->last()->code;
        $this->post(route('two-factor.verify'), ['code' => $code])->assertRedirect('/editais');
        $this->assertAuthenticated();
    }

    public function test_five_failed_attempts_block_correct_code_even_after_resend(): void
    {
        $this->startLogin();
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('two-factor.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
        }
        $this->travel(61)->seconds();
        $this->post(route('two-factor.resend'))->assertSessionHasNoErrors()->assertRedirect(route('two-factor.show'));
        Mail::assertQueuedCount(2);
        $code = Mail::queued(LoginCodeMail::class)->last()->code;
        $this->post(route('two-factor.verify'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_cancel_invalidates_pending_code(): void
    {
        $code = $this->startLogin();
        $this->post(route('two-factor.cancel'))->assertRedirect(route('login'));
        $this->post(route('two-factor.verify'), ['code' => $code])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_password_change_invalidates_pending_code(): void
    {
        $user = User::factory()->create();
        $code = $this->startLogin($user);
        $user->update(['password' => 'new-password']);
        $this->post(route('two-factor.verify'), ['code' => $code])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_success_preserves_intended_destination(): void
    {
        $this->withSession(['url.intended' => url('/profile')]);
        $code = $this->startLogin();
        $this->post(route('two-factor.verify'), ['code' => $code])->assertRedirect('/profile');
        $this->assertAuthenticated();
    }

    public function test_mail_failure_does_not_authenticate_or_leave_pending_challenge(): void
    {
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('Mail unavailable'));
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email')->assertSessionMissing('login_code');
        $this->assertGuest();
    }

    public function test_old_remember_cookie_cannot_bypass_code(): void
    {
        $user = User::factory()->create(['remember_token' => 'existing-token']);
        $cookie = auth()->guard('web')->getRecallerName();
        $this->withCookie($cookie, $user->id.'|existing-token|'.$user->password)
            ->get('/editais')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_email_contains_code_and_expiration(): void
    {
        $mail = new LoginCodeMail('123456');
        $mail->assertSeeInHtml('123456');
        $mail->assertSeeInHtml('5 minutos');
    }

    public function test_challenge_expiring_before_locked_read_returns_to_login(): void
    {
        $code = $this->startLogin();
        $key = 'login-code:'.session('login_code');
        $challenge = Cache::get($key);
        $cache = \Mockery::mock(Cache::getFacadeRoot());
        $cache->shouldReceive('get')->with($key)->twice()->andReturn($challenge, null);
        Cache::swap($cache);

        $this->post(route('two-factor.verify'), ['code' => $code])
            ->assertRedirect(route('login'))->assertSessionMissing('login_code');
        $this->assertGuest();
    }

    public function test_code_expiring_during_hash_check_cannot_authenticate(): void
    {
        $code = $this->startLogin();
        Hash::partialMock()->shouldReceive('check')->once()->andReturnUsing(function () {
            $this->travel(5)->minutes();

            return true;
        });

        $this->post(route('two-factor.verify'), ['code' => $code, 'trust_device' => true])
            ->assertRedirect(route('login'))->assertCookieMissing('trusted_device');
        $this->assertGuest();
        $this->assertDatabaseCount('trusted_devices', 0);
    }

    public function test_incomplete_cached_challenge_fails_closed(): void
    {
        $code = $this->startLogin();
        Cache::put('login-code:'.session('login_code'), ['user_id' => 1], 300);

        $this->post(route('two-factor.verify'), ['code' => $code])
            ->assertRedirect(route('login'))->assertSessionMissing('login_code');
        $this->assertGuest();
    }

    public function test_busy_account_lock_preserves_challenge_for_retry(): void
    {
        $user = User::factory()->create();
        $code = $this->startLogin($user);
        $token = session('login_code');
        $lock = Cache::lock('login-code:lock:'.$user->id, 10);
        $this->assertTrue($lock->get());
        try {
            $this->post(route('two-factor.verify'), ['code' => $code])
                ->assertSessionHasErrors('code')->assertSessionHas('login_code', $token);
            $this->assertGuest();
        } finally {
            $lock->release();
        }

        $this->post(route('two-factor.verify'), ['code' => $code])->assertRedirect('/editais');
        $this->assertAuthenticatedAs($user);
    }

    public function test_email_change_invalidates_pending_code(): void
    {
        $user = User::factory()->create();
        $code = $this->startLogin($user);
        $user->update(['email' => 'changed@example.com']);
        $this->post(route('two-factor.verify'), ['code' => $code])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_validation_does_not_flash_code_or_passwords(): void
    {
        $this->startLogin();
        $this->post(route('two-factor.verify'), [
            'code' => '123456', 'trust_device' => 'invalid',
            'password' => 'secret', 'current_password' => 'secret', 'password_confirmation' => 'secret',
        ])->assertSessionHasErrors('trust_device');
        foreach (['code', 'password', 'current_password', 'password_confirmation'] as $field) {
            $this->assertArrayNotHasKey($field, session('_old_input', []));
        }
        $this->assertGuest();
    }

    public function test_old_remember_cookie_cannot_bypass_code_on_stateful_api(): void
    {
        $user = User::factory()->create(['remember_token' => 'existing-token']);
        $cookie = auth()->guard('web')->getRecallerName();
        $this->withCookie($cookie, $user->id.'|existing-token|'.$user->password)
            ->withHeader('Referer', config('app.url'))
            ->get('/api/documents')->assertRedirect(route('login'))->assertCookieExpired($cookie);
        $this->assertGuest('web');
    }
}
