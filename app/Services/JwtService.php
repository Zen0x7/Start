<?php

namespace App\Services;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use RuntimeException;

class JwtService
{
    private Encrypter $encrypter;

    public function __construct(private Repository $config)
    {
        $key = base64_decode((string) $this->config->get('jwt.signing_key'));

        $this->encrypter = new Encrypter($key, (string) $this->config->get('jwt.cipher', 'aes-256-gcm'));
    }

    public function signAndEncrypt(array $payload): string
    {
        return $this->encrypter->encryptString(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public function decryptAndVerify(string $token): array
    {
        try {
            $data = $this->encrypter->decryptString($token);
        } catch (DecryptException) {
            throw new RuntimeException('Invalid or tampered token');
        }

        return json_decode($data, associative: true, flags: JSON_THROW_ON_ERROR);
    }

    public function verifyRequestToken(string $token, string $method, string $path, ?callable $dataAssertion = null): array
    {
        $payload = $this->decryptAndVerify($token);

        if (($payload['type'] ?? null) !== 'request') {
            throw new RuntimeException('Token is not a request-type token');
        }

        if (($payload['expires_at'] ?? 0) < time()) {
            throw new RuntimeException('Token has expired');
        }

        $tokenPayload = $payload['payload'] ?? [];

        $allowedMethods = (array) ($tokenPayload['method'] ?? []);
        $allowedPaths = (array) ($tokenPayload['path'] ?? []);

        $methodMatch = false;
        foreach ($allowedMethods as $allowed) {
            if (strcasecmp($allowed, $method) === 0) {
                $methodMatch = true;
                break;
            }
        }

        if (! $methodMatch) {
            throw new RuntimeException('Token does not authorize this HTTP method');
        }

        $pathMatch = false;
        foreach ($allowedPaths as $allowed) {
            if (fnmatch($allowed, $path)) {
                $pathMatch = true;
                break;
            }
        }

        if (! $pathMatch) {
            throw new RuntimeException('Token does not authorize this path');
        }

        if ($dataAssertion !== null) {
            $dataAssertion($tokenPayload['data'] ?? []);
        }

        return $payload;
    }

    public function buildEmailVerificationToken(string $email): string
    {
        $ttl = (int) $this->config->get('jwt.ttl.verify_email', 900);

        return $this->signAndEncrypt([
            'issued_at' => time(),
            'expires_at' => time() + $ttl,
            'type' => 'request',
            'payload' => [
                'method' => ['POST'],
                'path' => ['/api/auth/verify-email'],
                'data' => [
                    'email' => $email,
                ],
            ],
        ]);
    }

    public function buildAuthToken(string $userId): string
    {
        $ttl = (int) $this->config->get('jwt.ttl.auth', 3600);

        return $this->signAndEncrypt([
            'issued_at' => time(),
            'expires_at' => time() + $ttl,
            'type' => 'auth',
            'payload' => [
                'user_id' => $userId,
            ],
        ]);
    }

    public function validateAuthToken(string $token): array
    {
        $payload = $this->decryptAndVerify($token);

        if (($payload['type'] ?? null) !== 'auth') {
            throw new RuntimeException('Token is not an auth-type token');
        }

        if (($payload['expires_at'] ?? 0) < time()) {
            throw new RuntimeException('Auth token has expired');
        }

        return $payload;
    }
}
