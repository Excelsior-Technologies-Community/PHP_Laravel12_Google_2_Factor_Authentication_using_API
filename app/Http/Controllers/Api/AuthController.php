<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuthenticationLog;
use App\Models\TrustedDevice;
use App\Models\User;
use App\Notifications\SecurityAlertNotification;
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
     * Register User
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
            'password_changed_at' => now(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->logAuthentication(
            $user,
            'Register',
            'Success',
            $request,
            'User registered successfully'
        );

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Login User
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'trust_days' => 'nullable|integer|min:1|max:90',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!Auth::attempt($request->only('email', 'password'))) {
            $this->logAuthentication(
                $user,
                'Login',
                'Failed',
                $request,
                'Failed login attempt with invalid credentials'
            );

            // Send Failed Login Alert Notification if user exists
            if ($user) {
                try {
                    $user->notify(new SecurityAlertNotification(
                        'failed_login',
                        'Failed Login Attempt Detected',
                        [
                            'Date & Time' => now()->toDateTimeString(),
                            'IP Address' => $request->ip(),
                            'Device' => $request->userAgent(),
                        ]
                    ));
                } catch (\Exception $e) {}
            }

            return response()->json([
<<<<<<< HEAD
                'message' => 'Invalid credentials'
=======
                'success' => false,
                'message' => 'Invalid credentials.'
            ], 401);
        }

        // Check if login is from a new device/IP
        $isNewDevice = $this->isNewDeviceOrLocation($user, $request);

<<<<<<< HEAD
        // Log successful login
=======
>>>>>>> development
        $this->logAuthentication(
            $user,
            'Login',
            'Success',
            $request,
            'User authenticated with credentials'
        );

        // Send New Device Login Alert if detected
        if ($isNewDevice) {
            try {
                $user->notify(new SecurityAlertNotification(
                    'new_device_login',
                    'New Device or Location Login Detected',
                    [
                        'Date & Time' => now()->toDateTimeString(),
                        'IP Address' => $request->ip(),
                        'Browser/Device' => $request->userAgent(),
                        'Platform' => PHP_OS,
                    ]
                ));
            } catch (\Exception $e) {}
        }

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
            // Check if device is trusted
            if ($this->isTrustedDevice($user)) {
                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'message' => 'Trusted device recognized. Login successful.',
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

            return response()->json([
                'success' => true,
                'message' => 'Two-factor authentication required.',
                'trusted_device' => false,
                'requires_2fa' => true,

                'user_id' => $user->id,

            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Standard Login (2FA Disabled)
        |--------------------------------------------------------------------------
        */
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'requires_2fa' => false,
            'user' => $user,
            'token' => $token,

        ]);
    }

    /**
     * Verify Google 2FA OTP
     */
    public function verify2FA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'code' => 'required|string',
            'remember_device' => 'nullable|boolean',
            'trust_days' => 'nullable|integer|min:1|max:90',
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

        if (! $valid) {

            $this->logAuthentication(
                $user,
                'Verify 2FA',
                'Failed',
                $request,
                'Invalid 2FA OTP entered'
            );

            // Send security alert
            try {
                $user->notify(new SecurityAlertNotification(
                    'failed_2fa',
                    'Failed 2FA Verification Attempt',
                    [
                        'Date & Time' => now()->toDateTimeString(),
                        'IP Address' => $request->ip(),
                        'Device' => $request->userAgent(),
                    ]
                ));
            } catch (\Exception $e) {}

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

        if ($request->boolean('remember_device')) {
            $this->saveTrustedDevice($user, $request);
        }

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
            'Google Authenticator verified successfully'
        );

>>>>>>> development
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => '2FA verified successfully.',
            'requires_2fa' => false,
            'trusted_device' => $isTrusted,
            'user' => $user,
            'token' => $token,
<<<<<<< HEAD
            'message' => '2FA verified successfully'
