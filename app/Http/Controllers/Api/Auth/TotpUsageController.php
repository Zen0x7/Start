<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\TotpDevice;
use App\Models\TotpUsageLog;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TotpUsageController extends Controller
{
    public static function log(User $user, TotpDevice $device, string $action, ?Request $request = null): TotpUsageLog
    {
        return TotpUsageLog::create([
            'user_id' => $user->id,
            'totp_device_id' => $device->id,
            'action' => $action,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    public function confirmAction(Request $request, TotpService $totp): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'totp_code' => ['required', 'string', 'size:6'],
            'action' => ['required', 'string', 'max:100'],
        ]);

        $device = $totp->verifyAny($user, $validated['totp_code']);

        if ($device === null) {
            throw new HttpException(403, 'El código TOTP no es válido.');
        }

        static::log($user, $device, $validated['action'], $request);

        return response()->json(['message' => 'Código TOTP verificado exitosamente.']);
    }
}
