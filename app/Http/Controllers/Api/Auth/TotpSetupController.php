<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CertificateService;
use App\Services\JwtService;
use App\Services\TotpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TotpSetupController extends Controller
{
    public function init(Request $request, JwtService $jwt, CertificateService $certificates): JsonResponse
    {
        $user = $this->resolveUser($request, $jwt);

        $cert = $certificates->issue($user->id);

        return response()->json([
            'cert_id' => $cert->id,
            'public_key' => $cert->public_key,
            'public_key_jwk' => $certificates->publicKeyJwk($cert),
        ]);
    }

    public function confirm(Request $request, JwtService $jwt, CertificateService $certificates, TotpService $totp): JsonResponse
    {
        $user = $this->resolveUser($request, $jwt);

        $validated = $request->validate([
            'cert_id' => ['required', 'integer', 'exists:totp_certificates,id'],
            'encrypted_secret' => ['required', 'string'],
            'totp_code' => ['required', 'string', 'size:6'],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        $cert = $user->totpCertificates()->findOrFail($validated['cert_id']);

        if (! $certificates->verify($cert)) {
            $cert->delete();
            throw new HttpException(400, __('totp.expired_cert'));
        }

        $secret = $certificates->decryptSecret($cert, $validated['encrypted_secret']);

        if (! $totp->verify($secret, $validated['totp_code'])) {
            throw new HttpException(400, __('totp.invalid_code'));
        }

        $totp->createDevice($user, $secret, $validated['label'] ?? null);

        $cert->delete();

        $authToken = $jwt->buildAuthToken((string) $user->id);

        return response()->json([
            'message' => __('totp.configured'),
            'token' => $authToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    private function resolveUser(Request $request, JwtService $jwt): User
    {
        $token = $request->input('temp_token') ?? $request->bearerToken();

        if ($token === null) {
            throw new HttpException(401, __('totp.token_required'));
        }

        try {
            $payload = $jwt->validateTotpChallengeToken($token);
        } catch (\RuntimeException) {
            $payload = $jwt->validateAuthToken($token);
        }

        return User::findOrFail($payload['payload']['user_id']);
    }
}
