<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\JwtService;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use OTPHP\TOTP;
use Tests\TestCase;

class TotpVerifyTest extends TestCase
{
    use LazilyRefreshDatabase;

    private JwtService $jwt;

    private TotpService $totp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwt = app(JwtService::class);
        $this->totp = app(TotpService::class);
    }

    public function test_verify_with_valid_code_returns_auth_token(): void
    {
        $totp = TOTP::generate();
        $secret = $totp->getSecret();
        $user = User::factory()->create();

        $this->totp->createDevice($user, $secret);
        $tempToken = $this->jwt->buildTotpChallengeToken((string) $user->id);

        $validCode = $totp->now();

        $response = $this->postJson('/api/auth/totp/verify', [
            'temp_token' => $tempToken,
            'totp_code' => $validCode,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'user']);

        $authPayload = $this->jwt->validateAuthToken($response->json('token'));
        $this->assertSame((string) $user->id, $authPayload['payload']['user_id']);
    }

    public function test_verify_with_invalid_code_fails(): void
    {
        $secret = TOTP::generate()->getSecret();
        $user = User::factory()->create();

        $this->totp->createDevice($user, $secret);
        $tempToken = $this->jwt->buildTotpChallengeToken((string) $user->id);

        $response = $this->postJson('/api/auth/totp/verify', [
            'temp_token' => $tempToken,
            'totp_code' => '000000',
        ]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'El código TOTP ingresado no es válido.']);
    }

    public function test_verify_requires_fields(): void
    {
        $response = $this->postJson('/api/auth/totp/verify', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['temp_token', 'totp_code']);
    }
}
