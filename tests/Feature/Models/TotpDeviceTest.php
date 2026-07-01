<?php

namespace Tests\Feature\Models;

use App\Models\TotpCertificate;
use App\Models\TotpDevice;
use App\Models\TotpUsageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use OTPHP\TOTP;
use Tests\TestCase;

class TotpDeviceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_totp_device_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $device = TotpDevice::create([
            'user_id' => $user->id,
            'secret' => TOTP::generate()->getSecret(),
            'label' => 'Test',
        ]);

        $this->assertInstanceOf(User::class, $device->user);
        $this->assertSame($user->id, $device->user->id);
    }

    public function test_totp_certificate_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $cert = TotpCertificate::create([
            'user_id' => $user->id,
            'public_key' => 'test',
            'private_key' => 'test',
            'signature' => 'test',
            'expires_at' => now()->addHour(),
        ]);

        $this->assertInstanceOf(User::class, $cert->user);
        $this->assertSame($user->id, $cert->user->id);
    }

    public function test_totp_usage_log_belongs_to_user_and_device(): void
    {
        $user = User::factory()->create();
        $device = TotpDevice::create([
            'user_id' => $user->id,
            'secret' => TOTP::generate()->getSecret(),
        ]);

        $log = TotpUsageLog::create([
            'user_id' => $user->id,
            'totp_device_id' => $device->id,
            'action' => 'test',
        ]);

        $this->assertInstanceOf(User::class, $log->user);
        $this->assertSame($user->id, $log->user->id);

        $this->assertInstanceOf(TotpDevice::class, $log->device);
        $this->assertSame($device->id, $log->device->id);
    }
}
