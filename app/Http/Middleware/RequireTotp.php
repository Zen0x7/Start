<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Api\Auth\TotpUsageController;
use App\Services\TotpService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RequireTotp
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            throw new HttpException(401, 'Authentication required.');
        }

        $code = $request->header('X-TOTP-Code') ?? $request->input('totp_code');

        if ($code === null) {
            throw new HttpException(403, 'Se requiere un código TOTP para esta operación.');
        }

        $device = app(TotpService::class)->verifyAny($user, $code);

        if ($device === null) {
            throw new HttpException(403, 'El código TOTP no es válido.');
        }

        TotpUsageController::log($user, $device, $request->method().' '.$request->path(), $request);

        return $next($request);
    }
}
