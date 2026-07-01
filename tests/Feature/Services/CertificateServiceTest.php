<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CertificateServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private CertificateService $certificates;

    protected function setUp(): void
    {
        parent::setUp();

        $this->certificates = app(CertificateService::class);
    }

    public function test_issue_creates_certificate(): void
    {
        $user = User::factory()->create();

        $cert = $this->certificates->issue($user->id);

        $this->assertNotNull($cert->id);
        $this->assertSame($user->id, $cert->user_id);
        $this->assertNotEmpty($cert->public_key);
        $this->assertNotEmpty($cert->private_key);
        $this->assertNotEmpty($cert->signature);
        $this->assertStringContainsString('-----BEGIN PUBLIC KEY-----', $cert->public_key);
        $this->assertTrue($cert->expires_at->isFuture());
    }

    public function test_verify_valid_certificate(): void
    {
        $user = User::factory()->create();
        $cert = $this->certificates->issue($user->id);

        $this->assertTrue($this->certificates->verify($cert));
    }

    public function test_verify_expired_certificate(): void
    {
        $user = User::factory()->create();
        $cert = $this->certificates->issue($user->id);

        $cert->expires_at = now()->subMinute();
        $cert->save();

        $this->assertFalse($this->certificates->verify($cert->fresh()));
    }

    public function test_verify_tampered_certificate(): void
    {
        $user = User::factory()->create();
        $cert = $this->certificates->issue($user->id);

        $cert->public_key = str_replace('A', 'B', $cert->public_key);
        $cert->save();

        $this->assertFalse($this->certificates->verify($cert->fresh()));
    }

    public function test_public_key_jwk_rejects_invalid_key(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid public key in certificate');

        $user = User::factory()->create();
        $cert = $this->certificates->issue($user->id);
        $cert->public_key = 'not a valid PEM key';
        $cert->save();

        $this->certificates->publicKeyJwk($cert->fresh());
    }
}
