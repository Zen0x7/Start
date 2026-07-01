<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use App\Services\JwtService;
use App\Services\TotpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ResetPasswordController extends Controller
{
    public function reset(Request $request, JwtService $jwt, TotpService $totp): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'email' => ['required', 'string', 'email'],
            'totp_code' => ['nullable', 'string', 'size:6'],
        ]);

        try {
            $payload = $jwt->decryptAndVerify($validated['token']);
        } catch (\RuntimeException) {
            throw ValidationException::withMessages([
                'token' => [__('passwords.invalid_token')],
            ]);
        }

        $tokenEmail = $payload['payload']['data']['email'] ?? null;

        if ($tokenEmail !== $validated['email']) {
            throw ValidationException::withMessages([
                'email' => [__('passwords.invalid_token')],
            ]);
        }

        $user = User::where('email', $validated['email'])->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => [__('passwords.invalid_user')],
            ]);
        }

        $hasTotp = $user->totpDevices()->exists();

        if ($hasTotp) {
            if (empty($validated['totp_code'])) {
                throw ValidationException::withMessages([
                    'totp_code' => [__('passwords.totp_required')],
                ]);
            }

            $device = $totp->verifyAny($user, $validated['totp_code']);

            if ($device === null) {
                throw ValidationException::withMessages([
                    'totp_code' => [__('passwords.totp_invalid')],
                ]);
            }
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        $user->notify(
            (new PasswordChangedNotification)->locale($user->locale),
        );

        return response()->json(['message' => __('passwords.reset')]);
    }
}
