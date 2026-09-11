<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\Domain\ExpiredTwoFactorCodeException;
use App\Exceptions\Domain\InvalidTwoFactorCodeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyTwoFactorCodeRequest;
use App\Services\LoginCodeService;
use App\Services\TrustedDeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginCodeController extends Controller
{
    public function show(Request $request, LoginCodeService $codes): Response|RedirectResponse
    {
        if (! $codes->pendingUser($request)) {
            return $this->expired($request, $codes);
        }

        return Inertia::render('Auth/LoginCode', [
            'status' => session('status'),
            'codeTtlMinutes' => config('two_factor.code_ttl_minutes'),
            'codeLength' => config('two_factor.code_length'),
            'trustedDeviceDays' => config('two_factor.trusted_device_days'),
            'resendAvailableAt' => $request->session()->get('login_code_resend_at'),
        ]);
    }

    public function verify(VerifyTwoFactorCodeRequest $request, LoginCodeService $codes, TrustedDeviceService $devices): RedirectResponse
    {
        try {
            $user = $codes->verify($request, $request->string('code')->toString());
        } catch (InvalidTwoFactorCodeException $e) {
            throw ValidationException::withMessages(['code' => $e->getMessage()]);
        } catch (ExpiredTwoFactorCodeException) {
            return $this->expired($request, $codes);
        }

        if ($request->boolean('trust_device')) {
            $devices->trustDevice($request, $user);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('notices.index', absolute: false));
    }

    public function resend(Request $request, LoginCodeService $codes): RedirectResponse
    {
        $user = $codes->pendingUser($request);
        if (! $user) {
            return $this->expired($request, $codes);
        }

        $codes->send($request, $user);

        return redirect()->route('two-factor.show')->with('status', 'Um novo código foi enviado ao seu email.');
    }

    public function cancel(Request $request, LoginCodeService $codes): RedirectResponse
    {
        $codes->cancel($request);
        $request->session()->regenerate();

        return redirect()->route('login');
    }

    private function expired(Request $request, LoginCodeService $codes): RedirectResponse
    {
        $codes->cancel($request);

        return redirect()->route('login')->with('status', (new ExpiredTwoFactorCodeException)->getMessage());
    }
}
