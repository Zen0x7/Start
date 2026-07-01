<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request, JwtService $jwt): JsonResponse
    {
        $user = User::create($request->validated());

        $token = $jwt->buildEmailVerificationToken($user->email);

        $user->notify(new VerifyEmailNotification($token));

        return response()->json([
            'message' => __('auth.account_created'),
            'email' => $user->email,
        ], 201);
    }
}
