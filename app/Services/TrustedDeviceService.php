<?php

namespace App\Services;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TrustedDeviceService
{
    public function findTrustedDevice(Request $request, User $user, bool $recordUse = true): ?TrustedDevice
    {
        $cookie = $request->cookie('trusted_device');
        if (! is_string($cookie) || ! preg_match('/\A([a-zA-Z0-9]{40})\|([a-zA-Z0-9]{64})\z/', $cookie, $parts)) {
            return null;
        }

        $device = $user->trustedDevices()
            ->where('selector', $parts[1])
            ->where('expires_at', '>', now())
            ->first();

        if (! $device || ! hash_equals($device->token_hash, $this->tokenHash($user, $parts[2]))) {
            return null;
        }

        if ($recordUse) {
            $device->update([
                'last_used_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit($request->userAgent() ?? '', 255, ''),
            ]);
        }

        return $device;
    }

    public function trustDevice(Request $request, User $user): void
    {
        $selector = Str::random(40);
        $validator = Str::random(64);
        $days = config('two_factor.trusted_device_days');

        $user->trustedDevices()->create([
            'selector' => $selector,
            'token_hash' => $this->tokenHash($user, $validator),
            'user_agent' => Str::limit($request->userAgent() ?? '', 255, ''),
            'ip_address' => $request->ip(),
            'last_used_at' => now(),
            'expires_at' => now()->addDays($days),
        ]);

        Cookie::queue(Cookie::make(
            'trusted_device',
            $selector.'|'.$validator,
            $days * 24 * 60,
            '/',
            config('session.domain'),
            app()->isProduction() || $request->isSecure(),
            true,
            false,
            'lax',
        ));
    }

    public function revokeTrustedDevices(User $user): void
    {
        DB::transaction(function () use ($user) {
            $count = $user->trustedDevices()->delete();
            $actor = auth()->user();
            $user->audits()->create([
                'event' => 'trusted_devices_revoked',
                'user_type' => $actor?->getMorphClass(),
                'user_id' => $actor?->getKey(),
                'old_values' => ['trusted_devices_count' => $count],
                'new_values' => ['trusted_devices_count' => 0],
                'url' => request()->url(),
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit(request()->userAgent() ?? '', 255, ''),
            ]);
        });
        Cookie::queue(Cookie::forget('trusted_device', '/', config('session.domain')));
    }

    public function forProfile(Request $request): array
    {
        $user = $request->user();
        $current = $this->findTrustedDevice($request, $user, recordUse: false);

        return $user->trustedDevices()->orderByDesc('last_used_at')->orderByDesc('id')->get()
            ->map(fn (TrustedDevice $device) => [
                'id' => $device->id,
                'user_agent' => $device->user_agent,
                'ip_address' => $device->ip_address,
                'created_at' => $device->created_at?->toIso8601String(),
                'last_used_at' => $device->last_used_at?->toIso8601String(),
                'expires_at' => $device->expires_at->toIso8601String(),
                'expired' => $device->expires_at->isPast(),
                'is_current' => $current?->id === $device->id,
            ])->all();
    }

    public function revokeDevice(Request $request, int $id): void
    {
        $user = $request->user();
        $current = $this->findTrustedDevice($request, $user, recordUse: false);
        DB::transaction(function () use ($user, $id) {
            $device = $user->trustedDevices()->lockForUpdate()->findOrFail($id);
            $device->delete();
            $user->audits()->create([
                'event' => 'trusted_device_revoked',
                'user_type' => $user->getMorphClass(),
                'user_id' => $user->id,
                'old_values' => ['trusted_device_id' => $id],
                'new_values' => [],
                'url' => request()->url(),
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit(request()->userAgent() ?? '', 255, ''),
            ]);
        });

        if ($current?->id === $id) {
            Cookie::queue(Cookie::forget('trusted_device', '/', config('session.domain')));
        }
    }

    private function tokenHash(User $user, string $validator): string
    {
        // Credential changes invalidate trust even outside the password controllers.
        return hash_hmac('sha256', $user->id.'|'.$user->email.'|'.$user->getAuthPassword().'|'.$validator, config('app.key'));
    }
}
