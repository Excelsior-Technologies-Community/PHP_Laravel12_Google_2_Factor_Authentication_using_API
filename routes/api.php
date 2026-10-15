<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TwoFactorAuthController;

/*
|--------------------------------------------------------------------------
| Public Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {

    // Register
    Route::post('/register', [AuthController::class, 'register']);

    // Login (supports optional trust_days)
    Route::post('/login', [AuthController::class, 'login']);

    // Verify Google 2FA (supports remember_device and trust_days)
    Route::post('/verify-2fa', [AuthController::class, 'verify2FA']);

    // Recover 2FA using backup recovery code
    Route::post('/recover-2fa', [TwoFactorAuthController::class, 'verifyRecovery']);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Sanctum Authenticated)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Authentication & Profile Management
    |--------------------------------------------------------------------------
    */

    Route::prefix('auth')->group(function () {

        // Logout (supports current device or ?all_devices=true)
        Route::post('/logout', [AuthController::class, 'logout']);

        // Logout from all devices
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);

        // Authentication History (Filtered & Paginated)
        Route::get('/history', [AuthController::class, 'authenticationHistory']);

        // Trusted Devices List
        Route::get('/trusted-devices', [AuthController::class, 'trustedDevices']);

        // Remove Single Trusted Device (immediately enforces 2FA)
        Route::delete('/trusted-devices/{id}', [AuthController::class, 'removeTrustedDevice']);

        // Remove All Trusted Devices
        Route::delete('/trusted-devices', [AuthController::class, 'removeAllTrustedDevices']);

        // Security Dashboard (2FA, Last Login, Failed Attempts, Devices, Sessions, Recommendations)
        Route::get('/security-dashboard', [AuthController::class, 'securityDashboard']);

        // User Profile
        Route::get('/profile', [AuthController::class, 'profile']);

        // Update Profile Name
        Route::put('/profile', [AuthController::class, 'updateProfile']);

        // Request Email Change (Confirmation code sent to old email)
        Route::post('/request-email-change', [AuthController::class, 'requestEmailChange']);

        // Confirm Email Change
        Route::post('/confirm-email-change', [AuthController::class, 'confirmEmailChange']);

        // Change Password
        Route::post('/change-password', [AuthController::class, 'changePassword']);

        // Delete Account (Requires Password + 2FA code confirmation)
        Route::delete('/delete-account', [AuthController::class, 'deleteAccount']);

        // Active Sessions & Tokens
        Route::get('/active-sessions', [AuthController::class, 'activeSessions']);
    });

    /*
    |--------------------------------------------------------------------------
    | Google 2FA Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('2fa')->group(function () {

        // Generate Secret + QR + Setup Steps
        Route::post('/generate', [TwoFactorAuthController::class, 'generate2FASecret']);

        // Enable 2FA (Returns 8 Backup Recovery Codes)
        Route::post('/enable', [TwoFactorAuthController::class, 'enable2FA']);

        // Confirm Recovery Codes Saved
        Route::post('/confirm-recovery-codes', [TwoFactorAuthController::class, 'confirmRecoveryCodes']);

        // Disable 2FA (Requires Password or 2FA code)
        Route::post('/disable', [TwoFactorAuthController::class, 'disable2FA']);

        // Check 2FA Status & Recovery Codes
        Route::get('/status', [TwoFactorAuthController::class, 'get2FAStatus']);
    });
});
