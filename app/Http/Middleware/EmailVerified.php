<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EmailVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->email_verified_at === null) {
            throw new AccessDeniedHttpException(
                'Antes de continuar deberás confirmar tu correo electrónico.',
            );
        }

        return $next($request);
    }
}