=======
>>>>>>> development
        ]);
    }

    /**
     * Logout User (Supports current device or all devices)
     */
    public function logout(Request $request)
    {
        $this->logAuthentication(
            $request->user(),
            'Logout',
            'Success',
            $request,
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
            'message' => 'Logged out successfully from current device.',
            'all_devices' => false,
        ]);
    }

    /**
     * Logout from all devices
     */
    public function logoutAll(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();

        $this->logAuthentication(
            $user,
            'Logout All',
            'Success',
            $request,
            'All Sanctum tokens revoked'
        );

        return response()->json([
            'success' => true,
            'message' => 'All device tokens revoked. Logged out from all devices successfully.'
        ]);
    }

    /**
     * Authentication History (with Filtering and Pagination)
     */
    public function authenticationHistory(Request $request)
    {
        $user = $request->user();

        $query = AuthenticationLog::where('user_id', $user->id);

        // Filter by Event
        if ($request->filled('event')) {
            $query->where('event', 'like', '%' . $request->event . '%');
        }

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by Date Range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search in description or IP
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhere('ip_address', 'like', '%' . $request->search . '%')
                  ->orWhere('browser', 'like', '%' . $request->search . '%');
            });
        }

        $perPage = min(100, max(5, (int)$request->input('per_page', 10)));
        $logs = $query->latest('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Authentication history fetched successfully.',
            'data' => $logs
        ]);
    }

    /**
     * Trusted Devices List
     */
    public function trustedDevices(Request $request)
    {
        $user = $request->user();
        $currentCookie = Cookie::get('trusted_device');

        $devices = TrustedDevice::where('user_id', $user->id)
            ->latest('last_used_at')
            ->get()
            ->map(function ($device) use ($currentCookie) {
                $isCurrent = ($currentCookie && $device->device_token === $currentCookie);
                $device->is_current = $isCurrent;
                $device->is_expired = $device->isExpired();
                $device->remaining_days = max(0, (int)now()->diffInDays($device->expires_at, false));
                return $device;
            });

        return response()->json([
            'success' => true,
            'message' => 'Trusted devices fetched successfully.',
            'data' => $devices
        ]);
    }

    /**
     * Remove Single Trusted Device (Immediately enforces 2FA on that device)
     */
    public function removeTrustedDevice(Request $request, $id)
    {
        $user = $request->user();

        $device = TrustedDevice::where('user_id', $user->id)->findOrFail($id);
        $deviceToken = $device->device_token;
        $device->delete();

        // Clear cookie if currently active on this browser
        if (Cookie::get('trusted_device') === $deviceToken) {
            Cookie::queue(Cookie::forget('trusted_device'));
        }

        $this->logAuthentication(
            $user,
            'Trusted Device Removed',
            'Success',
            $request,
            "Trusted device #{$id} revoked. 2FA is now enforced on that device."
        );

        return response()->json([
            'success' => true,
            'message' => 'Trusted device removed successfully. 2FA will be required on its next login.'
        ]);
    }

    /**
     * Remove All Trusted Devices
     */
    public function removeAllTrustedDevices(Request $request)
    {
        $user = $request->user();

        $count = TrustedDevice::where('user_id', $user->id)->delete();

        Cookie::queue(Cookie::forget('trusted_device'));

        $this->logAuthentication(
            $user,
            'All Trusted Devices Removed',
            'Success',
            $request,
            "All {$count} trusted devices revoked. 2FA is now enforced on all devices."
        );

        return response()->json([
            'success' => true,
            'message' => "All {$count} trusted devices removed successfully. 2FA will be required on all subsequent logins."
        ]);
    }

    /**
     * Security Dashboard (Comprehensive metrics & actionable recommendations)
     */
    public function securityDashboard(Request $request)
    {
        $user = $request->user();



        $totalLogins = AuthenticationLog::where(
            'user_id',
            $user->id
        )
            ->where('event', 'Login')
            ->where('status', 'Success')
            ->count();



        $failedLogins = AuthenticationLog::where(
            'user_id',
            $user->id
        )
            ->where('event', 'Login')
            ->where('status', 'Failed')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();



        $trustedDevices = TrustedDevice::where(
            'user_id',
            $user->id
        )
            ->count();



        $lastLogin = AuthenticationLog::where(
            'user_id',
            $user->id
        )
            ->where('event', 'Login')
            ->where('status', 'Success')
            ->latest('created_at')
            ->first();



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
     * Get User Profile
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
                'pending_email' => $user->pending_email,
                'two_factor_enabled' => (bool)$user->google2fa_enabled,
                'password_changed_at' => $user->password_changed_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ]);
    }

    /**
     * Update User Profile (Name only; Email requires confirmation)
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
            "Profile name updated from '{$oldName}' to '{$request->name}'"
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user' => $user
        ]);
    }

    /**
     * Request Email Change (Confirmation sent to current old email)
     */
    public function requestEmailChange(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'new_email' => 'required|email|max:255|unique:users,email',
            'current_password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.'
            ], 422);
        }

        if ($user->email === $request->new_email) {
            return response()->json([
                'success' => false,
                'message' => 'New email must be different from current email.'
            ], 422);
        }

        $code = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $user->pending_email = $request->new_email;
        $user->pending_email_token = Hash::make($code);
        $user->pending_email_expires_at = now()->addMinutes(30);
        $user->save();

        $this->logAuthentication(
            $user,
            'Email Change Requested',
            'Success',
            $request,
            "Requested email change to {$request->new_email}"
        );

        // Send Email Confirmation Notification to Current Email
        try {
            $user->notify(new SecurityAlertNotification(
                'email_change_request',
                'Email Change Confirmation Request',
                [
                    'Current Email' => $user->email,
                    'Requested New Email' => $request->new_email,
                    'Confirmation Code' => $code,
                    'Expires In' => '30 minutes',
                    'IP Address' => $request->ip(),
                ]
            ));
        } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Confirmation code sent to your current email address. Please submit it to /api/auth/confirm-email-change within 30 minutes.',
            'code_preview' => $code, // Provided for convenient local testing/demo
            'pending_email' => $request->new_email,
        ]);
    }

    /**
     * Confirm Email Change
     */
    public function confirmEmailChange(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!$user->pending_email || !$user->pending_email_token) {
            return response()->json([
                'success' => false,
                'message' => 'No pending email change request found.'
            ], 400);
        }

        if ($user->pending_email_expires_at && now()->isAfter($user->pending_email_expires_at)) {
            $user->pending_email = null;
            $user->pending_email_token = null;
            $user->pending_email_expires_at = null;
            $user->save();

            return response()->json([
                'success' => false,
                'message' => 'Confirmation code has expired. Please request email change again.'
            ], 400);
        }

        if (!Hash::check($request->code, $user->pending_email_token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid confirmation code.'
            ], 400);
        }

        $oldEmail = $user->email;
        $newEmail = $user->pending_email;

        $user->email = $newEmail;
        $user->pending_email = null;
        $user->pending_email_token = null;
        $user->pending_email_expires_at = null;
        $user->save();

        $this->logAuthentication(
            $user,
            'Email Changed',
            'Success',
            $request,
            "Email changed from {$oldEmail} to {$newEmail}"
        );

        // Send alert to new email
        try {
            $user->notify(new SecurityAlertNotification(
                'email_changed',
                'Your Account Email Address Has Been Updated',
                [
                    'New Email' => $newEmail,
                    'Previous Email' => $oldEmail,
                    'Date & Time' => now()->toDateTimeString(),
                    'IP Address' => $request->ip(),
                ]
            ));
        } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Email address updated successfully.',
            'user' => $user
        ]);
    }

    /**
     * Change Password
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

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
            'password_changed_at' => now(),
        ]);

        $this->logAuthentication(
            $user,
            'Password Change',
            'Success',
            $request,
            'User changed account password successfully'
        );

        // Send Email Alert
        try {
            $user->notify(new SecurityAlertNotification(
                'password_changed',
                'Your Account Password Has Been Changed',
                [
                    'Date & Time' => now()->toDateTimeString(),
                    'IP Address' => $request->ip(),
                    'Device' => $request->userAgent(),
                ]
            ));
        } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.'
        ]);
    }

    /**
     * Delete Account (Requires Password and 2FA confirmation)
     */
    public function deleteAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
            'code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // 1. Password confirmation
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect password. Account deletion aborted.'
            ], 422);
        }

        // 2. 2FA confirmation if enabled
        if ($user->google2fa_enabled) {
            if (!$request->filled('code')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Two-factor authentication code or backup recovery code is required to delete this account.'
                ], 422);
            }

            $valid = $this->google2fa->verifyKey($user->google2fa_secret, $request->code);
            if (!$valid) {
                // Check if recovery code
                $valid = $user->verifyAndConsumeRecoveryCode(trim(strtoupper($request->code)));
            }

            if (!$valid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid 2FA code or recovery code. Account deletion aborted.'
                ], 422);
            }
        }

        // 3. Log event before deletion
        $this->logAuthentication(
            $user,
            'Account Deleted',
            'Success',
            $request,
            "Account {$user->email} was permanently deleted by user"
        );

        // 4. Cleanup all user tokens and trusted devices
        $user->tokens()->delete();
        $user->trustedDevices()->delete();

        // 5. Clear trusted device cookie
        Cookie::queue(Cookie::forget('trusted_device'));

        // 6. Delete user record
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Your account and all associated tokens and trusted devices have been permanently deleted.'
        ]);
    }

    /**
     * Active Sessions (Sanctum Tokens and Trusted Devices)
     */
    public function activeSessions(Request $request)
    {
        $user = $request->user();
        $currentAccessTokenId = $user->currentAccessToken()?->id;

        $tokens = $user->tokens()->latest('created_at')->get()->map(function ($token) use ($currentAccessTokenId) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
                'is_current' => ($token->id === $currentAccessTokenId),
            ];
        });

        $trustedDevices = TrustedDevice::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->latest('last_used_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Active sessions fetched successfully.',

            'data' => $sessions

        ]);
    }
}
