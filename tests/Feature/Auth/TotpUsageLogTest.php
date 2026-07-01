<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Api\Auth\TotpUsageController;
use App\Models\TotpDevice;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use OTPHP\TOTP;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TotpUsageLogTest extends TestCase
{
    use LazilyRefreshDatabase;

    private TotpService $totp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->totp = app(TotpService::class);
    }

    public function test_log_creates_entry(): void
    {
        $user = User::factory()->create();
        $device = $this->totp->createDevice($user, TOTP::generate()->getSecret());

        $log = TotpUsageController::log($user, $device, 'login');

        $this->assertDatabaseHas('totp_usage_logs', [
            'id' => $log->id,
            'user_id' => $user->id,
            'totp_device_id' => $device->id,
            'action' => 'login',
        ]);

        $this->assertInstanceOf(User::class, $log->user);
        $this->assertSame($user->id, $log->user->id);

        $this->assertInstanceOf(TotpDevice::class, $log->device);
        $this->assertSame($device->id, $log->device->id);
    }

    public function test_log_stores_ip_and_user_agent(): void
    {
        $user = User::factory()->create();
        $device = $this->totp->createDevice($user, TOTP::generate()->getSecret());

        $request = Request::create('/test', 'POST');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');
        $request->headers->set('User-Agent', 'TestBrowser/1.0');

        $log = TotpUsageController::log($user, $device, 'confirm_action', $request);

        $this->assertSame('192.168.1.1', $log->ip_address);
        $this->assertSame('TestBrowser/1.0', $log->user_agent);
    }

    public function test_confirm_action_with_valid_code(): void
    {
        $user = User::factory()->create();
        $totp = TOTP::generate();
        $this->totp->createDevice($user, $totp->getSecret());

        $request = Request::create('/api/auth/totp/confirm-action', 'POST', [
            'totp_code' => $totp->now(),
            'action' => 'delete_entity',
        ]);
        $request->setUserResolver(fn () => $user);

        $controller = app(TotpUsageController::class);
        $response = $controller->confirmAction($request, $this->totp);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Código TOTP verificado exitosamente.', $response->getData()->message);

        $this->assertDatabaseHas('totp_usage_logs', [
            'user_id' => $user->id,
            'action' => 'delete_entity',
        ]);
    }

    public function test_confirm_action_with_invalid_code(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage(__('totp.wrong_code'));

        $user = User::factory()->create();
        $totp = TOTP::generate();
        $this->totp->createDevice($user, $totp->getSecret());

        $request = Request::create('/api/auth/totp/confirm-action', 'POST', [
            'totp_code' => '000000',
            'action' => 'delete_entity',
        ]);
        $request->setUserResolver(fn () => $user);

        $controller = app(TotpUsageController::class);
        $controller->confirmAction($request, $this->totp);
    }
}
