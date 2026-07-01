<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VerifyToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->input('token') ?? $request->query('token') ?? $request->bearerToken();

        if ($token === null) {
            throw new HttpException(400, 'Verification token is required');
        }

        try {
            app(JwtService::class)->verifyRequestToken(
                token: $token,
                method: $request->method(),
                path: '/'.$request->path(),
            );
        } catch (\RuntimeException $e) {
            throw new HttpException(400, $e->getMessage());
        }

        return $next($request);
    }
}
