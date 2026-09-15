<?php

namespace App\Services;

use App\Exceptions\Domain\ExpiredTwoFactorCodeException;
use App\Exceptions\Domain\InvalidTwoFactorCodeException;
use App\Mail\LoginCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class LoginCodeService
{
    public function send(Request $request, User $user): void
    {
        $key = 'login-code:send:'.$user->id;
        $cooldown = config('two_factor.resend_throttle_seconds');
        if (! Cache::add($key, true, $cooldown)) {
            throw ValidationException::withMessages(['email' => "Aguarde {$cooldown} segundos antes de solicitar outro código."]);
        }

        $this->cancel($request);
        $token = Str::random(64);
        $length = config('two_factor.code_length');
        $code = (string) random_int(10 ** ($length - 1), (10 ** $length) - 1);
        $challenge = [
            'user_id' => $user->id,
            'hash' => Hash::make($code),
            'credentials' => $this->fingerprint($user),
            'expires_at' => now()->addMinutes(config('two_factor.code_ttl_minutes'))->timestamp,
        ];

        try {
            Mail::to($user->email)->queue(new LoginCodeMail($code));
        } catch (Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['email' => 'Não foi possível enviar o código. Aguarde um minuto e tente entrar novamente.']);
        }

        Cache::put('login-code:'.$token, $challenge, now()->addMinutes(config('two_factor.code_ttl_minutes')));
        $request->session()->put('login_code', $token);
        $request->session()->put('login_code_resend_at', now()->addSeconds($cooldown)->timestamp);
    }

    public function pendingUser(Request $request): ?User
    {
        $token = $request->session()->get('login_code');
        $challenge = $token ? Cache::get('login-code:'.$token) : null;

        return $this->challengeUser($challenge);
    }

    public function verify(Request $request, string $code): User
    {
        $token = $request->session()->get('login_code');
        if (! $token || ! ($pendingUser = $this->pendingUser($request))) {
            throw new ExpiredTwoFactorCodeException;
        }

        return Cache::lock('login-code:lock:'.$pendingUser->id, 10)->get(function () use ($request, $token, $code) {
            // Read one snapshot under the lock; the cache can expire between reads.
            $challenge = Cache::get('login-code:'.$token);
            $user = $this->challengeUser($challenge);
            if (! $user) {
                throw new ExpiredTwoFactorCodeException;
            }

            $attempts = 'login-code:attempts:'.$user->id;
            if (RateLimiter::tooManyAttempts($attempts, 5)) {
                throw ValidationException::withMessages(['code' => 'Limite de tentativas atingido. Aguarde 10 minutos para tentar novamente.']);
            }

            RateLimiter::hit($attempts, 600);
            if (! Hash::check($code, $challenge['hash'])) {
                throw new InvalidTwoFactorCodeException;
            }

            if ($challenge['expires_at'] <= now()->timestamp) {
                throw new ExpiredTwoFactorCodeException;
            }

            $this->cancel($request);
            RateLimiter::clear($attempts);

            return $user;
        }) ?: throw ValidationException::withMessages(['code' => 'Uma verificação está em andamento. Tente novamente em instantes.']);
    }

    public function cancel(Request $request): void
    {
        $token = $request->session()->pull('login_code');
        $request->session()->forget('login_code_resend_at');
        if ($token) {
            Cache::forget('login-code:'.$token);
        }
    }

    private function challengeUser(mixed $challenge): ?User
    {
        if (! is_array($challenge)
            || ! isset($challenge['user_id'], $challenge['hash'], $challenge['credentials'], $challenge['expires_at'])
            || ! is_int($challenge['user_id'])
            || ! is_string($challenge['hash']) || ! is_string($challenge['credentials'])
            || ! is_int($challenge['expires_at']) || $challenge['expires_at'] <= now()->timestamp) {
            return null;
        }

        $user = User::find($challenge['user_id']);

        return $user && hash_equals($challenge['credentials'], $this->fingerprint($user)) ? $user : null;
    }

    private function fingerprint(User $user): string
    {
        return hash_hmac('sha256', $user->email.'|'.$user->getAuthPassword(), config('app.key'));
    }
}
