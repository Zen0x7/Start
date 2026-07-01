<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\RequireTotp;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use OTPHP\TOTP;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RequireTotpMiddlewareTest extends TestCase
{
    use LazilyRefreshDatabase;

    private TotpService $totp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->totp = app(TotpService::class);
    }

    public function test_rejects_without_user(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Authentication required.');

        $request = Request::create('/test', 'GET');

        $middleware = new RequireTotp;
        $middleware->handle($request, fn () => response()->json(['ok' => true]));
    }

    public function test_rejects_without_totp_code(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Se requiere un código TOTP para esta operación.');

        $user = User::factory()->create();
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new RequireTotp;
        $middleware->handle($request, fn () => response()->json(['ok' => true]));
    }

    public function test_rejects_invalid_totp_code(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('El código TOTP no es válido.');

        $user = User::factory()->create();
        $this->totp->createDevice($user, TOTP::generate()->getSecret());

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->headers->set('X-TOTP-Code', '000000');

        $middleware = new RequireTotp;
        $middleware->handle($request, fn () => response()->json(['ok' => true]));
    }

    public function test_passes_with_valid_totp_code(): void
    {
        $user = User::factory()->create();
        $totp = TOTP::generate();
        $this->totp->createDevice($user, $totp->getSecret());

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->headers->set('X-TOTP-Code', $totp->now());

        $middleware = new RequireTotp;
        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(200, $response->getStatusCode());
    }
}
