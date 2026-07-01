<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use App\Services\JwtService;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use OTPHP\TOTP;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use LazilyRefreshDatabase;

    private JwtService $jwt;

    private TotpService $totp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwt = app(JwtService::class);
        $this->totp = app(TotpService::class);
    }

    public function test_reset_without_totp(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('oldpassword'),
        ]);

        $token = $this->jwt->buildEmailVerificationToken($user->email);

        $response = $this->postJson('/api/auth/password/reset', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => __('passwords.reset')]);

        $this->assertTrue(password_verify('newpassword123', $user->fresh()->password));

        Notification::assertSentTo($user, PasswordChangedNotification::class);
    }

    public function test_reset_with_totp(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('oldpassword'),
        ]);

        $totp = TOTP::generate();
        $this->totp->createDevice($user, $totp->getSecret());

        $token = $this->jwt->buildEmailVerificationToken($user->email);

        $response = $this->postJson('/api/auth/password/reset', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'totp_code' => $totp->now(),
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => __('passwords.reset')]);

        $this->assertTrue(password_verify('newpassword123', $user->fresh()->password));
    }

    public function test_reset_with_totp_requires_code(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('oldpassword'),
        ]);

        $totp = TOTP::generate();
        $this->totp->createDevice($user, $totp->getSecret());

        $token = $this->jwt->buildEmailVerificationToken($user->email);

        $response = $this->postJson('/api/auth/password/reset', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['totp_code']);
    }

    public function test_reset_with_invalid_token(): void
    {
        $response = $this->postJson('/api/auth/password/reset', [
            'token' => 'invalid',
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_with_wrong_email(): void
    {
        $user = User::factory()->create(['email' => 'real@example.com']);
        $token = $this->jwt->buildEmailVerificationToken($user->email);

        $response = $this->postJson('/api/auth/password/reset', [
            'token' => $token,
            'email' => 'wrong@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_with_invalid_totp_code(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('oldpassword'),
        ]);

        $totp = TOTP::generate();
        $this->totp->createDevice($user, $totp->getSecret());

        $token = $this->jwt->buildEmailVerificationToken($user->email);

        $response = $this->postJson('/api/auth/password/reset', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'totp_code' => '000000',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['totp_code']);
    }
}
