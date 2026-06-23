<?php

use App\Modules\CustomerAuth\Controllers\CustomerAuthController;
use App\Modules\CustomerAuth\Controllers\CustomerOtpController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/customer')->group(function () {
    Route::prefix('otp')->group(function () {
        Route::post('sms', [CustomerOtpController::class, 'sendSms']);
        Route::post('email', [CustomerOtpController::class, 'sendEmail']);
        Route::post('verify', [CustomerOtpController::class, 'verify']);
    });

    Route::post('auth/register', [CustomerAuthController::class, 'register']);

    Route::middleware('customer.jwt')->group(function () {
        Route::get('profile', [CustomerAuthController::class, 'profile']);
        Route::patch('profile/complete', [CustomerAuthController::class, 'completeProfile']);
    });
});
