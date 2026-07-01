<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use OTPHP\TOTP;
use Tests\TestCase;

class TotpServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private TotpService $totp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->totp = app(TotpService::class);
    }

    public function test_generate_secret_returns_base32(): void
    {
        $secret = $this->totp->generateSecret();

        $this->assertNotEmpty($secret);
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+=*$/', $secret);

        $totp = TOTP::createFromSecret($secret);
        $this->assertNotEmpty($totp->now());
    }

    public function test_verify_returns_true_for_valid_code(): void
    {
        $totp = TOTP::generate();
        $secret = $totp->getSecret();
        $code = $totp->now();

        $this->assertTrue($this->totp->verify($secret, $code));
    }

    public function test_verify_returns_false_for_invalid_code(): void
    {
        $totp = TOTP::generate();
        $secret = $totp->getSecret();

        $this->assertFalse($this->totp->verify($secret, '000000'));
    }

    public function test_create_device_stores_encrypted_secret(): void
    {
        $user = User::factory()->create();
        $secret = TOTP::generate()->getSecret();

        $device = $this->totp->createDevice($user, $secret, 'Test Device');

        $this->assertDatabaseHas('totp_devices', [
            'id' => $device->id,
            'user_id' => $user->id,
            'label' => 'Test Device',
        ]);

        $this->assertSame($secret, $this->totp->getSecret($device));
    }

    public function test_verify_device_works(): void
    {
        $user = User::factory()->create();
        $totp = TOTP::generate();
        $secret = $totp->getSecret();

        $device = $this->totp->createDevice($user, $secret);

        $this->assertTrue($this->totp->verifyDevice($device, $totp->now()));
        $this->assertFalse($this->totp->verifyDevice($device, '000000'));
    }

    public function test_has_devices(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->totp->hasDevices($user));

        $this->totp->createDevice($user, TOTP::generate()->getSecret());

        $this->assertTrue($this->totp->hasDevices($user));
    }

    public function test_provisioning_uri(): void
    {
        $secret = TOTP::generate()->getSecret();
        $uri = $this->totp->provisioningUri($secret, 'user@example.com', 'MyApp');

        $this->assertStringContainsString('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret='.$secret, $uri);
        $this->assertStringContainsString('issuer=MyApp', $uri);
    }
}
