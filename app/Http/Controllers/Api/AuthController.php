<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuthenticationLog;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;


class AuthController extends Controller
{
    /**
     * Google2FA Instance
     */
    private $google2fa;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * ===========================================
     * Register User
     * ===========================================
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'name' => 'required|string|max:255',

            'email' => 'required|string|email|max:255|unique:users',

            'password' => 'required|string|min:8',

        ]);

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([

            'name' => $request->name,

            'email' => $request->email,

            'password' => Hash::make($request->password),

        ]);

        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;

        AuthenticationLog::create([

            'user_id' => $user->id,

            'event' => 'Register',

            'status' => 'Success',

            'ip_address' => $request->ip(),

            'browser' => $request->userAgent(),

            'platform' => PHP_OS,

            'description' => 'User registered successfully',

        ]);

        return response()->json([

            'success' => true,

            'message' => 'Registration successful.',

            'user' => $user,

            'token' => $token,

        ], 201);
    }

    /**
     * ===========================================
     * Login User
     * ===========================================
     */

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'email'    => 'required|email',

            'password' => 'required',

        ]);

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {

            $this->logAuthentication(
                null,
                'Login',
                'Failed',
                $request,
                'Invalid email or password'
            );

            return response()->json([
<<<<<<< HEAD
                'message' => 'Invalid credentials'
=======
                'success' => false,
                'message' => 'Invalid credentials.'
>>>>>>> development
            ], 401);
        }

        $user = User::where('email', $request->email)->first();

<<<<<<< HEAD
        // Log successful login
=======
>>>>>>> development
        $this->logAuthentication(
            $user,
            'Login',
            'Success',
            $request,
            'User logged in successfully'
        );

<<<<<<< HEAD
        // Skip OTP if trusted device
        if ($user->google2fa_enabled) {

            if ($this->isTrustedDevice($user)) {

                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'user' => $user,
                    'token' => $token,
                    'requires_2fa' => false,
                    'trusted_device' => true,
                    'message' => 'Trusted device detected. Login successful.'
                ]);
            }

=======
        /*
        |--------------------------------------------------------------------------
        | Google 2FA Enabled
        |--------------------------------------------------------------------------
        */

        if ($user->google2fa_enabled) {

            /*
            |--------------------------------------------------------------------------
            | Trusted Device Found
            |--------------------------------------------------------------------------
            */

            if ($this->isTrustedDevice($user)) {

                $token = $user
                    ->createToken('auth_token')
                    ->plainTextToken;

                return response()->json([

                    'success' => true,

                    'message' => 'Trusted device detected. Login successful.',

                    'trusted_device' => true,

                    'requires_2fa' => false,

                    'user' => $user,

                    'token' => $token,

                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | OTP Required
            |--------------------------------------------------------------------------
            */

>>>>>>> development
            return response()->json([

                'success' => true,

                'message' => '2FA verification required.',

                'trusted_device' => false,

                'requires_2fa' => true,
<<<<<<< HEAD
                'user_id' => $user->id,
                'trusted_device' => false
=======

                'user_id' => $user->id,

>>>>>>> development
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Normal Login
        |--------------------------------------------------------------------------
        */

        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;

        return response()->json([

            'success' => true,

            'message' => 'Login successful.',

            'requires_2fa' => false,

            'user' => $user,

            'token' => $token,
<<<<<<< HEAD
            'requires_2fa' => false
=======

>>>>>>> development
        ]);
    }

    /**
     * ===========================================
     * Verify Google 2FA
     * ===========================================
     */

    public function verify2FA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'code' => 'required|string',
            'remember_device' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
<<<<<<< HEAD
                'errors' => $validator->errors()
=======
                'success' => false,
                'errors' => $validator->errors(),
>>>>>>> development
            ], 422);
        }

        $user = User::find($request->user_id);

        $valid = $this->google2fa->verifyKey(
            $user->google2fa_secret,
            $request->code
        );

<<<<<<< HEAD
        if (!$valid) {
=======
        if (! $valid) {
>>>>>>> development

            $this->logAuthentication(
                $user,
                'Verify 2FA',
                'Failed',
                $request,
                'Invalid OTP entered'
            );

            return response()->json([
<<<<<<< HEAD
                'message' => 'Invalid 2FA code'
            ], 401);
        }

        // Save trusted device
=======
                'success' => false,
                'message' => 'Invalid 2FA verification code.',
            ], 401);
        }

        /*
    |--------------------------------------------------------------------------
    | Remember Trusted Device
    |--------------------------------------------------------------------------
    */

