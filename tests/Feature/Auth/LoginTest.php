<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use LazilyRefreshDatabase;

    private JwtService $jwt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jwt = app(JwtService::class);
    }

    public function test_user_with_verified_email_can_login(): void
    {
        $user = User::factory()->create([
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
            'token',
            'user' => ['id', 'name', 'email'],
        ]);

        $payload = $this->jwt->validateAuthToken($response->json('token'));
        $this->assertSame((string) $user->id, $payload['payload']['user_id']);
    }

    public function test_user_with_unverified_email_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'ian@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'ian@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Antes de continuar deberás confirmar tu correo electrónico.',
            'email' => 'ian@example.com',
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
