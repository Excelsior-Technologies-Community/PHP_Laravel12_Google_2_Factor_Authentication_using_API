<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google2fa_secret',
        'google2fa_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'google2fa_enabled' => 'boolean',
    ];

    public function enableTwoFactorAuth()
    {
        $this->google2fa_enabled = true;
        $this->save();
    }

    public function disableTwoFactorAuth()
    {
        $this->google2fa_secret = null;
        $this->google2fa_enabled = false;
        $this->save();
    }
}