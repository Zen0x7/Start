<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class JwtServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private JwtService $jwt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwt = app(JwtService::class);
    }

    public function test_sign_and_encrypt_returns_opaque_token(): void
    {
        $payload = [
            'issued_at' => time(),
            'expires_at' => time() + 900,
            'type' => 'request',
            'payload' => [
                'method' => ['POST'],
                'path' => ['/api/auth/verify-email'],
                'data' => ['email' => 'test@example.com'],
            ],
        ];

        $token = $this->jwt->signAndEncrypt($payload);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $token);
    }

    public function test_decrypt_and_verify_returns_original_payload(): void
    {
        $payload = [
            'issued_at' => time(),
            'expires_at' => time() + 900,
            'type' => 'request',
            'payload' => [
                'method' => ['POST'],
                'path' => ['/api/test'],
                'data' => ['foo' => 'bar'],
            ],
        ];

        $token = $this->jwt->signAndEncrypt($payload);
        $decoded = $this->jwt->decryptAndVerify($token);

        $this->assertSame($payload, $decoded);
    }

    public function test_rejects_tampered_token(): void
    {
        $this->expectException(\RuntimeException::class);

        $token = $this->jwt->signAndEncrypt(['type' => 'request', 'payload' => []]);

        $tampered = substr($token, 0, -5).'AAAAA';

        $this->jwt->decryptAndVerify($tampered);
    }

    public function test_rejects_invalid_token_format(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid or tampered token');

        $this->jwt->decryptAndVerify('not-a-valid-token');
    }

    public function test_build_email_verification_token(): void
    {
        $token = $this->jwt->buildEmailVerificationToken('user@example.com');

        $payload = $this->jwt->decryptAndVerify($token);

        $this->assertSame('request', $payload['type']);
        $this->assertSame(['POST'], $payload['payload']['method']);
        $this->assertSame(['/api/auth/verify-email'], $payload['payload']['path']);
        $this->assertSame('user@example.com', $payload['payload']['data']['email']);
        $this->assertArrayHasKey('issued_at', $payload);
        $this->assertArrayHasKey('expires_at', $payload);
    }

    public function test_verify_request_token_validates_method_and_path(): void
    {
        $token = $this->jwt->buildEmailVerificationToken('test@example.com');

        $result = $this->jwt->verifyRequestToken(
            $token,
            'POST',
            '/api/auth/verify-email',
        );

        $this->assertSame('request', $result['type']);
    }

    public function test_verify_request_token_rejects_wrong_method(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Token does not authorize this HTTP method',
        );

        $token = $this->jwt->buildEmailVerificationToken('test@example.com');
        $this->jwt->verifyRequestToken($token, 'GET', '/api/auth/verify-email');
    }

    public function test_verify_request_token_rejects_wrong_path(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Token does not authorize this path');

        $token = $this->jwt->buildEmailVerificationToken('test@example.com');
        $this->jwt->verifyRequestToken(
            $token,
            'POST',
            '/api/something-else',
        );
    }

    public function test_verify_request_token_rejects_non_request_type(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Token is not a request-type token');

        $token = $this->jwt->buildAuthToken('1');
        $this->jwt->verifyRequestToken($token, 'POST', '/api/auth/verify-email');
    }

    public function test_verify_request_token_with_data_assertion(): void
    {
        $token = $this->jwt->buildEmailVerificationToken('test@example.com');

        $asserted = null;
        $this->jwt->verifyRequestToken(
            $token,
            'POST',
            '/api/auth/verify-email',
            function (array $data) use (&$asserted): void {
                $asserted = $data;
            },
        );

        $this->assertSame(['email' => 'test@example.com'], $asserted);
    }

    public function test_expired_token_is_rejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Token has expired');

        $payload = [
            'issued_at' => time() - 1000,
            'expires_at' => time() - 1,
            'type' => 'request',
            'payload' => [
                'method' => ['POST'],
                'path' => ['/test'],
                'data' => [],
            ],
        ];

        $token = $this->jwt->signAndEncrypt($payload);
        $this->jwt->verifyRequestToken($token, 'POST', '/test');
    }

    public function test_build_auth_token(): void
    {
        $token = $this->jwt->buildAuthToken('42');
        $payload = $this->jwt->validateAuthToken($token);

        $this->assertSame('auth', $payload['type']);
        $this->assertSame('42', $payload['payload']['user_id']);
    }

    public function test_rejects_non_auth_token_in_validate(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Token is not an auth-type token');

        $token = $this->jwt->buildEmailVerificationToken('test@test.com');
        $this->jwt->validateAuthToken($token);
    }

    public function test_rejects_expired_auth_token(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Auth token has expired');

        $token = $this->jwt->signAndEncrypt([
            'issued_at' => time() - 3600,
            'expires_at' => time() - 1,
            'type' => 'auth',
            'payload' => ['user_id' => '1'],
        ]);
        $this->jwt->validateAuthToken($token);
    }

    public function test_auth_token_with_user_id_authenticates(): void
    {
        $user = User::factory()->create();
        $token = $this->jwt->buildAuthToken((string) $user->id);
        $payload = $this->jwt->validateAuthToken($token);

        $this->assertSame((string) $user->id, $payload['payload']['user_id']);
    }
}
