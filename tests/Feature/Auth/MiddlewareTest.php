<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\EmailVerified;
use App\Http\Middleware\JwtAuth;
use App\Http\Middleware\VerifyToken;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use LazilyRefreshDatabase;

    private JwtService $jwt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwt = app(JwtService::class);
    }

    public function test_jwt_auth_passes_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = $this->jwt->buildAuthToken((string) $user->id);

        $request = Request::create('/_test', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$token);

        $middleware = new JwtAuth;
        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame($user->id, $request->user()->id);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_jwt_auth_rejects_missing_token(): void
    {
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Authentication required');

        $request = Request::create('/_test', 'GET');

        $middleware = new JwtAuth;
        $middleware->handle($request, fn () => response()->json(['ok' => true]));
    }

    public function test_jwt_auth_rejects_invalid_token(): void
    {
        $this->expectException(UnauthorizedHttpException::class);

        $request = Request::create('/_test', 'GET');
        $request->headers->set('Authorization', 'Bearer invalid-token-here');

        $middleware = new JwtAuth;
        $middleware->handle($request, fn () => response()->json(['ok' => true]));
    }

    public function test_jwt_auth_rejects_expired_token(): void
    {
        $this->expectException(UnauthorizedHttpException::class);

        $user = User::factory()->create();
        $token = $this->jwt->signAndEncrypt([
            'issued_at' => time() - 3600,
            'expires_at' => time() - 1,
            'type' => 'auth',
            'payload' => ['user_id' => (string) $user->id],
        ]);

        $request = Request::create('/_test', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$token);

        $middleware = new JwtAuth;
        $middleware->handle($request, fn () => response()->json(['ok' => true]));
    }

    public function test_jwt_auth_rejects_nonexistent_user(): void
    {
        $this->expectException(UnauthorizedHttpException::class);

        $token = $this->jwt->buildAuthToken('99999');

        $request = Request::create('/_test', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$token);

        $middleware = new JwtAuth;
        $middleware->handle($request, fn () => response()->json(['ok' => true]));
    }

    public function test_jwt_auth_rejects_token_without_user_id(): void
    {
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid token payload');

        $token = $this->jwt->signAndEncrypt([
            'issued_at' => time(),
            'expires_at' => time() + 3600,
            'type' => 'auth',
            'payload' => [],
        ]);

        $request = Request::create('/_test', 'GET');
        $request->headers->set('Authorization', 'Bearer '.$token);

        $middleware = new JwtAuth;
        $middleware->handle($request, fn () => response()->json(['ok' => true]));
    }

    public function test_email_verified_passes_for_verified_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $request = Request::create('/_test', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new EmailVerified;
        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_email_verified_rejects_unverified_user(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Antes de continuar deberás confirmar tu correo electrónico.');

        $user = User::factory()->unverified()->create();

        $request = Request::create('/_test', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new EmailVerified;
        $middleware->handle($request, fn () => response()->json(['ok' => true]));
    }

    public function test_email_verified_rejects_no_user(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Antes de continuar deberás confirmar tu correo electrónico.');

        $request = Request::create('/_test', 'GET');

        $middleware = new EmailVerified;
        $middleware->handle($request, fn () => response()->json(['ok' => true]));
    }

    public function test_verify_token_passes_with_valid_token(): void
    {
        $token = $this->jwt->buildEmailVerificationToken('test@example.com');

        $request = Request::create('/api/auth/verify-email', 'POST', ['token' => $token]);

        $middleware = new VerifyToken;
        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_verify_token_rejects_without_token(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Verification token is required');

        $request = Request::create('/api/test', 'POST');

        $middleware = new VerifyToken;
        $middleware->handle($request, fn () => response()->json(['ok' => true]));
    }

    public function test_verify_token_rejects_expired_token(): void
    {
        $this->expectException(HttpException::class);

        $token = $this->jwt->signAndEncrypt([
            'issued_at' => time() - 1000,
            'expires_at' => time() - 1,
            'type' => 'request',
            'payload' => [
                'method' => ['POST'],
                'path' => ['/api/test'],
                'data' => ['email' => 'test@example.com'],
            ],
        ]);

        $request = Request::create('/api/test', 'POST', ['token' => $token]);

        $middleware = new VerifyToken;
        $middleware->handle($request, fn () => response()->json(['ok' => true]));
    }

    public function test_verify_token_rejects_wrong_method(): void
    {
        $this->expectException(HttpException::class);

        $token = $this->jwt->buildEmailVerificationToken('test@example.com');

        $request = Request::create('/api/test', 'GET');
        $request->query->set('token', $token);

        $middleware = new VerifyToken;
        $middleware->handle($request, fn () => response()->json(['ok' => true]));
    }
}
