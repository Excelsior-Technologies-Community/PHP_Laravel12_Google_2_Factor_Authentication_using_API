<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TwoFactorAuthController;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-2fa', [AuthController::class, 'verify2FA']);
    Route::post('/recover-2fa', [AuthController::class, 'verifyRecovery']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // 2FA routes
    Route::prefix('2fa')->group(function () {
        Route::post('/generate', [TwoFactorAuthController::class, 'generate2FASecret']);
        Route::post('/enable', [TwoFactorAuthController::class, 'enable2FA']);
        Route::post('/disable', [TwoFactorAuthController::class, 'disable2FA']);
        Route::get('/status', [TwoFactorAuthController::class, 'get2FAStatus']);
    });
});