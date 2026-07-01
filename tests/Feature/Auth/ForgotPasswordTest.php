<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\JwtService;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use OTPHP\TOTP;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use LazilyRefreshDatabase;

    private JwtService $jwt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwt = app(JwtService::class);
    }

    public function test_send_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/auth/password/email', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => __('passwords.sent')]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_send_reset_link_nonexistent_email(): void
    {
        $response = $this->postJson('/api/auth/password/email', [
            'email' => 'nobody@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_check_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $token = $this->jwt->buildEmailVerificationToken($user->email);

        $response = $this->getJson('/api/auth/password/reset/'.urlencode($token));

        $response->assertStatus(200);
        $response->assertJson([
            'email' => 'test@example.com',
            'has_totp' => false,
        ]);
    }

    public function test_check_token_with_totp(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        app(TotpService::class)->createDevice($user, TOTP::generate()->getSecret());
        $token = $this->jwt->buildEmailVerificationToken($user->email);

        $response = $this->getJson('/api/auth/password/reset/'.urlencode($token));

        $response->assertStatus(200);
        $response->assertJson([
            'email' => 'test@example.com',
            'has_totp' => true,
        ]);
    }

    public function test_check_invalid_token(): void
    {
        $response = $this->getJson('/api/auth/password/reset/invalid-token');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['token']);
    }

    public function test_check_token_without_email_in_payload(): void
    {
        $token = $this->jwt->signAndEncrypt([
            'issued_at' => time(),
            'expires_at' => time() + 900,
            'type' => 'request',
            'payload' => [
                'method' => ['POST'],
                'path' => ['/api/auth/password/reset'],
                'data' => [],
            ],
        ]);

        $response = $this->getJson('/api/auth/password/reset/' . urlencode($token));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['token']);
    }

    public function test_reset_notification_uses_locale(): void
    {
        $user = User::factory()->create(['name' => 'Test User', 'locale' => 'en']);
        $token = $this->jwt->buildEmailVerificationToken($user->email);

        $notification = (new \App\Notifications\ResetPasswordNotification($token))->locale($user->locale);
        $mail = $notification->toMail($user);

        $this->assertSame('Reset your password', $mail->subject);
    }

    public function test_check_token_with_nonexistent_user(): void
    {
        $token = $this->jwt->buildEmailVerificationToken('nobody@example.com');

        $response = $this->getJson('/api/auth/password/reset/' . urlencode($token));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['token']);
    }
}