>>>>>>> development
        if ($request->boolean('remember_device')) {
            $this->saveTrustedDevice($user, $request);
        }

<<<<<<< HEAD
=======
        /*
    |--------------------------------------------------------------------------
    | Log Success
    |--------------------------------------------------------------------------
    */

>>>>>>> development
        $this->logAuthentication(
            $user,
            'Verify 2FA',
            'Success',
            $request,
<<<<<<< HEAD
            'Google Authenticator verified'
        );

=======
            'Google Authenticator verified successfully'
        );

        /*
    |--------------------------------------------------------------------------
    | Create Sanctum Token
    |--------------------------------------------------------------------------
    */

>>>>>>> development
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => '2FA verified successfully.',
            'requires_2fa' => false,
            'trusted_device' => $request->boolean('remember_device'),
            'user' => $user,
            'token' => $token,
<<<<<<< HEAD
            'message' => '2FA verified successfully'
=======
>>>>>>> development
        ]);
    }

    /**
     * ===========================================
     * Logout User
     * ===========================================
     */
    public function logout(Request $request)
    {
        $this->logAuthentication(
            $request->user(),
            'Logout',
            'Success',
            $request,
<<<<<<< HEAD
            'User logged out'
        );

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

private function logAuthentication(
    ?User $user,
    string $event,
    string $status,
    Request $request,
    string $description
): void {

    AuthenticationLog::create([
        'user_id' => optional($user)->id,
        'event' => $event,
        'status' => $status,
        'ip_address' => $request->ip(),
        'browser' => $request->userAgent(),
        'platform' => PHP_OS,
        'description' => $description,
    ]);
}

    private function isTrustedDevice(User $user): bool
    {
        $token = Cookie::get('trusted_device');

        if (!$token) {
            return false;
        }

        $device = TrustedDevice::where('user_id', $user->id)
            ->where('device_token', $token)
            ->first();

        if (!$device) {
            return false;
        }

        if ($device->isExpired()) {

            $device->delete();

            return false;
        }

        $device->markAsUsed();

        return true;
    }

  private function saveTrustedDevice(User $user, Request $request): void
{
    $token = Str::random(80);

    TrustedDevice::create([
        'user_id' => $user->id,
        'device_token' => $token,
        'device_name' => $request->userAgent(),
        'browser' => $request->userAgent(),
        'platform' => PHP_OS,
        'ip_address' => $request->ip(),
        'last_used_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);

    Cookie::queue(
        Cookie::make(
            'trusted_device',
            $token,
            60 * 24 * 30 // 30 days
        )
    );
}

    // Authentication History
    public function authenticationHistory(Request $request)
    {
        $logs = AuthenticationLog::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Authentication history fetched successfully.',
            'data' => $logs
        ]);
    }

    // Trusted Devices
    public function trustedDevices(Request $request)
    {
        $devices = TrustedDevice::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Trusted devices fetched successfully.',
            'data' => $devices
        ]);
    }

    // Remove Single Trusted Device
    public function removeTrustedDevice(Request $request, $id)
    {
        $device = TrustedDevice::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $device->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trusted device removed successfully.'
        ]);
    }

    // Remove All Trusted Devices
    public function removeAllTrustedDevices(Request $request)
    {
        TrustedDevice::where('user_id', $request->user()->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'All trusted devices removed successfully.'
        ]);
    }

    // Security Dashboard
    public function securityDashboard(Request $request)
    {
        $user = $request->user();

        $totalLogins = AuthenticationLog::where('user_id', $user->id)
=======
            'User logged out successfully'
        );


        // Delete current token
        if ($request->user()->currentAccessToken()) {

            $request->user()
                ->currentAccessToken()
                ->delete();
        }


        return response()->json([

            'success' => true,

            'message' => 'Logged out successfully.'

        ]);
    }



    /**
     * ===========================================
     * Authentication Log Helper
     * ===========================================
     */
    private function logAuthentication(
        ?User $user,
        string $event,
        string $status,
        Request $request,
        string $description
    ): void {

        AuthenticationLog::create([

            'user_id' => optional($user)->id,

            'event' => $event,

            'status' => $status,

            'ip_address' => $request->ip(),

            'browser' => $request->userAgent(),

            'platform' => PHP_OS,

            'description' => $description,

        ]);
    }



    /**
     * ===========================================
     * Check Trusted Device
     * ===========================================
     */
    private function isTrustedDevice(User $user): bool
    {

        $token = Cookie::get('trusted_device');


        if (!$token) {

            return false;
        }


        $device = TrustedDevice::where('user_id', $user->id)

            ->where('device_token', $token)

            ->first();



        if (!$device) {

            return false;
        }



        // Remove expired device

        if ($device->isExpired()) {


            $device->delete();


            return false;
        }



        // Update last used time

        $device->markAsUsed();



        return true;
    }

    /**
     * ===========================================
     * Save Trusted Device
     * ===========================================
     */
    private function saveTrustedDevice(
        User $user,
        Request $request
    ): void {

        $token = Str::random(80);


        TrustedDevice::create([

            'user_id' => $user->id,

            'device_token' => $token,

            'device_name' => $request->userAgent(),

            'browser' => $request->userAgent(),

            'platform' => PHP_OS,

            'ip_address' => $request->ip(),

            'last_used_at' => now(),

            'expires_at' => now()->addDays(30),

        ]);



        Cookie::queue(

            Cookie::make(

                'trusted_device',

                $token,

                60 * 24 * 30

            )

        );
    }



    /**
     * ===========================================
     * Authentication History
     * ===========================================
     */
    public function authenticationHistory(Request $request)
    {

        $logs = AuthenticationLog::where(
            'user_id',
            $request->user()->id
        )
            ->oldest()
            ->paginate(10);



        return response()->json([

            'success' => true,

            'message' => 'Authentication history fetched successfully.',

            'data' => $logs

        ]);
    }



    /**
     * ===========================================
     * Trusted Devices List
     * ===========================================
     */
    public function trustedDevices(Request $request)
    {

        $devices = TrustedDevice::where(
            'user_id',
            $request->user()->id
        )
            ->latest()
            ->get();



        return response()->json([

            'success' => true,

            'message' => 'Trusted devices fetched successfully.',

            'data' => $devices

        ]);
    }

    /**
     * ===========================================
     * Remove Single Trusted Device
     * ===========================================
     */
    public function removeTrustedDevice(
        Request $request,
        $id
    ) {

        $device = TrustedDevice::where(
            'user_id',
            $request->user()->id
        )
            ->findOrFail($id);



        $device->delete();



        return response()->json([

            'success' => true,

            'message' => 'Trusted device removed successfully.'

        ]);
    }



    /**
     * ===========================================
     * Remove All Trusted Devices
     * ===========================================
     */
    public function removeAllTrustedDevices(Request $request)
    {

        TrustedDevice::where(
            'user_id',
            $request->user()->id
        )
            ->delete();



        return response()->json([

            'success' => true,

            'message' => 'All trusted devices removed successfully.'

        ]);
    }



    /**
     * ===========================================
     * Security Dashboard
     * ===========================================
     */
    public function securityDashboard(Request $request)
    {

        $user = $request->user();



        $totalLogins = AuthenticationLog::where(
            'user_id',
            $user->id
        )
>>>>>>> development
            ->where('event', 'Login')
            ->where('status', 'Success')
            ->count();

<<<<<<< HEAD
        $failedLogins = AuthenticationLog::where('user_id', $user->id)
=======


        $failedLogins = AuthenticationLog::where(
            'user_id',
            $user->id
        )
>>>>>>> development
            ->where('event', 'Login')
            ->where('status', 'Failed')
            ->count();

<<<<<<< HEAD
        $trustedDevices = TrustedDevice::where('user_id', $user->id)
            ->count();

        $lastLogin = AuthenticationLog::where('user_id', $user->id)
=======


        $trustedDevices = TrustedDevice::where(
            'user_id',
            $user->id
        )
            ->count();



        $lastLogin = AuthenticationLog::where(
            'user_id',
            $user->id
        )
>>>>>>> development
            ->where('event', 'Login')
            ->where('status', 'Success')
            ->latest()
            ->first();

<<<<<<< HEAD
        return response()->json([
            'success' => true,
            'data' => [
                'two_factor_enabled' => $user->google2fa_enabled,
                'trusted_devices' => $trustedDevices,
                'successful_logins' => $totalLogins,
                'failed_logins' => $failedLogins,
                'last_login' => $lastLogin,
            ]
=======


        return response()->json([

            'success' => true,

            'data' => [

                'user_name' => $user->name,

                'email' => $user->email,

                'two_factor_enabled' =>
                $user->google2fa_enabled,

                'trusted_devices' =>
                $trustedDevices,

                'successful_logins' =>
                $totalLogins,

                'failed_logins' =>
                $failedLogins,

                'last_login' =>
                $lastLogin

            ]

        ]);
    }

    /**
     * ===========================================
     * Get User Profile
     * ===========================================
     */
    public function profile(Request $request)
    {

        $user = $request->user();


        return response()->json([

            'success' => true,

            'message' => 'Profile fetched successfully.',

            'data' => [

                'id' => $user->id,

                'name' => $user->name,

                'email' => $user->email,

                'two_factor_enabled' =>
                $user->google2fa_enabled,

                'created_at' =>
                $user->created_at,

                'updated_at' =>
                $user->updated_at,

            ]

        ]);
    }



    /**
     * ===========================================
     * Update User Profile
     * ===========================================
     */
    public function updateProfile(Request $request)
    {

        $validator = Validator::make($request->all(), [

            'name' => 'required|string|max:255',

        ]);



        if ($validator->fails()) {

            return response()->json([

                'success' => false,

                'errors' => $validator->errors()

            ], 422);
        }



        $user = $request->user();



        $oldName = $user->name;



        $user->update([

            'name' => $request->name

        ]);



        $this->logAuthentication(

            $user,

            'Profile Update',

            'Success',

            $request,

            "Profile name changed from {$oldName} to {$request->name}"

        );



        return response()->json([

            'success' => true,

            'message' => 'Profile updated successfully.',

            'user' => $user

        ]);
    }

    /**
     * ===========================================
     * Change Password
     * ===========================================
     */
    public function changePassword(Request $request)
    {

        $validator = Validator::make($request->all(), [

            'current_password' => 'required',

            'new_password' => 'required|min:8|confirmed',

        ]);



        if ($validator->fails()) {

            return response()->json([

                'success' => false,

                'errors' => $validator->errors()

            ], 422);
        }



        $user = $request->user();



        if (!Hash::check(
            $request->current_password,
            $user->password
        )) {


            return response()->json([

                'success' => false,

                'message' => 'Current password is incorrect.'

            ], 422);
        }



        $user->update([

            'password' => Hash::make(
                $request->new_password
            )

        ]);



        $this->logAuthentication(

            $user,

            'Password Change',

            'Success',

            $request,

            'User changed password successfully'

        );



        return response()->json([

            'success' => true,

            'message' => 'Password changed successfully.'

        ]);
    }





    /**
     * ===========================================
     * Delete Account
     * ===========================================
     */
    public function deleteAccount(Request $request)
    {

        $user = $request->user();



        // Delete authentication logs

        AuthenticationLog::where(
            'user_id',
            $user->id
        )->delete();



        // Delete trusted devices

        TrustedDevice::where(
            'user_id',
            $user->id
        )->delete();



        // Delete all API tokens

        $user->tokens()->delete();



        // Delete user

        $user->delete();



        return response()->json([

            'success' => true,

            'message' => 'Account deleted successfully.'

        ]);
    }





    /**
     * ===========================================
     * Active Sessions
     * ===========================================
     */
    public function activeSessions(Request $request)
    {

        $sessions = TrustedDevice::where(

            'user_id',

            $request->user()->id

        )
            ->oldest()
            ->get();



        return response()->json([

            'success' => true,

            'message' => 'Active sessions fetched successfully.',

            'data' => $sessions

>>>>>>> development
        ]);
    }
}
