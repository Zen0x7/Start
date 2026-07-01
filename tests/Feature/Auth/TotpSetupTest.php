<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\CertificateService;
use App\Services\JwtService;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use OTPHP\TOTP;
use phpseclib3\Crypt\PublicKeyLoader;
use Tests\TestCase;

class TotpSetupTest extends TestCase
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

    public function test_init_returns_public_key_and_cert_id(): void
    {
        $user = User::factory()->create();
        $tempToken = $this->jwt->buildTotpChallengeToken((string) $user->id);

        $response = $this->postJson('/api/auth/totp/setup/init', [
            'temp_token' => $tempToken,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['cert_id', 'public_key']);
        $this->assertStringContainsString('-----BEGIN PUBLIC KEY-----', $response->json('public_key'));
    }

    public function test_init_fails_without_token(): void
    {
        $response = $this->postJson('/api/auth/totp/setup/init', []);

        $response->assertStatus(401);
    }

    public function test_init_allows_multiple_devices(): void
    {
        $user = User::factory()->create();
        $this->totp->createDevice($user, TOTP::generate()->getSecret());
        $tempToken = $this->jwt->buildTotpChallengeToken((string) $user->id);

        $response = $this->postJson('/api/auth/totp/setup/init', [
            'temp_token' => $tempToken,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['cert_id', 'public_key']);
    }

    public function test_confirm_with_invalid_code_fails(): void
    {
        $user = User::factory()->create();
        $tempToken = $this->jwt->buildTotpChallengeToken((string) $user->id);

        $initResponse = $this->postJson('/api/auth/totp/setup/init', [
            'temp_token' => $tempToken,
        ]);

        $certId = $initResponse->json('cert_id');
        $publicKey = $initResponse->json('public_key');

        $totp = TOTP::generate();
        $secret = $totp->getSecret();
        $encrypted = PublicKeyLoader::loadPublicKey($publicKey)
            ->withHash('sha256')
            ->withMGFHash('sha256')
            ->encrypt($secret);

        $response = $this->postJson('/api/auth/totp/setup/confirm', [
            'temp_token' => $tempToken,
            'cert_id' => $certId,
            'encrypted_secret' => base64_encode($encrypted),
            'totp_code' => '000000',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'El código TOTP no es válido. Escanea el código QR nuevamente.']);
    }

    public function test_complete_setup_flow_with_valid_totp(): void
    {
        $user = User::factory()->create();
        $tempToken = $this->jwt->buildTotpChallengeToken((string) $user->id);

        $initResponse = $this->postJson('/api/auth/totp/setup/init', [
            'temp_token' => $tempToken,
        ]);

        $certId = $initResponse->json('cert_id');
        $publicKey = $initResponse->json('public_key');

        $totp = TOTP::generate();
        $secret = $totp->getSecret();
        $encrypted = PublicKeyLoader::loadPublicKey($publicKey)
            ->withHash('sha256')
            ->withMGFHash('sha256')
            ->encrypt($secret);

        $validCode = $totp->now();

        $response = $this->postJson('/api/auth/totp/setup/confirm', [
            'temp_token' => $tempToken,
            'cert_id' => $certId,
            'encrypted_secret' => base64_encode($encrypted),
            'totp_code' => $validCode,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'token', 'user']);
        $response->assertJson(['message' => 'Dispositivo TOTP configurado exitosamente.']);

        $this->assertDatabaseHas('totp_devices', [
            'user_id' => $user->id,
        ]);
    }

    public function test_confirm_with_expired_certificate_fails(): void
    {
        $user = User::factory()->create();
        $tempToken = $this->jwt->buildTotpChallengeToken((string) $user->id);

        $cert = app(CertificateService::class)->issue($user->id);
        $cert->expires_at = now()->subHour();
        $cert->save();

        $secret = TOTP::generate()->getSecret();
        $encrypted = PublicKeyLoader::loadPublicKey($cert->public_key)
            ->withHash('sha256')
            ->withMGFHash('sha256')
            ->encrypt($secret);

        $response = $this->postJson('/api/auth/totp/setup/confirm', [
            'temp_token' => $tempToken,
            'cert_id' => $cert->id,
            'encrypted_secret' => base64_encode($encrypted),
            'totp_code' => '000000',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'El certificado ha expirado. Solicita uno nuevo.']);
    }

    public function test_init_with_auth_token_instead_of_challenge(): void
    {
        $user = User::factory()->create();
        $authToken = $this->jwt->buildAuthToken((string) $user->id);

        $response = $this->postJson('/api/auth/totp/setup/init', [], [
            'Authorization' => 'Bearer '.$authToken,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['cert_id', 'public_key']);
    }
}
