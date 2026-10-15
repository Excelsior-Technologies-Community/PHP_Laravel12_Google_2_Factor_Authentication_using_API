<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google 2FA & Security API Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 min-h-screen text-slate-800">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold text-lg shadow-md">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold leading-tight">Laravel 2FA Security System</h1>
                    <p class="text-xs text-slate-500">Sanctum API &amp; Multi-Factor Authentication</p>
                </div>
            </div>

            <!-- Header Action Controls -->
            <div id="header-user-info" class="hidden flex items-center space-x-3">
                <div class="text-right">
                    <p id="user-display-name" class="text-sm font-semibold text-slate-800">User</p>
                    <p id="user-display-email" class="text-xs text-slate-500">user@example.com</p>
                </div>
                <div class="h-6 w-px bg-slate-200"></div>
                <button onclick="logout(false)" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-md font-medium transition cursor-pointer">
                    <i class="fa-solid fa-arrow-right-from-bracket mr-1"></i> Logout
                </button>
                <button onclick="logout(true)" class="text-xs bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 px-3 py-1.5 rounded-md font-medium transition cursor-pointer">
                    <i class="fa-solid fa-power-off mr-1"></i> Logout All Devices
                </button>
            </div>
        </div>
    </header>

    <!-- Global Alert Notification -->
    <div class="max-w-6xl mx-auto px-4 mt-4">
        <div id="alert-box" class="hidden p-4 rounded-lg text-sm flex items-start justify-between shadow-sm transition">
            <div class="flex items-start space-x-3">
                <i id="alert-icon" class="fa-solid fa-circle-info mt-0.5"></i>
                <div id="alert-message" class="font-medium"></div>
            </div>
            <button onclick="hideAlert()" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    <!-- Main Container -->
    <main class="max-w-6xl mx-auto px-4 py-6">

        <!-- 1. AUTHENTICATION SECTION (GUEST) -->
        <section id="guest-section" class="max-w-md mx-auto bg-white p-6 rounded-xl shadow-md border border-slate-200">
            <!-- Form Tabs -->
            <div class="flex border-b border-slate-200 mb-6">
                <button id="tab-login-btn" onclick="switchAuthTab('login')" class="flex-1 pb-3 text-center font-semibold text-indigo-600 border-b-2 border-indigo-600 transition cursor-pointer">
                    Sign In
                </button>
                <button id="tab-register-btn" onclick="switchAuthTab('register')" class="flex-1 pb-3 text-center font-semibold text-slate-400 hover:text-slate-600 transition cursor-pointer">
                    Create Account
                </button>
            </div>

            <!-- Login / Register Form -->
            <div id="auth-form-container" class="space-y-4">
                <div id="name-group" class="hidden space-y-1">
                    <label class="text-xs font-semibold text-slate-600 uppercase">Full Name</label>
                    <input type="text" id="auth-name" placeholder="John Doe" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-600 uppercase">Email Address</label>
                    <input type="email" id="auth-email" placeholder="user@example.com" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-600 uppercase">Password</label>
                    <input type="password" id="auth-password" placeholder="••••••••" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div id="password-confirm-group" class="hidden space-y-1">
                    <label class="text-xs font-semibold text-slate-600 uppercase">Confirm Password</label>
                    <input type="password" id="auth-password-confirm" placeholder="••••••••" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div id="login-options" class="pt-2">
                    <label class="text-xs font-semibold text-slate-600 uppercase block mb-1">Device Name</label>
                    <input type="text" id="auth-device-name" placeholder="e.g. MacBook Pro / Chrome" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 mb-3">
                </div>

                <button id="auth-submit-btn" onclick="submitAuth()" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg text-sm transition shadow-sm cursor-pointer">
                    Sign In
                </button>
            </div>

            <!-- 2FA Prompt Verification Card -->
            <div id="two-factor-verify-card" class="hidden space-y-5">
                <div class="text-center">
                    <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-2 text-xl">
                        <i class="fa-solid fa-shield-virus"></i>
                    </div>
                    <h2 class="text-lg font-bold">Two-Factor Authentication</h2>
                    <p class="text-xs text-slate-500">Enter the 6-digit OTP code or an 8-character recovery code.</p>
                </div>

                <!-- OTP Input Mode -->
                <div id="otp-input-mode" class="space-y-3">
                    <label class="text-xs font-semibold text-slate-600 uppercase">Authenticator Code</label>
                    <input type="text" id="verify-otp-code" maxlength="6" placeholder="123456" class="w-full tracking-widest text-center text-2xl font-mono px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">

                    <!-- Trust Device Option -->
                    <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 space-y-2">
                        <label class="flex items-center space-x-2 text-xs text-slate-700 cursor-pointer">
                            <input type="checkbox" id="trust-device-check" class="rounded text-indigo-600 focus:ring-0">
                            <span>Trust this device (skip 2FA next time)</span>
                        </label>
                        <div id="trust-duration-container" class="hidden pl-5 text-xs text-slate-600 flex items-center space-x-2">
                            <span>Trust duration:</span>
                            <select id="trust-days-select" class="border border-slate-300 rounded px-2 py-1 text-xs bg-white">
                                <option value="7">7 Days</option>
                                <option value="15">15 Days</option>
                                <option value="30" selected>30 Days</option>
                                <option value="60">60 Days</option>
                                <option value="90">90 Days</option>
                            </select>
                        </div>
                    </div>

                    <button onclick="verify2FA()" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg text-sm transition cursor-pointer">
                        Verify &amp; Continue
                    </button>

                    <div class="text-center pt-2">
                        <button onclick="toggleRecoveryCodeInput(true)" class="text-xs text-indigo-600 hover:underline font-medium cursor-pointer">
                            Lost phone? Use a backup recovery code
                        </button>
                    </div>
                </div>

                <!-- Recovery Code Mode -->
                <div id="recovery-input-mode" class="hidden space-y-3">
                    <label class="text-xs font-semibold text-slate-600 uppercase">Backup Recovery Code</label>
                    <input type="text" id="verify-recovery-code" maxlength="10" placeholder="A1B2C3D4" class="w-full tracking-widest text-center uppercase text-xl font-mono px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-slate-500">Each recovery code can only be used once.</p>

                    <button onclick="verifyRecoveryCode()" class="w-full py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg text-sm transition cursor-pointer">
                        Verify Recovery Code
                    </button>

                    <div class="text-center pt-2">
                        <button onclick="toggleRecoveryCodeInput(false)" class="text-xs text-indigo-600 hover:underline font-medium cursor-pointer">
                            Back to 6-digit Authenticator code
                        </button>
                    </div>
                </div>

                <button onclick="cancel2FA()" class="w-full py-2 text-slate-500 hover:text-slate-700 text-xs font-medium cursor-pointer">
                    Cancel and Sign In with different account
                </button>
            </div>
        </section>

        <!-- 2. AUTHENTICATED DASHBOARD (USER) -->
        <section id="user-section" class="hidden space-y-6">

            <!-- Navigation Tabs -->
            <div class="bg-white border border-slate-200 rounded-xl p-1.5 flex flex-wrap gap-1 shadow-sm">
                <button onclick="switchDashboardTab('overview')" id="tab-btn-overview" class="px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white transition flex items-center space-x-2 cursor-pointer">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Dashboard &amp; 2FA</span>
                </button>
                <button onclick="switchDashboardTab('sessions')" id="tab-btn-sessions" class="px-4 py-2 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 transition flex items-center space-x-2 cursor-pointer">
                    <i class="fa-solid fa-laptop"></i>
                    <span>Devices &amp; Sessions</span>
                </button>
                <button onclick="switchDashboardTab('audit')" id="tab-btn-audit" class="px-4 py-2 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 transition flex items-center space-x-2 cursor-pointer">
                    <i class="fa-solid fa-list-check"></i>
                    <span>Audit Logs</span>
                </button>
                <button onclick="switchDashboardTab('account')" id="tab-btn-account" class="px-4 py-2 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 transition flex items-center space-x-2 cursor-pointer">
                    <i class="fa-solid fa-user-shield"></i>
                    <span>Account Security</span>
                </button>
            </div>

            <!-- TAB 1: DASHBOARD & 2FA -->
            <div id="tab-content-overview" class="space-y-6">
                <!-- Top Metrics Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Security Score Card -->
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase">Security Score</span>
                            <div class="flex items-baseline space-x-2 mt-1">
                                <span id="metric-score" class="text-3xl font-bold text-indigo-600">--</span>
                                <span class="text-xs text-slate-400">/ 100</span>
                            </div>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 mt-3 overflow-hidden">
                            <div id="metric-score-bar" class="bg-indigo-600 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- 2FA Status Card -->
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase">2FA Status</span>
                            <div id="metric-2fa-status" class="mt-1 flex items-center space-x-2 text-lg font-bold text-slate-700">
                                <span>Loading...</span>
                            </div>
                        </div>
                        <p id="metric-2fa-sub" class="text-xs text-slate-500 mt-2">Recovery codes: --</p>
                    </div>

                    <!-- Active Sessions -->
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase">Active Sessions</span>
                            <div id="metric-sessions" class="mt-1 text-3xl font-bold text-slate-800">--</div>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">Active Sanctum tokens</p>
                    </div>

                    <!-- Trusted Devices -->
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-semibold text-slate-400 uppercase">Trusted Devices</span>
                            <div id="metric-trusted-devices" class="mt-1 text-3xl font-bold text-slate-800">--</div>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">Bypass 2FA on recognized devices</p>
                    </div>
                </div>

                <!-- 2FA Control Card -->
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-base font-bold text-slate-800">Two-Factor Authentication (2FA)</h2>
                            <p class="text-xs text-slate-500">Protect your account using time-based one-time password (TOTP) through Google Authenticator.</p>
                        </div>
                        <div id="2fa-badge-container"></div>
                    </div>

                    <!-- Not Enrolled State -->
                    <div id="2fa-disabled-view" class="space-y-4">
                        <p class="text-sm text-slate-600">Two-factor authentication is currently not enabled on this account. Enable it now to safeguard your account against unauthorized access.</p>
                        <button onclick="start2FASetup()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm cursor-pointer">
                            <i class="fa-solid fa-shield-plus mr-1"></i> Setup Google Authenticator
                        </button>
                    </div>

                    <!-- 2FA Setup Flow In Progress -->
                    <div id="2fa-setup-view" class="hidden border border-indigo-100 bg-indigo-50/40 p-5 rounded-xl space-y-5">
                        <div class="flex items-start justify-between">
                            <h3 class="text-sm font-bold text-indigo-900">Step 1: Scan QR Code or Enter Secret Key</h3>
                            <button onclick="cancel2FASetup()" class="text-xs text-slate-500 hover:text-slate-700 cursor-pointer">Cancel</button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                            <!-- QR Code SVG -->
                            <div class="bg-white p-4 rounded-xl border border-slate-200 flex flex-col items-center justify-center">
                                <div id="setup-qr-container" class="w-48 h-48 flex items-center justify-center"></div>
                                <span class="text-[11px] text-slate-500 mt-2">Scan in Google Authenticator or 1Password</span>
                            </div>

                            <!-- Manual Key & Verification Form -->
                            <div class="space-y-4">
                                <div>
                                    <label class="text-xs font-semibold text-slate-600 uppercase block mb-1">Manual Secret Key</label>
                                    <div class="flex items-center space-x-2">
                                        <code id="setup-secret-key" class="bg-white border border-slate-300 px-3 py-2 rounded-lg text-xs font-mono font-bold tracking-wider text-indigo-700 flex-1 select-all">--</code>
                                        <button onclick="copyToClipboard('setup-secret-key')" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs font-medium cursor-pointer">
                                            <i class="fa-regular fa-copy"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-xs font-semibold text-slate-600 uppercase block">Step 2: Enter 6-digit Code from App</label>
                                    <input type="text" id="setup-verify-code" maxlength="6" placeholder="000000" class="w-full tracking-widest text-center text-xl font-mono px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>

                                <button onclick="confirmAndEnable2FA()" class="w-full py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition shadow-sm cursor-pointer">
                                    <i class="fa-solid fa-check mr-1"></i> Verify &amp; Enable 2FA
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Recovery Codes Confirmation Modal/Card -->
                    <div id="recovery-codes-modal" class="hidden border-2 border-amber-300 bg-amber-50/70 p-5 rounded-xl space-y-4">
                        <div class="flex items-center space-x-3 text-amber-800">
                            <i class="fa-solid fa-triangle-exclamation text-2xl text-amber-600"></i>
                            <div>
                                <h3 class="text-sm font-bold">Important: Save Your Backup Recovery Codes</h3>
                                <p class="text-xs text-amber-700">If you lose your mobile device, these codes are the only way to recover access to your account. Each code can be used only once.</p>
                            </div>
                        </div>

                        <div id="recovery-codes-list" class="grid grid-cols-2 sm:grid-cols-4 gap-2 bg-white p-4 rounded-lg border border-amber-200 font-mono text-xs font-bold text-center tracking-wider text-slate-800">
                            <!-- Populated via JS -->
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                            <button onclick="copyRecoveryCodes()" class="text-xs bg-white border border-slate-300 hover:bg-slate-50 px-3 py-2 rounded-lg font-medium text-slate-700 transition cursor-pointer">
                                <i class="fa-regular fa-copy mr-1"></i> Copy All Codes
                            </button>
                            <button onclick="confirmRecoveryCodesSaved()" class="text-xs bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg font-medium transition shadow-sm cursor-pointer">
                                <i class="fa-solid fa-check-double mr-1"></i> I have saved these backup recovery codes
                            </button>
                        </div>
                    </div>

                    <!-- 2FA Enabled State -->
                    <div id="2fa-enabled-view" class="hidden space-y-4">
                        <div class="flex items-center justify-between bg-green-50 border border-green-200 p-4 rounded-xl text-green-900 text-sm">
                            <div class="flex items-center space-x-3">
                                <i class="fa-solid fa-circle-check text-green-600 text-xl"></i>
                                <div>
                                    <p class="font-semibold">Two-factor authentication is active</p>
                                    <p class="text-xs text-green-700">Your account is fortified with multi-factor authentication.</p>
                                </div>
                            </div>
                            <button onclick="disable2FA()" class="text-xs bg-red-100 hover:bg-red-200 text-red-700 border border-red-300 px-3 py-1.5 rounded-lg font-medium transition cursor-pointer">
                                Disable 2FA
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Security Recommendations Box -->
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                    <h2 class="text-base font-bold text-slate-800 flex items-center space-x-2">
                        <i class="fa-solid fa-lightbulb text-amber-500"></i>
                        <span>Security Recommendations</span>
                    </h2>
                    <ul id="recommendations-list" class="space-y-2 text-xs text-slate-600">
                        <li class="p-3 bg-slate-50 rounded-lg border border-slate-100">Loading recommendations...</li>
                    </ul>
                </div>
            </div>

            <!-- TAB 2: ACTIVE SESSIONS & TRUSTED DEVICES -->
            <div id="tab-content-sessions" class="hidden space-y-6">
                <!-- Trusted Devices -->
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-800">Trusted Devices</h2>
                            <p class="text-xs text-slate-500">Devices where 2FA verification is remembered until expiration.</p>
                        </div>
                        <button onclick="revokeAllTrustedDevices()" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 px-3 py-1.5 rounded-lg font-medium transition cursor-pointer">
                            Revoke All Devices
                        </button>
                    </div>

                    <div id="trusted-devices-list" class="space-y-2">
                        <p class="text-xs text-slate-400">Loading trusted devices...</p>
                    </div>
                </div>

                <!-- Active Sanctum Tokens -->
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-800">Active API Sessions</h2>
                            <p class="text-xs text-slate-500">Logged-in instances using Sanctum personal access tokens.</p>
                        </div>
                        <button onclick="logout(true)" class="text-xs bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 px-3 py-1.5 rounded-lg font-medium transition cursor-pointer">
                            Terminate All Sessions
                        </button>
                    </div>

                    <div id="active-sessions-list" class="space-y-2">
                        <p class="text-xs text-slate-400">Loading sessions...</p>
                    </div>
                </div>
            </div>

            <!-- TAB 3: AUDIT LOGS -->
            <div id="tab-content-audit" class="hidden space-y-6">
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-800">Security Audit Logs</h2>
                            <p class="text-xs text-slate-500">Immutable trail of authentication and security events on your account.</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <select id="audit-filter-event" onchange="loadAuditLogs()" class="border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs bg-white text-slate-700">
                                <option value="">All Events</option>
                                <option value="login_success">Login Success</option>
                                <option value="login_failed">Login Failed</option>
                                <option value="logout">Logout</option>
                                <option value="logout_all_devices">Logout All Devices</option>
                                <option value="2fa_enabled">2FA Enabled</option>
                                <option value="2fa_disabled">2FA Disabled</option>
                                <option value="recovery_code_used">Recovery Code Used</option>
                                <option value="trusted_device_added">Trusted Device Added</option>
                                <option value="trusted_device_removed">Trusted Device Removed</option>
                                <option value="email_change_requested">Email Change Requested</option>
                                <option value="email_changed">Email Changed</option>
                            </select>
                            <button onclick="loadAuditLogs()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-lg text-xs font-medium cursor-pointer">
                                <i class="fa-solid fa-rotate"></i>
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-slate-500 uppercase font-semibold border-b border-slate-200">
                                <tr>
                                    <th class="py-2.5 px-3">Event</th>
                                    <th class="py-2.5 px-3">Status</th>
                                    <th class="py-2.5 px-3">IP Address</th>
                                    <th class="py-2.5 px-3">Device / User Agent</th>
                                    <th class="py-2.5 px-3">Date &amp; Time</th>
                                </tr>
                            </thead>
                            <tbody id="audit-logs-table-body" class="divide-y divide-slate-100">
                                <tr>
                                    <td colspan="5" class="py-4 text-center text-slate-400">Loading audit history...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 4: ACCOUNT SECURITY & SETTINGS -->
            <div id="tab-content-account" class="hidden space-y-6">
                <!-- Change Email Flow -->
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-base font-bold text-slate-800">Email Address Change</h2>
                        <p class="text-xs text-slate-500">Requires your current password and a 6-digit confirmation code sent to your existing email.</p>
                    </div>

                    <!-- Step 1: Request -->
                    <div id="email-step-request" class="space-y-3 max-w-md">
                        <div>
                            <label class="text-xs font-semibold text-slate-600 uppercase block mb-1">New Email Address</label>
                            <input type="email" id="change-new-email" placeholder="new.email@example.com" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600 uppercase block mb-1">Current Password</label>
                            <input type="password" id="change-email-password" placeholder="••••••••" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                        </div>
                        <button onclick="requestEmailChange()" class="py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-medium transition cursor-pointer">
                            Send Confirmation Code to Old Email
                        </button>
                    </div>

                    <!-- Step 2: Confirm -->
                    <div id="email-step-confirm" class="hidden space-y-3 max-w-md bg-indigo-50/50 p-4 rounded-xl border border-indigo-100">
                        <p class="text-xs text-indigo-900 font-medium">A 6-digit confirmation code was sent to your current email. Enter it below to complete the change.</p>
                        <div>
                            <label class="text-xs font-semibold text-slate-600 uppercase block mb-1">6-Digit Confirmation Code</label>
                            <input type="text" id="change-email-token" maxlength="6" placeholder="123456" class="w-full tracking-widest text-center text-xl font-mono px-3 py-2 border border-slate-300 rounded-lg">
                        </div>
                        <div class="flex items-center space-x-2">
                            <button onclick="confirmEmailChange()" class="py-2 px-4 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-medium transition cursor-pointer">
                                Confirm Email Change
                            </button>
                            <button onclick="cancelEmailChange()" class="py-2 px-3 text-slate-500 text-xs hover:underline cursor-pointer">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Danger Zone: Account Deletion -->
                <div class="bg-red-50/40 border border-red-200 p-6 rounded-xl space-y-4">
                    <div class="border-b border-red-200/60 pb-3">
                        <h2 class="text-base font-bold text-red-700">Danger Zone: Delete Account</h2>
                        <p class="text-xs text-red-600">Permanently delete your account, personal data, trusted devices, and active tokens.</p>
                    </div>

                    <div class="max-w-md space-y-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-700 uppercase block mb-1">Confirm Password</label>
                            <input type="password" id="delete-account-password" placeholder="Your current password" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-700 uppercase block mb-1">2FA Code (If 2FA is enabled)</label>
                            <input type="text" id="delete-account-2fa" placeholder="6-digit code or leave empty if disabled" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white">
                        </div>
                        <button onclick="deleteAccount()" class="py-2 px-4 bg-red-600 hover:bg-red-700 text-white rounded-lg text-xs font-medium transition shadow-sm cursor-pointer">
                            <i class="fa-solid fa-trash-can mr-1"></i> Permanently Delete My Account
                        </button>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <script>
        // State
        let apiToken = localStorage.getItem('demo_api_token') || null;
        let currentUser = null;
        let currentUserId = null;
        let authMode = 'login';
        let latestRecoveryCodes = [];

        // Dynamic API base URL to work with both php artisan serve and xampp subfolders
        const API_BASE = '{{ url('/api') }}';

        // DOM Loaded Init
        window.addEventListener('DOMContentLoaded', () => {
            // Listen for trust device checkbox
            document.getElementById('trust-device-check')?.addEventListener('change', (e) => {
                document.getElementById('trust-duration-container').classList.toggle('hidden', !e.target.checked);
            });

            if (apiToken) {
                initAuthenticatedSession();
            } else {
                showGuestUI();
            }
        });

        // Notifications
        function showAlert(msg, type = 'info') {
            const box = document.getElementById('alert-box');
            const messageEl = document.getElementById('alert-message');
            const iconEl = document.getElementById('alert-icon');

            box.className = 'p-4 rounded-lg text-sm flex items-start justify-between shadow-sm transition ';
            if (type === 'error') {
                box.classList.add('bg-red-50', 'text-red-800', 'border', 'border-red-200');
                iconEl.className = 'fa-solid fa-circle-exclamation mt-0.5 text-red-600';
            } else if (type === 'success') {
                box.classList.add('bg-green-50', 'text-green-800', 'border', 'border-green-200');
                iconEl.className = 'fa-solid fa-circle-check mt-0.5 text-green-600';
            } else {
                box.classList.add('bg-blue-50', 'text-blue-800', 'border', 'border-blue-200');
                iconEl.className = 'fa-solid fa-circle-info mt-0.5 text-blue-600';
            }

            messageEl.textContent = msg;
            box.classList.remove('hidden');
        }

        function hideAlert() {
            document.getElementById('alert-box').classList.add('hidden');
        }

        // Tab Switching in Guest Form
        function switchAuthTab(mode) {
            authMode = mode;
            hideAlert();
            const isReg = mode === 'register';

            document.getElementById('name-group').classList.toggle('hidden', !isReg);
            document.getElementById('password-confirm-group').classList.toggle('hidden', !isReg);
            document.getElementById('login-options').classList.toggle('hidden', isReg);

            const loginBtn = document.getElementById('tab-login-btn');
            const regBtn = document.getElementById('tab-register-btn');
            const submitBtn = document.getElementById('auth-submit-btn');

            if (isReg) {
                regBtn.className = 'flex-1 pb-3 text-center font-semibold text-indigo-600 border-b-2 border-indigo-600 transition cursor-pointer';
                loginBtn.className = 'flex-1 pb-3 text-center font-semibold text-slate-400 hover:text-slate-600 transition cursor-pointer';
                submitBtn.textContent = 'Create Account';
            } else {
                loginBtn.className = 'flex-1 pb-3 text-center font-semibold text-indigo-600 border-b-2 border-indigo-600 transition cursor-pointer';
                regBtn.className = 'flex-1 pb-3 text-center font-semibold text-slate-400 hover:text-slate-600 transition cursor-pointer';
                submitBtn.textContent = 'Sign In';
            }
        }

        // Auth Submission
        async function submitAuth() {
            hideAlert();
            const email = document.getElementById('auth-email').value.trim();
            const password = document.getElementById('auth-password').value;

            if (!email || !password) {
                showAlert('Please fill in both email and password.', 'error');
                return;
            }

            if (authMode === 'register') {
                const name = document.getElementById('auth-name').value.trim();
                const password_confirmation = document.getElementById('auth-password-confirm').value;

                if (!name) {
                    showAlert('Please enter your full name.', 'error');
                    return;
                }
                if (password !== password_confirmation) {
                    showAlert('Passwords do not match.', 'error');
                    return;
                }

                try {
                    const res = await fetch(`${API_BASE}/auth/register`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                        body: JSON.stringify({name, email, password, password_confirmation})
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'Registration failed');

                    apiToken = data.token;
                    currentUser = data.user;
                    localStorage.setItem('demo_api_token', apiToken);
                    showAlert('Account created successfully! Welcome.', 'success');
                    initAuthenticatedSession();
                } catch (err) {
                    showAlert(err.message, 'error');
                }
            } else {
                const device_name = document.getElementById('auth-device-name').value.trim() || undefined;

                try {
                    const res = await fetch(`${API_BASE}/auth/login`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                        body: JSON.stringify({email, password, device_name})
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.message || 'Login failed');

                    if (data.requires_2fa) {
                        currentUserId = data.user_id;
                        document.getElementById('auth-form-container').classList.add('hidden');
                        document.getElementById('two-factor-verify-card').classList.remove('hidden');
                        showAlert(data.message || 'Two-Factor Authentication required.', 'info');
                    } else {
                        apiToken = data.token;
                        currentUser = data.user;
                        localStorage.setItem('demo_api_token', apiToken);
                        showAlert('Signed in successfully!', 'success');
                        initAuthenticatedSession();
                    }
                } catch (err) {
                    showAlert(err.message, 'error');
                }
            }
        }

        // 2FA Verification Form
        async function verify2FA() {
            hideAlert();
            const code = document.getElementById('verify-otp-code').value.trim();
            const remember_device = document.getElementById('trust-device-check').checked;
            const trust_days = remember_device ? parseInt(document.getElementById('trust-days-select').value) : undefined;

            if (!code || code.length < 6) {
                showAlert('Please enter the 6-digit code.', 'error');
                return;
            }

            try {
                const res = await fetch(`${API_BASE}/auth/verify-2fa`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                    body: JSON.stringify({user_id: currentUserId, code, remember_device, trust_days})
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Invalid 2FA code');

                apiToken = data.token;
                currentUser = data.user;
                localStorage.setItem('demo_api_token', apiToken);
                showAlert('Two-factor authentication successful!', 'success');
                cancel2FA();
                initAuthenticatedSession();
            } catch (err) {
                showAlert(err.message, 'error');
            }
        }

        // Recovery Code Verification
        async function verifyRecoveryCode() {
            hideAlert();
            const recovery_code = document.getElementById('verify-recovery-code').value.trim();
            if (!recovery_code) {
                showAlert('Please enter your recovery code.', 'error');
                return;
            }

            try {
                const res = await fetch(`${API_BASE}/auth/recover-2fa`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                    body: JSON.stringify({user_id: currentUserId, recovery_code})
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Invalid recovery code');

                apiToken = data.token;
                currentUser = data.user;
                localStorage.setItem('demo_api_token', apiToken);
                showAlert(data.message || 'Recovery verified successfully!', 'success');
                cancel2FA();
                initAuthenticatedSession();
            } catch (err) {
                showAlert(err.message, 'error');
            }
        }

        function toggleRecoveryCodeInput(showRecovery) {
            document.getElementById('otp-input-mode').classList.toggle('hidden', showRecovery);
            document.getElementById('recovery-input-mode').classList.toggle('hidden', !showRecovery);
        }

        function cancel2FA() {
            document.getElementById('two-factor-verify-card').classList.add('hidden');
            document.getElementById('auth-form-container').classList.remove('hidden');
            document.getElementById('verify-otp-code').value = '';
            document.getElementById('verify-recovery-code').value = '';
            toggleRecoveryCodeInput(false);
        }

        // Session Bootstrapping
        async function initAuthenticatedSession() {
            if (!apiToken) {
                showGuestUI();
                return;
            }

            // Immediately switch views to authenticated so user sees logged-in view
            document.getElementById('guest-section').classList.add('hidden');
            document.getElementById('user-section').classList.remove('hidden');
            document.getElementById('header-user-info').classList.remove('hidden');

            if (currentUser) {
                document.getElementById('user-display-name').textContent = currentUser.name || 'User';
                document.getElementById('user-display-email').textContent = currentUser.email || '';
            }

            try {
                // Fetch dashboard data
                const res = await fetch(`${API_BASE}/auth/security-dashboard`, {
                    headers: {'Authorization': `Bearer ${apiToken}`, 'Accept': 'application/json'}
                });

                if (res.status === 401) {
                    // Token expired or invalid
                    logout(false, false);
                    return;
                }

                if (res.ok) {
                    const json = await res.json();
                    const payload = json.data || json;

                    if (payload.user) {
                        currentUser = payload.user;
                        document.getElementById('user-display-name').textContent = currentUser.name || 'User';
                        document.getElementById('user-display-email').textContent = currentUser.email || '';
                    }

                    // Populate Security Dashboard Metrics
                    renderDashboardMetrics(payload);
                }

                // Check 2FA Status
                await refresh2FAStatus();
            } catch (err) {
                console.error("Dashboard fetch error:", err);
                // Do not log out on minor network error; keep user section active
            }
        }

        function showGuestUI() {
            document.getElementById('guest-section').classList.remove('hidden');
            document.getElementById('user-section').classList.add('hidden');
            document.getElementById('header-user-info').classList.add('hidden');
        }

        // Dashboard Tabs
        function switchDashboardTab(tabName) {
            const tabs = ['overview', 'sessions', 'audit', 'account'];
            tabs.forEach(t => {
                const btn = document.getElementById(`tab-btn-${t}`);
                const content = document.getElementById(`tab-content-${t}`);
                if (btn && content) {
                    if (t === tabName) {
                        btn.className = 'px-4 py-2 text-sm font-medium rounded-lg bg-indigo-600 text-white transition flex items-center space-x-2 cursor-pointer';
                        content.classList.remove('hidden');
                    } else {
                        btn.className = 'px-4 py-2 text-sm font-medium rounded-lg text-slate-600 hover:bg-slate-100 transition flex items-center space-x-2 cursor-pointer';
                        content.classList.add('hidden');
                    }
                }
            });

            if (tabName === 'sessions') {
                loadSessionsAndDevices();
            } else if (tabName === 'audit') {
                loadAuditLogs();
            } else if (tabName === 'overview') {
                initAuthenticatedSession();
            }
        }

        // Render Security Metrics
        function renderDashboardMetrics(payload) {
            const health = payload.security_health || {};
            const score = typeof health.score !== 'undefined' ? health.score : (payload.security_score || 50);
            
            document.getElementById('metric-score').textContent = score;
            const scoreBar = document.getElementById('metric-score-bar');
            scoreBar.style.width = `${score}%`;

            if (score >= 80) {
                scoreBar.className = 'bg-green-600 h-2 rounded-full transition-all duration-500';
            } else if (score >= 50) {
                scoreBar.className = 'bg-amber-500 h-2 rounded-full transition-all duration-500';
            } else {
                scoreBar.className = 'bg-red-500 h-2 rounded-full transition-all duration-500';
            }

            const sessions = payload.active_sessions?.sessions || payload.active_sessions || [];
            document.getElementById('metric-sessions').textContent = Array.isArray(sessions) ? sessions.length : 1;

            const trustedDevices = payload.trusted_devices?.devices || payload.trusted_devices || [];
            document.getElementById('metric-trusted-devices').textContent = Array.isArray(trustedDevices) ? trustedDevices.length : 0;

            // Recommendations
            const recList = document.getElementById('recommendations-list');
            recList.innerHTML = '';
            const recs = health.recommendations || payload.recommendations || [];
            if (recs.length > 0) {
                recs.forEach(rec => {
                    const li = document.createElement('li');
                    li.className = 'p-3 bg-amber-50/70 border border-amber-200 text-amber-900 rounded-lg flex items-center space-x-2';
                    li.innerHTML = `<i class="fa-solid fa-triangle-exclamation text-amber-600"></i><span>${escapeHtml(rec)}</span>`;
                    recList.appendChild(li);
                });
            } else {
                recList.innerHTML = '<li class="p-3 bg-green-50 border border-green-200 text-green-900 rounded-lg flex items-center space-x-2"><i class="fa-solid fa-circle-check text-green-600"></i><span>All recommended security protections are active! Great job.</span></li>';
            }
        }

        // 2FA Management
        async function refresh2FAStatus() {
            try {
                const res = await fetch(`${API_BASE}/2fa/status`, {
                    headers: {'Authorization': `Bearer ${apiToken}`, 'Accept': 'application/json'}
                });
                if (!res.ok) return;

                const data = await res.json();
                const isEnabled = !!data.google2fa_enabled;

                const statusEl = document.getElementById('metric-2fa-status');
                const badgeEl = document.getElementById('2fa-badge-container');
                const subEl = document.getElementById('metric-2fa-sub');

                if (isEnabled) {
                    statusEl.innerHTML = '<span class="text-green-600 flex items-center space-x-1.5"><i class="fa-solid fa-circle-check"></i><span>Active</span></span>';
                    badgeEl.innerHTML = '<span class="text-xs bg-green-100 text-green-800 font-semibold px-2.5 py-1 rounded-full"><i class="fa-solid fa-lock mr-1"></i>2FA Enabled</span>';
                    subEl.textContent = `Confirmed: ${data.recovery_codes_confirmed ? 'Yes' : 'Pending confirmation'} (${data.recovery_codes_remaining ?? 0} codes remaining)`;

                    document.getElementById('2fa-enabled-view').classList.remove('hidden');
                    document.getElementById('2fa-disabled-view').classList.add('hidden');
                    document.getElementById('2fa-setup-view').classList.add('hidden');
                } else {
                    statusEl.innerHTML = '<span class="text-red-500 flex items-center space-x-1.5"><i class="fa-solid fa-circle-xmark"></i><span>Disabled</span></span>';
                    badgeEl.innerHTML = '<span class="text-xs bg-red-100 text-red-700 font-semibold px-2.5 py-1 rounded-full"><i class="fa-solid fa-lock-open mr-1"></i>2FA Off</span>';
                    subEl.textContent = 'Setup required for full protection';

                    document.getElementById('2fa-disabled-view').classList.remove('hidden');
                    document.getElementById('2fa-enabled-view').classList.add('hidden');
                }
            } catch (err) {
                console.error("2FA status fetch error:", err);
            }
        }

        async function start2FASetup() {
            hideAlert();
            try {
                const res = await fetch(`${API_BASE}/2fa/generate`, {
                    method: 'POST',
                    headers: {'Authorization': `Bearer ${apiToken}`, 'Accept': 'application/json'}
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Failed to generate 2FA setup');

                document.getElementById('setup-qr-container').innerHTML = data.qr_code_svg;
                document.getElementById('setup-secret-key').textContent = data.formatted_secret || data.secret;
                document.getElementById('setup-verify-code').value = '';

                document.getElementById('2fa-setup-view').classList.remove('hidden');
                document.getElementById('2fa-disabled-view').classList.add('hidden');
            } catch (err) {
                showAlert(err.message, 'error');
            }
        }

        function cancel2FASetup() {
            document.getElementById('2fa-setup-view').classList.add('hidden');
            document.getElementById('2fa-disabled-view').classList.remove('hidden');
        }

        async function confirmAndEnable2FA() {
            hideAlert();
            const code = document.getElementById('setup-verify-code').value.trim();
            if (!code || code.length < 6) {
                showAlert('Please enter the 6-digit code from your app.', 'error');
                return;
            }

            try {
                const res = await fetch(`${API_BASE}/2fa/enable`, {
                    method: 'POST',
                    headers: {'Authorization': `Bearer ${apiToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json'},
                    body: JSON.stringify({code})
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Invalid verification code');

                document.getElementById('2fa-setup-view').classList.add('hidden');
                showAlert('2FA successfully enabled! Please copy and confirm your recovery codes below.', 'success');

                // Render Recovery Codes
                latestRecoveryCodes = data.recovery_codes || [];
                const codesList = document.getElementById('recovery-codes-list');
                codesList.innerHTML = '';
                latestRecoveryCodes.forEach(rc => {
                    const div = document.createElement('div');
                    div.className = 'p-2 bg-slate-50 border border-slate-200 rounded';
                    div.textContent = rc;
                    codesList.appendChild(div);
                });

                document.getElementById('recovery-codes-modal').classList.remove('hidden');
                await refresh2FAStatus();
            } catch (err) {
                showAlert(err.message, 'error');
            }
        }

        function copyRecoveryCodes() {
            navigator.clipboard.writeText(latestRecoveryCodes.join('\n'));
            showAlert('Backup recovery codes copied to clipboard!', 'info');
        }

        async function confirmRecoveryCodesSaved() {
            hideAlert();
            try {
                const res = await fetch(`${API_BASE}/2fa/confirm-recovery-codes`, {
                    method: 'POST',
                    headers: {'Authorization': `Bearer ${apiToken}`, 'Accept': 'application/json'}
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Confirmation failed');

                document.getElementById('recovery-codes-modal').classList.add('hidden');
                showAlert('Recovery codes confirmed! Your account is now fully protected.', 'success');
                await refresh2FAStatus();
            } catch (err) {
                showAlert(err.message, 'error');
            }
        }

        async function disable2FA() {
            hideAlert();
            const pw = prompt('Please enter your account password to disable 2FA:');
            if (!pw) return;

            try {
                const res = await fetch(`${API_BASE}/2fa/disable`, {
                    method: 'POST',
                    headers: {'Authorization': `Bearer ${apiToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json'},
                    body: JSON.stringify({password: pw})
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Failed to disable 2FA');

                showAlert('Two-factor authentication has been disabled.', 'info');
                await refresh2FAStatus();
            } catch (err) {
                showAlert(err.message, 'error');
            }
        }

        // Sessions & Trusted Devices
        async function loadSessionsAndDevices() {
            try {
                // Trusted Devices
                const devRes = await fetch(`${API_BASE}/auth/trusted-devices`, {
                    headers: {'Authorization': `Bearer ${apiToken}`, 'Accept': 'application/json'}
                });
                const devData = await devRes.json();
                const devContainer = document.getElementById('trusted-devices-list');
                devContainer.innerHTML = '';

                const devices = devData.trusted_devices || [];
                if (devices.length > 0) {
                    devices.forEach(dev => {
                        const item = document.createElement('div');
                        item.className = 'flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs';
                        item.innerHTML = `
                            <div>
                                <p class="font-bold text-slate-800">${escapeHtml(dev.device_name || 'Browser Device')}</p>
                                <p class="text-slate-500">IP: ${escapeHtml(dev.ip_address || '-')} | Expires: ${escapeHtml(dev.expires_at || '-')} (${dev.days_remaining ?? 0} days left)</p>
                            </div>
                            <button onclick="revokeTrustedDevice(${dev.id})" class="text-xs bg-red-50 text-red-600 hover:bg-red-100 px-3 py-1.5 rounded border border-red-200 cursor-pointer">
                                Revoke
                            </button>
                        `;
                        devContainer.appendChild(item);
                    });
                } else {
                    devContainer.innerHTML = '<p class="text-xs text-slate-400 py-2">No trusted devices found.</p>';
                }

                // Active Sessions
                const sessRes = await fetch(`${API_BASE}/auth/active-sessions`, {
                    headers: {'Authorization': `Bearer ${apiToken}`, 'Accept': 'application/json'}
                });
                const sessData = await sessRes.json();
                const sessContainer = document.getElementById('active-sessions-list');
                sessContainer.innerHTML = '';

                const sessions = sessData.sessions || [];
                if (sessions.length > 0) {
                    sessions.forEach(s => {
                        const item = document.createElement('div');
                        item.className = 'flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs';
                        item.innerHTML = `
                            <div>
                                <p class="font-bold text-slate-800">${escapeHtml(s.name || 'Sanctum Token')} ${s.is_current ? '<span class="bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded text-[10px] ml-1 font-semibold">Current Session</span>' : ''}</p>
                                <p class="text-slate-500">Created: ${escapeHtml(s.created_at || '-')} | Last used: ${escapeHtml(s.last_used_at || 'Never')}</p>
                            </div>
                        `;
                        sessContainer.appendChild(item);
                    });
                } else {
                    sessContainer.innerHTML = '<p class="text-xs text-slate-400 py-2">No active sessions.</p>';
                }
            } catch (err) {
                console.error(err);
            }
        }

        async function revokeTrustedDevice(id) {
            hideAlert();
            try {
                const res = await fetch(`${API_BASE}/auth/trusted-devices/${id}`, {
                    method: 'DELETE',
                    headers: {'Authorization': `Bearer ${apiToken}`, 'Accept': 'application/json'}
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Failed to revoke device');

                showAlert(data.message || 'Device revoked. 2FA is now required on that device.', 'success');
                loadSessionsAndDevices();
            } catch (err) {
                showAlert(err.message, 'error');
            }
        }

        async function revokeAllTrustedDevices() {
            if (!confirm('Are you sure you want to revoke all trusted devices? 2FA will be enforced on all next logins.')) return;
            hideAlert();
            try {
                const res = await fetch(`${API_BASE}/auth/trusted-devices`, {
                    method: 'DELETE',
                    headers: {'Authorization': `Bearer ${apiToken}`, 'Accept': 'application/json'}
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Failed to revoke devices');

                showAlert(data.message || 'All trusted devices revoked.', 'success');
                loadSessionsAndDevices();
            } catch (err) {
                showAlert(err.message, 'error');
            }
        }

        // Audit Logs
        async function loadAuditLogs() {
            const event = document.getElementById('audit-filter-event')?.value || '';
            const tbody = document.getElementById('audit-logs-table-body');
            tbody.innerHTML = '<tr><td colspan="5" class="py-4 text-center text-slate-400">Loading audit history...</td></tr>';

            try {
                const url = event ? `${API_BASE}/auth/history?event=${encodeURIComponent(event)}` : `${API_BASE}/auth/history`;
                const res = await fetch(url, {
                    headers: {'Authorization': `Bearer ${apiToken}`, 'Accept': 'application/json'}
                });
                const data = await res.json();
                const logs = data.history?.data || data.history || [];

                tbody.innerHTML = '';
                if (logs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="py-4 text-center text-slate-400">No logs found matching criteria.</td></tr>';
                    return;
                }

                logs.forEach(log => {
                    const tr = document.createElement('tr');
                    const isSuccess = log.status === 'success';
                    const badgeClass = isSuccess ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';

                    tr.innerHTML = `
                        <td class="py-2 px-3 font-semibold text-slate-800">${escapeHtml(log.event)}</td>
                        <td class="py-2 px-3"><span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase ${badgeClass}">${escapeHtml(log.status)}</span></td>
                        <td class="py-2 px-3 font-mono">${escapeHtml(log.ip_address || '-')}</td>
                        <td class="py-2 px-3 truncate max-w-xs text-slate-500" title="${escapeHtml(log.user_agent || '')}">${escapeHtml(log.user_agent || '-')}</td>
                        <td class="py-2 px-3 text-slate-400">${escapeHtml(log.created_at || '-')}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } catch (err) {
                console.error(err);
                tbody.innerHTML = '<tr><td colspan="5" class="py-4 text-center text-red-500">Failed to load logs.</td></tr>';
            }
        }

        // Account Settings: Email Change
        async function requestEmailChange() {
            hideAlert();
            const new_email = document.getElementById('change-new-email').value.trim();
            const password = document.getElementById('change-email-password').value;

            if (!new_email || !password) {
                showAlert('Please provide both new email and current password.', 'error');
                return;
            }

            try {
                const res = await fetch(`${API_BASE}/auth/request-email-change`, {
                    method: 'POST',
                    headers: {'Authorization': `Bearer ${apiToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json'},
                    body: JSON.stringify({new_email, password})
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Request failed');

                showAlert(data.message || 'Confirmation code sent to your old email address.', 'info');
                document.getElementById('email-step-request').classList.add('hidden');
                document.getElementById('email-step-confirm').classList.remove('hidden');
            } catch (err) {
                showAlert(err.message, 'error');
            }
        }

        async function confirmEmailChange() {
            hideAlert();
            const token = document.getElementById('change-email-token').value.trim();
            if (!token) {
                showAlert('Please enter the 6-digit confirmation code.', 'error');
                return;
            }

            try {
                const res = await fetch(`${API_BASE}/auth/confirm-email-change`, {
                    method: 'POST',
                    headers: {'Authorization': `Bearer ${apiToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json'},
                    body: JSON.stringify({token})
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Failed to confirm email change');

                showAlert(data.message || 'Email successfully changed!', 'success');
                cancelEmailChange();
                initAuthenticatedSession();
            } catch (err) {
                showAlert(err.message, 'error');
            }
        }

        function cancelEmailChange() {
            document.getElementById('email-step-confirm').classList.add('hidden');
            document.getElementById('email-step-request').classList.remove('hidden');
            document.getElementById('change-new-email').value = '';
            document.getElementById('change-email-password').value = '';
            document.getElementById('change-email-token').value = '';
        }

        // Delete Account
        async function deleteAccount() {
            hideAlert();
            const password = document.getElementById('delete-account-password').value;
            const code = document.getElementById('delete-account-2fa').value.trim() || undefined;

            if (!password) {
                showAlert('Please enter your password to confirm account deletion.', 'error');
                return;
            }

            if (!confirm('This action cannot be undone! Are you absolutely sure you want to permanently delete your account?')) {
                return;
            }

            try {
                const res = await fetch(`${API_BASE}/auth/delete-account`, {
                    method: 'DELETE',
                    headers: {'Authorization': `Bearer ${apiToken}`, 'Content-Type': 'application/json', 'Accept': 'application/json'},
                    body: JSON.stringify({password, code})
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Account deletion failed');

                showAlert('Your account has been deleted permanently.', 'info');
                logout(false, false);
            } catch (err) {
                showAlert(err.message, 'error');
            }
        }

        // Logout
        async function logout(allDevices = false, notify = true) {
            hideAlert();
            try {
                if (apiToken) {
                    const endpoint = allDevices ? `${API_BASE}/auth/logout-all` : `${API_BASE}/auth/logout`;
                    await fetch(endpoint, {
                        method: 'POST',
                        headers: {'Authorization': `Bearer ${apiToken}`, 'Accept': 'application/json'}
                    });
                }
            } catch (err) {
                console.error(err);
            } finally {
                apiToken = null;
                currentUser = null;
                currentUserId = null;
                localStorage.removeItem('demo_api_token');
                showGuestUI();
                if (notify) {
                    showAlert(allDevices ? 'Logged out from all devices.' : 'Signed out successfully.', 'info');
                }
            }
        }

        // Helper utilities
        function copyToClipboard(elementId) {
            const text = document.getElementById(elementId).textContent;
            navigator.clipboard.writeText(text);
            showAlert('Copied to clipboard!', 'info');
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    </script>
</body>
</html>