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
    private $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    // Register
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Registration successful'
        ], 201);
    }

    // Login
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
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
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = User::where('email', $request->email)->first();

        // Log successful login
        $this->logAuthentication(
            $user,
            'Login',
            'Success',
            $request,
            'User logged in successfully'
        );

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

            return response()->json([
                'message' => '2FA verification required',
                'requires_2fa' => true,
                'user_id' => $user->id,
                'trusted_device' => false
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'requires_2fa' => false
        ]);
    }

    // Verify 2FA
    public function verify2FA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'code' => 'required|string',
            'remember_device' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::find($request->user_id);

        $valid = $this->google2fa->verifyKey(
            $user->google2fa_secret,
            $request->code
        );

        if (!$valid) {

            $this->logAuthentication(
                $user,
                'Verify 2FA',
                'Failed',
                $request,
                'Invalid OTP entered'
            );

            return response()->json([
                'message' => 'Invalid 2FA code'
            ], 401);
        }

        // Save trusted device
        if ($request->boolean('remember_device')) {
            $this->saveTrustedDevice($user, $request);
        }

        $this->logAuthentication(
            $user,
            'Verify 2FA',
            'Success',
            $request,
            'Google Authenticator verified'
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => '2FA verified successfully'
        ]);
    }

    // Logout
    public function logout(Request $request)
    {
        $this->logAuthentication(
            $request->user(),
            'Logout',
            'Success',
            $request,
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
            ->where('event', 'Login')
            ->where('status', 'Success')
            ->count();

        $failedLogins = AuthenticationLog::where('user_id', $user->id)
            ->where('event', 'Login')
            ->where('status', 'Failed')
            ->count();

        $trustedDevices = TrustedDevice::where('user_id', $user->id)
            ->count();

        $lastLogin = AuthenticationLog::where('user_id', $user->id)
            ->where('event', 'Login')
            ->where('status', 'Success')
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'two_factor_enabled' => $user->google2fa_enabled,
                'trusted_devices' => $trustedDevices,
                'successful_logins' => $totalLogins,
                'failed_logins' => $failedLogins,
                'last_login' => $lastLogin,
            ]
        ]);
    }
}
