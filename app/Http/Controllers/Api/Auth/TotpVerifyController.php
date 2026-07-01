<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\JwtService;
use App\Services\TotpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TotpVerifyController extends Controller
{
    public function verify(Request $request, JwtService $jwt, TotpService $totp): JsonResponse
    {
        $validated = $request->validate([
            'temp_token' => ['required', 'string'],
            'totp_code' => ['required', 'string', 'size:6'],
        ]);

        $payload = $jwt->validateTotpChallengeToken($validated['temp_token']);
        $user = User::findOrFail($payload['payload']['user_id']);

        $device = $totp->verifyAny($user, $validated['totp_code']);

        if ($device === null) {
            throw new HttpException(403, __('totp.wrong_code'));
        }

        TotpUsageController::log($user, $device, 'login');

        $authToken = $jwt->buildAuthToken((string) $user->id);

        return response()->json([
            'token' => $authToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
