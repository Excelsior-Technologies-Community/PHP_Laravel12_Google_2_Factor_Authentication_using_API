<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuthenticationLog;
use App\Models\User;
use App\Notifications\SecurityAlertNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackend;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorAuthController extends Controller
{
    private $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate 2FA Secret + QR Code + Setup Flow
     */
    public function generate2FASecret(Request $request)
    {
        $user = Auth::user();

        // Generate new secret key
        $secretKey = $this->google2fa->generateSecretKey();

        // Save secret temporarily
        $user->google2fa_secret = $secretKey;
        $user->save();

        // Format secret key for easy manual entry (grouped by 4 chars)
        $formattedSecret = trim(chunk_split($secretKey, 4, ' '));

        // Generate QR code URL
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secretKey
        );

        // Generate QR code SVG
        $renderer = new ImageRenderer(
            new RendererStyle(300),
            new SvgImageBackend()
        );

        $writer = new Writer($renderer);
        $qrCodeSvg = $writer->writeString($qrCodeUrl);

        return response()->json([
            'success' => true,
            'message' => '2FA setup initialized successfully.',
            'secret' => $secretKey,
            'manual_entry_key' => $formattedSecret,
            'qr_code_url' => $qrCodeUrl,
            'qr_code_svg' => $qrCodeSvg,
            'setup_steps' => [
                'step_1' => 'Install Google Authenticator, Authy, or any TOTP app on your device.',
                'step_2' => 'Scan the QR code or enter the manual secret key into your authenticator app.',
                'step_3' => 'Enter the 6-digit verification code from the app into the enable endpoint (/api/2fa/enable) to activate 2FA.',
                'step_4' => 'Save your one-time backup recovery codes and confirm receipt.',
            ]
        ]);
    }

    /**
     * Enable 2FA
     */
    public function enable2FA(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = Auth::user();
        $secretKey = $user->google2fa_secret;

        if (!$secretKey) {
            return response()->json([
                'success' => false,
                'message' => '2FA secret not found. Please generate 2FA secret first.'
            ], 400);
        }

        $valid = $this->google2fa->verifyKey(
            $secretKey,
            $request->code
        );

        if (!$valid) {
            AuthenticationLog::create([
                'user_id' => $user->id,
                'event' => '2FA Enable',
                'status' => 'Failed',
                'ip_address' => $request->ip(),
                'browser' => $request->userAgent(),
                'platform' => PHP_OS,
                'description' => 'Failed 2FA verification code during enrollment',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code. Please check your authenticator app and try again.'
            ], 400);
        }

        $user->enableTwoFactorAuth();

        // Generate 8 backup recovery codes
        $recoveryCodes = $user->generateAndStoreRecoveryCodes();

        AuthenticationLog::create([
            'user_id' => $user->id,
            'event' => '2FA Enable',
            'status' => 'Success',
            'ip_address' => $request->ip(),
            'browser' => $request->userAgent(),
            'platform' => PHP_OS,
            'description' => 'Two-factor authentication enabled successfully',
        ]);

        // Send Email Alert
        try {
            $user->notify(new SecurityAlertNotification(
                '2fa_enabled',
                'Two-Factor Authentication Has Been Enabled',
                [
                    'Date & Time' => now()->toDateTimeString(),
                    'IP Address' => $request->ip(),
                    'Device' => $request->userAgent(),
                ]
            ));
        } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'message' => '2FA enabled successfully.',
            'recovery_codes' => $recoveryCodes,
            'notice' => 'IMPORTANT: Store these 8 backup recovery codes in a secure location. Each code can be used once. Please confirm by calling /api/2fa/confirm-recovery-codes.',
            'confirmation_required' => true,
            'user' => $user
        ]);
    }

    /**
     * Confirm Saved Recovery Codes
     */
    public function confirmRecoveryCodes(Request $request)
    {
        $request->validate([
            'confirmed' => 'required|boolean',
        ]);

        $user = Auth::user();

        if (!$user->google2fa_enabled) {
            return response()->json([
                'success' => false,
                'message' => '2FA is not enabled on this account.'
            ], 400);
        }

        $user->recovery_codes_confirmed_at = now();
        $user->save();

        AuthenticationLog::create([
            'user_id' => $user->id,
            'event' => 'Recovery Codes Confirmed',
            'status' => 'Success',
            'ip_address' => $request->ip(),
            'browser' => $request->userAgent(),
            'platform' => PHP_OS,
            'description' => 'User confirmed saving 2FA recovery backup codes',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Recovery codes confirmed as saved securely.',
            'recovery_codes_confirmed_at' => $user->recovery_codes_confirmed_at,
        ]);
    }

    /**
     * Disable 2FA
     */
    public function disable2FA(Request $request)
    {
        $request->validate([
            'password' => 'required_without:code|string',
            'code' => 'required_without:password|string',
        ]);

        $user = Auth::user();

        if ($request->filled('password')) {
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incorrect password provided.'
                ], 422);
            }
        } elseif ($request->filled('code')) {
            $valid = $this->google2fa->verifyKey($user->google2fa_secret, $request->code);
            if (!$valid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid 2FA verification code.'
                ], 422);
            }
        }

        $user->disableTwoFactorAuth();

        AuthenticationLog::create([
            'user_id' => $user->id,
            'event' => '2FA Disable',
            'status' => 'Success',
            'ip_address' => $request->ip(),
            'browser' => $request->userAgent(),
            'platform' => PHP_OS,
            'description' => 'Two-factor authentication disabled',
        ]);

        // Send Email Alert
        try {
            $user->notify(new SecurityAlertNotification(
                '2fa_disabled',
                'Two-Factor Authentication Has Been Disabled',
                [
                    'Date & Time' => now()->toDateTimeString(),
                    'IP Address' => $request->ip(),
                    'Device' => $request->userAgent(),
                ]
            ));
        } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'message' => '2FA disabled successfully.',
            'user' => $user
        ]);
    }

    /**
     * Get 2FA Status
     */
    public function get2FAStatus()
    {
        $user = Auth::user();
        $remainingCodes = count($user->two_factor_recovery_codes ?? []);

        return response()->json([
            'success' => true,
            'google2fa_enabled' => (bool)$user->google2fa_enabled,
            'has_secret' => !empty($user->google2fa_secret),
            'recovery_codes_confirmed' => !is_null($user->recovery_codes_confirmed_at),
            'recovery_codes_remaining' => $remainingCodes,
        ]);
    }

    /**
     * Recover 2FA using a Backup Recovery Code
     */
    public function verifyRecovery(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'recovery_code' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No user account found with this email address.'
            ], 404);
        }

        if (!$user->google2fa_enabled) {
            return response()->json([
                'success' => false,
                'message' => '2FA is not enabled on this account.'
            ], 400);
        }

        $code = trim(strtoupper($request->recovery_code));
        $valid = $user->verifyAndConsumeRecoveryCode($code);

        if (!$valid) {
            AuthenticationLog::create([
                'user_id' => $user->id,
                'event' => 'Recovery Code Login',
                'status' => 'Failed',
                'ip_address' => $request->ip(),
                'browser' => $request->userAgent(),
                'platform' => PHP_OS,
                'description' => 'Invalid recovery code attempt',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid or already used backup recovery code.'
            ], 400);
        }

        // Generate Sanctum access token
        $token = $user->createToken('auth_token')->plainTextToken;

        $remainingCodes = count($user->two_factor_recovery_codes ?? []);

        AuthenticationLog::create([
            'user_id' => $user->id,
            'event' => 'Recovery Code Login',
            'status' => 'Success',
            'ip_address' => $request->ip(),
            'browser' => $request->userAgent(),
            'platform' => PHP_OS,
            'description' => "Logged in using recovery code. Remaining codes: {$remainingCodes}",
        ]);

        // Send alert
        try {
            $user->notify(new SecurityAlertNotification(
                'recovery_code_used',
                'A 2FA Backup Recovery Code Was Used',
                [
                    'Remaining Backup Codes' => $remainingCodes,
                    'Date & Time' => now()->toDateTimeString(),
                    'IP Address' => $request->ip(),
                    'Device' => $request->userAgent(),
                ]
            ));
        } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Successfully authenticated using backup recovery code.',
            'token' => $token,
            'remaining_recovery_codes' => $remainingCodes,
            'user' => $user
        ]);
    }
}