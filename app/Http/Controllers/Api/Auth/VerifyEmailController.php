<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VerifyEmailController extends Controller
{
    public function check(string $token, JwtService $jwt): JsonResponse
    {
        try {
            $payload = $jwt->decryptAndVerify($token);
        } catch (\RuntimeException $e) {
            throw new HttpException(400, __('verify.invalid_link'));
        }

        if (($payload['type'] ?? null) !== 'request') {
            throw new HttpException(400, __('verify.invalid_token'));
        }

        $email = $payload['payload']['data']['email'] ?? null;

        if ($email === null) {
            throw new HttpException(400, __('verify.token_missing_email'));
        }

        return response()->json(['email' => $email]);
    }

    public function verify(VerifyEmailRequest $request, JwtService $jwt): JsonResponse
    {
        $token = $request->input('token');
        $password = $request->input('password');

        try {
            $jwt->verifyRequestToken(
                token: $token,
                method: $request->method(),
                path: '/'.$request->path(),
            );
        } catch (\RuntimeException $e) {
            throw new HttpException(400, $e->getMessage());
        }

        $payload = $jwt->decryptAndVerify($token);
        $email = $payload['payload']['data']['email'] ?? null;

        if ($email === null) {
            throw new HttpException(400, __('verify.invalid_token'));
        }

        $user = User::where('email', $email)->first();

        if ($user === null) {
            throw new HttpException(404, __('verify.user_not_found'));
        }

        if ($user->email_verified_at !== null) {
            return response()->json(['message' => __('verify.already_verified')]);
        }

        if (! password_verify($password, $user->password)) {
            throw new HttpException(403, __('verify.wrong_password'));
        }

        $user->email_verified_at = now();
        $user->save();

        $tempToken = $jwt->buildTotpChallengeToken((string) $user->id);

        return response()->json([
            'message' => __('verify.confirmed'),
            'totp_status' => 'setup_required',
            'temp_token' => $tempToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function resend(ResendVerificationRequest $request, JwtService $jwt): JsonResponse
    {
        $email = $request->input('email');

        $rateLimitKey = 'verify-email:'.$email;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            throw new HttpException(429, __('verify.too_many_requests', ['minutes' => ceil($seconds / 60)]));
        }

        RateLimiter::hit($rateLimitKey, 3600 * 6);

        $user = User::where('email', $email)->first();

        if ($user->email_verified_at !== null) {
            return response()->json(['message' => __('verify.already_verified')]);
        }

        $token = $jwt->buildEmailVerificationToken($email);

        $user->notify(new VerifyEmailNotification($token));

        return response()->json(['message' => __('verify.resent')]);
    }
}
