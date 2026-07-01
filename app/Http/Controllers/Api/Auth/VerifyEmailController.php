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
            throw new HttpException(400, 'El enlace de verificación no es válido o ha expirado.');
        }

        if (($payload['type'] ?? null) !== 'request') {
            throw new HttpException(400, 'Token inválido.');
        }

        $email = $payload['payload']['data']['email'] ?? null;

        if ($email === null) {
            throw new HttpException(400, 'Token inválido: falta el correo electrónico.');
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
            throw new HttpException(400, 'Token inválido.');
        }

        $user = User::where('email', $email)->first();

        if ($user === null) {
            throw new HttpException(404, 'Usuario no encontrado.');
        }

        if ($user->email_verified_at !== null) {
            return response()->json(['message' => 'El correo electrónico ya está verificado.']);
        }

        if (! password_verify($password, $user->password)) {
            throw new HttpException(403, 'La contraseña ingresada es incorrecta.');
        }

        $user->email_verified_at = now();
        $user->save();

        $tempToken = $jwt->buildTotpChallengeToken((string) $user->id);

        return response()->json([
            'message' => 'Correo electrónico confirmado exitosamente.',
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

            throw new HttpException(429, 'Demasiadas solicitudes. Intenta de nuevo en '.ceil($seconds / 60).' minutos.');
        }

        RateLimiter::hit($rateLimitKey, 3600 * 6);

        $user = User::where('email', $email)->first();

        if ($user->email_verified_at !== null) {
            return response()->json(['message' => 'El correo electrónico ya está verificado.']);
        }

        $token = $jwt->buildEmailVerificationToken($email);

        $user->notify(new VerifyEmailNotification($token));

        return response()->json(['message' => 'Correo de verificación reenviado.']);
    }
}
