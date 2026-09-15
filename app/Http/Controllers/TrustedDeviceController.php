<?php

namespace App\Http\Controllers;

use App\Models\TrustedDevice;
use App\Services\TrustedDeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrustedDeviceController extends Controller
{
    public function destroy(Request $request, TrustedDevice $device, TrustedDeviceService $devices): RedirectResponse
    {
        $devices->revokeDevice($request, $device->id);

        return redirect()->route('profile.edit');
    }

    public function destroyAll(Request $request, TrustedDeviceService $devices): RedirectResponse
    {
        $devices->revokeTrustedDevices($request->user());

        return redirect()->route('profile.edit');
    }
}
