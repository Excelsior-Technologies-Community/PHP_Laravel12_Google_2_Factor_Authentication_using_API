<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google2fa_secret',
        'google2fa_enabled',
        'two_factor_recovery_codes',
        'recovery_codes_confirmed_at',
        'pending_email',
        'pending_email_token',
        'pending_email_expires_at',
        'password_changed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
        'two_factor_recovery_codes',
        'pending_email_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'google2fa_enabled' => 'boolean',
        'two_factor_recovery_codes' => 'array',
        'recovery_codes_confirmed_at' => 'datetime',
        'pending_email_expires_at' => 'datetime',
        'password_changed_at' => 'datetime',
    ];

    public function enableTwoFactorAuth(): void
    {
        $this->google2fa_enabled = true;
        $this->save();
    }

    public function disableTwoFactorAuth(): void
    {
        $this->google2fa_secret = null;
        $this->google2fa_enabled = false;
        $this->two_factor_recovery_codes = null;
        $this->recovery_codes_confirmed_at = null;
        $this->save();
    }

    /**
     * Generate 8 new recovery codes, hash them for storage, return plain codes to user.
     */
    public function generateAndStoreRecoveryCodes(): array
    {
        $plainCodes = [];
        $hashedCodes = [];

        for ($i = 0; $i < 8; $i++) {
            $code = strtoupper(Str::random(10));
            $plainCodes[] = $code;
            $hashedCodes[] = Hash::make($code);
        }

        $this->two_factor_recovery_codes = $hashedCodes;
        $this->recovery_codes_confirmed_at = null;
        $this->save();

        return $plainCodes;
    }

    /**
     * Check and consume a recovery code.
     */
    public function verifyAndConsumeRecoveryCode(string $code): bool
    {
        $storedCodes = $this->two_factor_recovery_codes ?? [];

        foreach ($storedCodes as $index => $hashedCode) {
            if (Hash::check($code, $hashedCode)) {
                // Consume the code
                unset($storedCodes[$index]);
                $this->two_factor_recovery_codes = array_values($storedCodes);
                $this->save();
                return true;
            }
        }

        return false;
    }

    /**
     * Trusted devices.
     */
    public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDevice::class);
    }

    /**
     * Authentication logs
     */
    public function authenticationLogs(): HasMany
    {
        return $this->hasMany(AuthenticationLog::class);
    }
}
