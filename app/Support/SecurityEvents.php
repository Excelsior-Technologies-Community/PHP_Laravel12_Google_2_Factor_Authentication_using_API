<?php

namespace App\Support;

class SecurityEvents
{
    public const REGISTER = 'Register';
    public const LOGIN = 'Login';
    public const LOGOUT = 'Logout';
    public const LOGOUT_ALL = 'Logout All';
    public const TWO_FACTOR_ENABLE = '2FA Enable';
    public const TWO_FACTOR_DISABLE = '2FA Disable';
    public const TWO_FACTOR_RECOVERY = 'Recovery Code Login';
    public const RECOVERY_CONFIRMED = 'Recovery Codes Confirmed';
    public const PASSWORD_CHANGE = 'Password Change';
    public const EMAIL_CHANGE_REQUEST = 'Email Change Requested';
    public const EMAIL_CHANGED = 'Email Changed';
    public const PROFILE_UPDATE = 'Profile Update';
    public const TRUSTED_DEVICE_REMOVED = 'Trusted Device Removed';
    public const ALL_TRUSTED_DEVICES_REMOVED = 'All Trusted Devices Removed';
    public const ACCOUNT_DELETED = 'Account Deleted';
}
