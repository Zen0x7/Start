<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_with_verified_email_gets_totp_status(): void
    {
        User::factory()->create([
            'email' => 'ian@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'ian@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'totp_status',
            'temp_token',
            'user' => ['id', 'name', 'email'],
        ]);
        $this->assertContains($response->json('totp_status'), ['setup_required', 'verify_required']);

        $tempToken = $response->json('temp_token');
        $this->assertNotEmpty($tempToken);

        $payload = app(JwtService::class)->validateTotpChallengeToken($tempToken);
        $this->assertSame((string) $response->json('user.id'), $payload['payload']['user_id']);
    }

    public function test_login_returns_setup_required_when_no_totp_devices(): void
    {
        User::factory()->create([
            'email' => 'ian@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'ian@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['totp_status' => 'setup_required']);
    }

    public function test_user_with_unverified_email_cannot_login(): void
    {
        User::factory()->unverified()->create([
            'email' => 'ian@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'ian@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => __('auth.unverified'),
        ]);
    }

    public function test_login_with_wrong_password_fails(): void
    {
        User::factory()->create([
            'email' => 'ian@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'ian@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_login_with_nonexistent_email_fails(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
    }
}
