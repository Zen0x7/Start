<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    public function sendResetLink(Request $request, JwtService $jwt): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        $token = $jwt->buildEmailVerificationToken($user->email);

        $user->notify(
            (new ResetPasswordNotification($token))->locale($user->locale),
        );

        return response()->json(['message' => __('passwords.sent')]);
    }

    public function checkToken(string $token, JwtService $jwt): JsonResponse
    {
        try {
            $payload = $jwt->decryptAndVerify($token);
        } catch (\RuntimeException) {
            throw ValidationException::withMessages([
                'token' => [__('passwords.invalid_token')],
            ]);
        }

        $email = $payload['payload']['data']['email'] ?? null;

        if ($email === null) {
            throw ValidationException::withMessages([
                'token' => [__('passwords.invalid_token')],
            ]);
        }

        $user = User::where('email', $email)->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'token' => [__('passwords.invalid_user')],
            ]);
        }

        $hasTotp = $user->totpDevices()->exists();

        return response()->json([
            'email' => $email,
            'has_totp' => $hasTotp,
        ]);
    }
}
