<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Services\JwtService;
use App\Services\TotpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, JwtService $jwt): JsonResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        if ($user === null || ! password_verify($request->input('password'), $user->password)) {
            LoginAttempt::create([
                'user_id' => $user?->id,
                'email' => $request->input('email'),
                'successful' => false,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            throw ValidationException::withMessages([
                'email' => [__('auth.credentials')],
            ]);
        }

        LoginAttempt::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'successful' => true,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($user->email_verified_at === null) {
            return response()->json([
                'message' => __('auth.unverified'),
                'email' => $user->email,
            ], 403);
        }

        $tempToken = $jwt->buildTotpChallengeToken((string) $user->id);

        $hasTotp = app(TotpService::class)->hasDevices($user);

        return response()->json([
            'totp_status' => $hasTotp ? 'verify_required' : 'setup_required',
            'temp_token' => $tempToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
