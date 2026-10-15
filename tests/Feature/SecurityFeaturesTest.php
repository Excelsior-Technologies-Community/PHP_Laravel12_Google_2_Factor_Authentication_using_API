<?php

namespace Tests\Feature;

use App\Models\AuthenticationLog;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class SecurityFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_login()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePassword123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
        $this->assertDatabaseHas('authentication_logs', ['event' => 'Register']);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'SecurePassword123!',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_logout_and_logout_all_devices()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $token1 = $user->createToken('device_1')->plainTextToken;
        $token2 = $user->createToken('device_2')->plainTextToken;
        $token3 = $user->createToken('device_3')->plainTextToken;

        $this->assertEquals(3, $user->tokens()->count());

        // Logout current device token
        $response = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200);
        $this->assertEquals(2, $user->fresh()->tokens()->count());

        // Logout all devices
        $logoutAllResponse = $this->withHeader('Authorization', 'Bearer ' . $token2)
            ->postJson('/api/auth/logout-all');

        $logoutAllResponse->assertStatus(200);
        $this->assertEquals(0, $user->fresh()->tokens()->count());
    }

    public function test_two_factor_enable_generates_recovery_codes_and_can_confirm()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        $user->google2fa_secret = $secret;
        $user->save();

        $code = $google2fa->getCurrentOtp($secret);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/2fa/enable', ['code' => $code]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['recovery_codes', 'notice']);

        $this->assertTrue($user->fresh()->google2fa_enabled);
        $this->assertCount(8, $user->fresh()->two_factor_recovery_codes);
        $this->assertNull($user->fresh()->recovery_codes_confirmed_at);

        // Confirm recovery codes
        $confirmResponse = $this->actingAs($user->fresh(), 'sanctum')
            ->postJson('/api/2fa/confirm-recovery-codes', ['confirmed' => true]);

        $confirmResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertNotNull($user->fresh()->recovery_codes_confirmed_at);
    }

    public function test_two_factor_recovery_code_authentication()
    {
        $user = User::factory()->create([
            'email' => 'recover@example.com',
            'google2fa_enabled' => true,
            'google2fa_secret' => (new Google2FA())->generateSecretKey(),
        ]);

        $plainRecoveryCodes = $user->generateAndStoreRecoveryCodes();
        $this->assertCount(8, $plainRecoveryCodes);

        $firstCode = $plainRecoveryCodes[0];

        $response = $this->postJson('/api/auth/recover-2fa', [
            'email' => 'recover@example.com',
            'recovery_code' => $firstCode,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('remaining_recovery_codes', 7);

        $this->assertCount(7, $user->fresh()->two_factor_recovery_codes);
    }

    public function test_trusted_device_selectable_duration_and_revocation()
    {
        $user = User::factory()->create();

        $device = TrustedDevice::create([
            'user_id' => $user->id,
            'device_token' => 'dummy-token-12345',
            'device_name' => 'Chrome on Windows',
            'browser' => 'Chrome',
            'platform' => 'Windows',
            'ip_address' => '127.0.0.1',
            'last_used_at' => now(),
            'expires_at' => now()->addDays(15),
        ]);

        $this->assertFalse($device->isExpired());

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/trusted-devices');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Revoke device
        $deleteResponse = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/auth/trusted-devices/{$device->id}");

        $deleteResponse->assertStatus(200);
        $this->assertDatabaseMissing('trusted_devices', ['id' => $device->id]);
    }

    public function test_email_change_request_and_confirmation()
    {
        $user = User::factory()->create([
            'email' => 'old_email@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Request change
        $reqResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/request-email-change', [
                'new_email' => 'new_email@example.com',
                'current_password' => 'password123',
            ]);

        $reqResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $code = $reqResponse->json('code_preview');

        // Confirm change
        $confirmResponse = $this->actingAs($user->fresh(), 'sanctum')
            ->postJson('/api/auth/confirm-email-change', [
                'code' => $code,
            ]);

        $confirmResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals('new_email@example.com', $user->fresh()->email);
    }

    public function test_account_deletion_requires_password_and_2fa()
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct_password'),
            'google2fa_enabled' => false,
        ]);

        $user->createToken('test_token');
        TrustedDevice::create([
            'user_id' => $user->id,
            'device_token' => 'test_device_token',
            'expires_at' => now()->addDays(30),
        ]);

        // Wrong password should fail
        $failResponse = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/auth/delete-account', [
                'password' => 'wrong_password',
            ]);

        $failResponse->assertStatus(422);

        // Correct password succeeds
        $successResponse = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/auth/delete-account', [
                'password' => 'correct_password',
            ]);

        $successResponse->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('trusted_devices', ['user_id' => $user->id]);
    }

    public function test_security_dashboard_and_audit_logs()
    {
        $user = User::factory()->create([
            'google2fa_enabled' => true,
        ]);

        AuthenticationLog::create([
            'user_id' => $user->id,
            'event' => 'Login',
            'status' => 'Success',
            'ip_address' => '127.0.0.1',
            'description' => 'Login succeeded',
        ]);

        AuthenticationLog::create([
            'user_id' => $user->id,
            'event' => 'Login',
            'status' => 'Failed',
            'ip_address' => '127.0.0.1',
            'description' => 'Failed login',
        ]);

        // Dashboard
        $dashResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/security-dashboard');

        $dashResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'two_factor',
                    'last_login',
                    'successful_logins_total',
                    'failed_logins_recent_7_days',
                    'trusted_devices',
                    'active_sessions',
                    'security_health' => [
                        'score',
                        'rating',
                        'recommendations',
                    ],
                ]
            ]);

        // Filtered History
        $historyResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/history?event=Login&status=Success');

        $historyResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertCount(1, $historyResponse->json('data.data'));
    }
}
