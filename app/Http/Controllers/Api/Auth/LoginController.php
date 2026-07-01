<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, JwtService $jwt): JsonResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        if ($user === null || ! password_verify($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if ($user->email_verified_at === null) {
            return response()->json([
                'message' => 'Antes de continuar deberás confirmar tu correo electrónico.',
                'email' => $user->email,
            ], 403);
        }

        $token = $jwt->buildAuthToken((string) $user->id);

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
