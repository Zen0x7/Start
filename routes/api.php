<?php

use App\Http\Controllers\Api\Auth\ActivityController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Api\Auth\TotpSetupController;
use App\Http\Controllers\Api\Auth\TotpUsageController;
use App\Http\Controllers\Api\Auth\TotpVerifyController;
use App\Http\Controllers\Api\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::name('auth.')->group(function (): void {
    Route::post('/auth/register', RegisterController::class)->name('register');
    Route::post('/auth/login', LoginController::class)->name('login');

    Route::get('/auth/verify-email/{token}', [VerifyEmailController::class, 'check'])->name('verify-email.check');
    Route::post('/auth/verify-email', [VerifyEmailController::class, 'verify'])->name('verify-email.confirm');
    Route::post('/auth/resend-verification', [VerifyEmailController::class, 'resend'])->name('verify-email.resend');

    Route::post('/auth/totp/setup/init', [TotpSetupController::class, 'init'])->name('totp.setup.init');
    Route::post('/auth/totp/setup/confirm', [TotpSetupController::class, 'confirm'])->name('totp.setup.confirm');
    Route::post('/auth/password/email', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('/auth/password/reset/{token}', [ForgotPasswordController::class, 'checkToken'])->name('password.check');
    Route::post('/auth/password/reset', [ResetPasswordController::class, 'reset'])->name('password.reset');

    Route::post('/auth/totp/verify', [TotpVerifyController::class, 'verify'])->name('totp.verify');
    Route::post('/auth/totp/confirm-action', [TotpUsageController::class, 'confirmAction'])->name('totp.confirm-action');

    Route::middleware('jwt.auth')->group(function (): void {
        Route::get('/auth/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/auth/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/auth/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');
        Route::post('/auth/profile/delete', [ProfileController::class, 'destroy'])->name('profile.delete');
        Route::post('/auth/totp/devices/delete', [ProfileController::class, 'deleteDevice'])->name('totp.device.delete');
        Route::get('/auth/activity', [ActivityController::class, 'index'])->name('activity');
    });
});
