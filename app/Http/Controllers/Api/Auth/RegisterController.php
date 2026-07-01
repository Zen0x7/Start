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
        $data = $request->validated();
        $data['locale'] = app()->getLocale();

        $user = User::create($data);

        $token = $jwt->buildEmailVerificationToken($user->email);

        $user->notify(
            (new VerifyEmailNotification($token))->locale($user->locale),
        );

        return response()->json([
            'message' => __('auth.account_created'),
            'email' => $user->email,
        ], 201);
    }
}
