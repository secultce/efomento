<?php

namespace Tests\Feature\Auth;

use App\Mail\LoginCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('two-factor.show'));
        Mail::assertQueued(LoginCodeMail::class, fn ($mail) => $mail->hasTo($user->email));
        $code = Mail::queued(LoginCodeMail::class)->first()->code;
        $this->post(route('two-factor.verify'), ['code' => $code])
            ->assertRedirect(route('notices.index', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors([
            'email' => 'As credenciais indicadas não coincidem com as registradas no sistema.',
        ]);

        $this->assertGuest();
    }

    public function test_unknown_email_returns_a_portuguese_login_error(): void
    {
        $this->post('/login', [
            'email' => 'unknown@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors([
            'email' => 'As credenciais indicadas não coincidem com as registradas no sistema.',
        ]);

        $this->assertGuest();
    }

    public function test_login_required_fields_are_named_in_portuguese(): void
    {
        $this->post('/login', [])->assertSessionHasErrors([
            'email' => 'É obrigatória a indicação de um valor para o campo e-mail.',
            'password' => 'É obrigatória a indicação de um valor para o campo senha.',
        ]);
    }

    public function test_login_rate_limit_error_is_in_portuguese(): void
    {
        $this->freezeTime();
        $key = 'limited@example.com|127.0.0.1';

        RateLimiter::increment($key, decaySeconds: 60, amount: 5);

        $this->post('/login', [
            'email' => 'limited@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors([
            'email' => 'O número limite de tentativas de login foi atingido. Por favor, tente novamente dentro de 60 segundos.',
        ]);

        RateLimiter::clear($key);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
