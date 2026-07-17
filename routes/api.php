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
<<<<<<< HEAD
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-2fa', [AuthController::class, 'verify2FA']);
    Route::post('/recover-2fa', [TwoFactorAuthController::class, 'verifyRecovery']);
=======


    // Register
    Route::post(
        '/register',
        [AuthController::class, 'register']
    );


    // Login
    Route::post(
        '/login',
        [AuthController::class, 'login']
    );


    // Verify Google 2FA
    Route::post(
        '/verify-2fa',
        [AuthController::class, 'verify2FA']
    );


    // Recover 2FA using recovery code
    Route::post(
        '/recover-2fa',
        [TwoFactorAuthController::class, 'verifyRecovery']
    );
>>>>>>> development
});



/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

<<<<<<< HEAD
    // Auth routes
    Route::prefix('auth')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);

        // Authentication History
        Route::get('/history', [AuthController::class, 'authenticationHistory']);

        // Trusted Devices
        Route::get('/trusted-devices', [AuthController::class, 'trustedDevices']);

        // Remove Single Trusted Device
        Route::delete('/trusted-devices/{id}', [AuthController::class, 'removeTrustedDevice']);

        // Remove All Trusted Devices
        Route::delete('/trusted-devices', [AuthController::class, 'removeAllTrustedDevices']);

        // Security Dashboard
        Route::get('/security-dashboard', [AuthController::class, 'securityDashboard']);
=======

    /*
    |--------------------------------------------------------------------------
    | Authentication Management
    |--------------------------------------------------------------------------
    */

    Route::prefix('auth')->group(function () {


        // Logout
        Route::post(
            '/logout',
            [AuthController::class, 'logout']
        );



        // Authentication History
        Route::get(
            '/history',
            [AuthController::class, 'authenticationHistory']
        );



        // Trusted Devices List
        Route::get(
            '/trusted-devices',
            [AuthController::class, 'trustedDevices']
        );



        // Remove Single Trusted Device
        Route::delete(
            '/trusted-devices/{id}',
            [AuthController::class, 'removeTrustedDevice']
        );



        // Remove All Trusted Devices
        Route::delete(
            '/trusted-devices',
            [AuthController::class, 'removeAllTrustedDevices']
        );



        // Security Dashboard
        Route::get(
            '/security-dashboard',
            [AuthController::class, 'securityDashboard']
        );



        /*
        |--------------------------------------------------------------------------
        | New Added Features
        |--------------------------------------------------------------------------
        */


        // User Profile
        Route::get(
            '/profile',
            [AuthController::class, 'profile']
        );



        // Update Profile
        Route::put(
            '/profile',
            [AuthController::class, 'updateProfile']
        );



        // Change Password
        Route::post(
            '/change-password',
            [AuthController::class, 'changePassword']
        );



        // Delete Account
        Route::delete(
            '/delete-account',
            [AuthController::class, 'deleteAccount']
        );



        // Active Sessions
        Route::get(
            '/active-sessions',
            [AuthController::class, 'activeSessions']
        );
>>>>>>> development
    });




    /*
    |--------------------------------------------------------------------------
    | Google 2FA Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('2fa')->group(function () {

<<<<<<< HEAD
        Route::post('/generate', [TwoFactorAuthController::class, 'generate2FASecret']);

        Route::post('/enable', [TwoFactorAuthController::class, 'enable2FA']);

        Route::post('/disable', [TwoFactorAuthController::class, 'disable2FA']);

        Route::get('/status', [TwoFactorAuthController::class, 'get2FAStatus']);
=======

        // Generate Secret + QR
        Route::post(
            '/generate',
            [TwoFactorAuthController::class, 'generate2FASecret']
        );



        // Enable 2FA
        Route::post(
            '/enable',
            [TwoFactorAuthController::class, 'enable2FA']
        );



        // Disable 2FA
        Route::post(
            '/disable',
            [TwoFactorAuthController::class, 'disable2FA']
        );



        // Check 2FA Status
        Route::get(
            '/status',
            [TwoFactorAuthController::class, 'get2FAStatus']
        );
>>>>>>> development
    });
});
