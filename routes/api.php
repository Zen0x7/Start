<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::name('auth.')->group(function (): void {
    Route::post('/auth/register', RegisterController::class)->name('register');
    Route::post('/auth/login', LoginController::class)->name('login');

    Route::get('/auth/verify-email/{token}', [VerifyEmailController::class, 'check'])->name('verify-email.check');
    Route::post('/auth/verify-email', [VerifyEmailController::class, 'verify'])->name('verify-email.confirm');
    Route::post('/auth/resend-verification', [VerifyEmailController::class, 'resend'])->name('verify-email.resend');
});
