<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel 2FA API Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
            <h1 class="text-2xl font-bold text-center mb-6">Laravel 2FA API Demo</h1>
            
            <div id="app" class="space-y-4">
                <!-- Auth Section -->
                <div id="auth-section" class="space-y-4">
                    <div class="space-y-2">
                        <input type="email" id="email" placeholder="Email" class="w-full p-2 border rounded">
                        <input type="password" id="password" placeholder="Password" class="w-full p-2 border rounded">
                        <input type="text" id="name" placeholder="Name (for register)" class="w-full p-2 border rounded hidden">
                    </div>
                    
                    <div class="space-x-2">
                        <button onclick="login()" class="bg-blue-500 text-white px-4 py-2 rounded">Login</button>
                        <button onclick="toggleRegister()" id="toggle-btn" class="bg-gray-500 text-white px-4 py-2 rounded">Register</button>
                        <button onclick="logout()" class="bg-red-500 text-white px-4 py-2 rounded hidden" id="logout-btn">Logout</button>
                    </div>
                </div>

                <!-- 2FA Verification Section -->
                <div id="2fa-verify-section" class="hidden space-y-4">
                    <h3 class="font-bold">Enter 2FA Code</h3>
                    <input type="text" id="2fa-code" placeholder="Enter 6-digit code" class="w-full p-2 border rounded">
                    <button onclick="verify2FA()" class="bg-green-500 text-white px-4 py-2 rounded">Verify</button>
                    <button onclick="cancel2FA()" class="bg-gray-500 text-white px-4 py-2 rounded">Cancel</button>
                </div>

                <!-- 2FA Management Section -->
                <div id="2fa-section" class="hidden space-y-4">
                    <h3 class="font-bold">Two-Factor Authentication</h3>
                    <div id="2fa-status" class="p-2 border rounded"></div>
                    
                    <div id="2fa-setup" class="hidden space-y-4">
                        <div id="qr-code" class="p-4 border rounded"></div>
                        <div class="text-sm">
                            <p>Secret Key: <span id="secret-key" class="font-mono"></span></p>
                            <p>Scan QR code with Google Authenticator app</p>
                        </div>
                        <input type="text" id="enable-code" placeholder="Enter code from app" class="w-full p-2 border rounded">
                        <button onclick="enable2FA()" class="bg-green-500 text-white px-4 py-2 rounded">Enable 2FA</button>
                    </div>
                    
                    <div class="space-x-2">
                        <button onclick="setup2FA()" id="setup-btn" class="bg-blue-500 text-white px-4 py-2 rounded">Setup 2FA</button>
                        <button onclick="disable2FA()" id="disable-btn" class="bg-red-500 text-white px-4 py-2 rounded hidden">Disable 2FA</button>
                    </div>
                </div>

                <!-- Message Display -->
                <div id="message" class="p-2 rounded hidden"></div>
            </div>
        </div>
    </div>

    <script>
        let apiToken = null;
        let currentUserId = null;
        let isRegistering = false;

        const API_BASE = '/api';

        function showMessage(text, type = 'info') {
            const messageDiv = document.getElementById('message');
            messageDiv.textContent = text;
            messageDiv.className = `p-2 rounded ${type === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'}`;
            messageDiv.classList.remove('hidden');
            setTimeout(() => messageDiv.classList.add('hidden'), 5000);
        }

        function toggleRegister() {
            isRegistering = !isRegistering;
            document.getElementById('name').classList.toggle('hidden');
            document.getElementById('toggle-btn').textContent = isRegistering ? 'Login' : 'Register';
        }

        async function login() {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (isRegistering) {
                const name = document.getElementById('name').value;
                const response = await fetch(`${API_BASE}/auth/register`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({name, email, password})
                });
                
                const data = await response.json();
                if (response.ok) {
                    apiToken = data.token;
                    currentUserId = data.user.id;
                    updateUIAfterLogin();
                    showMessage('Registration successful!');
                } else {
                    showMessage(data.message || 'Registration failed', 'error');
                }
            } else {
                const response = await fetch(`${API_BASE}/auth/login`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({email, password})
                });
                
                const data = await response.json();
                if (response.ok) {
                    if (data.requires_2fa) {
                        currentUserId = data.user_id;
                        document.getElementById('auth-section').classList.add('hidden');
                        document.getElementById('2fa-verify-section').classList.remove('hidden');
                        showMessage('2FA verification required');
                    } else {
                        apiToken = data.token;
                        currentUserId = data.user.id;
                        updateUIAfterLogin();
                        showMessage('Login successful!');
                    }
                } else {
                    showMessage(data.message || 'Login failed', 'error');
                }
            }
        }

        async function verify2FA() {
            const code = document.getElementById('2fa-code').value;
            const response = await fetch(`${API_BASE}/auth/verify-2fa`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({user_id: currentUserId, code})
            });
            
            const data = await response.json();
            if (response.ok) {
                apiToken = data.token;
                document.getElementById('2fa-verify-section').classList.add('hidden');
                updateUIAfterLogin();
                showMessage('2FA verified successfully!');
            } else {
                showMessage(data.message || 'Invalid code', 'error');
            }
        }

        function cancel2FA() {
            document.getElementById('2fa-verify-section').classList.add('hidden');
            document.getElementById('auth-section').classList.remove('hidden');
        }

        async function setup2FA() {
            const response = await fetch(`${API_BASE}/2fa/generate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${apiToken}`
                }
            });
            
            const data = await response.json();
            if (response.ok) {
                document.getElementById('secret-key').textContent = data.secret;
                document.getElementById('qr-code').innerHTML = data.qr_code_svg;
                document.getElementById('2fa-setup').classList.remove('hidden');
                showMessage('Scan QR code with Google Authenticator');
            } else {
                showMessage('Failed to generate 2FA', 'error');
            }
        }

        async function enable2FA() {
            const code = document.getElementById('enable-code').value;
            const response = await fetch(`${API_BASE}/2fa/enable`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${apiToken}`
                },
                body: JSON.stringify({code})
            });
            
            const data = await response.json();
            if (response.ok) {
                check2FAStatus();
                document.getElementById('2fa-setup').classList.add('hidden');
                showMessage('2FA enabled successfully!');
                alert('Recovery codes: ' + data.recovery_codes.join(', '));
            } else {
                showMessage(data.message || 'Invalid code', 'error');
            }
        }

        async function disable2FA() {
            const response = await fetch(`${API_BASE}/2fa/disable`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${apiToken}`
                }
            });
            
            const data = await response.json();
            if (response.ok) {
                check2FAStatus();
                showMessage('2FA disabled successfully!');
            } else {
                showMessage('Failed to disable 2FA', 'error');
            }
        }

        async function check2FAStatus() {
            const response = await fetch(`${API_BASE}/2fa/status`, {
                headers: {'Authorization': `Bearer ${apiToken}`}
            });
            
            const data = await response.json();
            if (response.ok) {
                const statusDiv = document.getElementById('2fa-status');
                if (data.google2fa_enabled) {
                    statusDiv.innerHTML = '<p class="text-green-600">✓ 2FA is enabled</p>';
                    document.getElementById('setup-btn').classList.add('hidden');
                    document.getElementById('disable-btn').classList.remove('hidden');
                } else {
                    statusDiv.innerHTML = '<p class="text-red-600">✗ 2FA is disabled</p>';
                    document.getElementById('setup-btn').classList.remove('hidden');
                    document.getElementById('disable-btn').classList.add('hidden');
                }
            }
        }

        async function logout() {
            const response = await fetch(`${API_BASE}/auth/logout`, {
                method: 'POST',
                headers: {'Authorization': `Bearer ${apiToken}`}
            });
            
            if (response.ok) {
                apiToken = null;
                currentUserId = null;
                document.getElementById('auth-section').classList.remove('hidden');
                document.getElementById('2fa-section').classList.add('hidden');
                document.getElementById('logout-btn').classList.add('hidden');
                showMessage('Logged out successfully');
            }
        }

        function updateUIAfterLogin() {
            document.getElementById('auth-section').classList.add('hidden');
            document.getElementById('2fa-section').classList.remove('hidden');
            document.getElementById('logout-btn').classList.remove('hidden');
            check2FAStatus();
        }
    </script>
</body>
</html>