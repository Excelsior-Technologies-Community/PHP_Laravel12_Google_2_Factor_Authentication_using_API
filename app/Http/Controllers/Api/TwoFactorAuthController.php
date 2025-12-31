<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    // Generate 2FA secret and QR code
    public function generate2FASecret(Request $request)
    {
        $user = Auth::user();

        // Generate new secret key
        $secretKey = $this->google2fa->generateSecretKey();

        // Save secret temporarily
        $user->google2fa_secret = $secretKey;
        $user->save();

        // Generate QR code URL
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secretKey
        );

        // Generate QR code image
        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new SvgImageBackend()
        );
        
        $writer = new Writer($renderer);
        $qrCodeSvg = $writer->writeString($qrCodeUrl);

        return response()->json([
            'secret' => $secretKey,
            'qr_code_url' => $qrCodeUrl,
            'qr_code_svg' => $qrCodeSvg,
            'message' => '2FA secret generated successfully'
        ]);
    }

    // Enable 2FA
    public function enable2FA(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = Auth::user();
        $secretKey = $user->google2fa_secret;

        $valid = $this->google2fa->verifyKey(
            $secretKey,
            $request->code
        );

        if (!$valid) {
            return response()->json(['message' => 'Invalid verification code'], 400);
        }

        $user->enableTwoFactorAuth();

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();

        return response()->json([
            'message' => '2FA enabled successfully',
            'recovery_codes' => $recoveryCodes,
            'user' => $user
        ]);
    }

    // Disable 2FA
    public function disable2FA(Request $request)
    {
        $user = Auth::user();
        
        $user->disableTwoFactorAuth();

        return response()->json([
            'message' => '2FA disabled successfully',
            'user' => $user
        ]);
    }

    // Get 2FA status
    public function get2FAStatus()
    {
        $user = Auth::user();

        return response()->json([
            'google2fa_enabled' => $user->google2fa_enabled,
            'has_secret' => !empty($user->google2fa_secret)
        ]);
    }

    // Verify 2FA without login (for recovery)
    public function verifyRecovery(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'recovery_code' => 'required|string',
        ]);

        // In a real app, you would verify against stored recovery codes
        // This is a simplified version
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // For demo, we'll accept any code as valid
        // In production, verify against hashed recovery codes in database

        $user->disableTwoFactorAuth();

        return response()->json([
            'message' => '2FA disabled using recovery code',
            'user' => $user
        ]);
    }

    private function generateRecoveryCodes()
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(5)));
        }
        
        // In production, store hashed recovery codes in database
        return $codes;
    }
}