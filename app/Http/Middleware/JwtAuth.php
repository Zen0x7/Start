<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class JwtAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            throw new UnauthorizedHttpException('Bearer', 'Authentication required');
        }

        try {
            $payload = app(JwtService::class)->validateAuthToken($token);
        } catch (\RuntimeException) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid or expired token');
        }

        $userId = $payload['payload']['user_id'] ?? null;

        if ($userId === null) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid token payload');
        }

        $user = User::find($userId);

        if ($user === null) {
            throw new UnauthorizedHttpException('Bearer', 'User not found');
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
