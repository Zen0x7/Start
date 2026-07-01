<?php

namespace Tests\Feature\Api;

use App\Models\LoginAttempt;
use App\Models\TotpUsageLog;
use App\Models\User;
use App\Services\JwtService;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use OTPHP\TOTP;
use Tests\TestCase;

class ActivityTest extends TestCase
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

    public function test_returns_login_attempts(): void
    {
        $user = User::factory()->create();

        LoginAttempt::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'successful' => true,
            'ip_address' => '192.168.1.1',
        ]);

        LoginAttempt::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'successful' => false,
            'ip_address' => '192.168.1.2',
        ]);

        $token = $this->jwt->buildAuthToken((string) $user->id);

        $response = $this->getJson('/api/auth/activity', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'activity');

        $activity = $response->json('activity');
        $this->assertCount(2, $activity);
        $this->assertSame('login', $activity[0]['type']);
        $this->assertSame('login', $activity[1]['type']);
    }

    public function test_returns_totp_usage(): void
    {
        $user = User::factory()->create();
        $device = $this->totp->createDevice($user, TOTP::generate()->getSecret());

        TotpUsageLog::create([
            'user_id' => $user->id,
            'totp_device_id' => $device->id,
            'action' => 'login',
        ]);

        $token = $this->jwt->buildAuthToken((string) $user->id);

        $response = $this->getJson('/api/auth/activity', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'activity');

        $activity = $response->json('activity');
        $this->assertSame('totp', $activity[0]['type']);
        $this->assertTrue($activity[0]['successful']);
        $this->assertSame('Default', $activity[0]['device']);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/auth/activity');

        $response->assertStatus(401);
    }
}
