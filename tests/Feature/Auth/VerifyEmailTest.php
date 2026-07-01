<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    use LazilyRefreshDatabase;

    private JwtService $jwt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jwt = app(JwtService::class);
    }

    public function test_check_token_returns_email(): void
    {
        $token = $this->jwt->buildEmailVerificationToken('ian@example.com');

        $response = $this->getJson('/api/auth/verify-email/'.urlencode($token));

        $response->assertStatus(200);
        $response->assertJson(['email' => 'ian@example.com']);
    }

    public function test_check_token_with_non_request_type_rejected(): void
    {
        $token = $this->jwt->signAndEncrypt([
            'issued_at' => time(),
            'expires_at' => time() + 900,
            'type' => 'auth',
            'payload' => ['user_id' => '1'],
        ]);

        $response = $this->getJson('/api/auth/verify-email/'.urlencode($token));

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Token inválido.']);
    }

    public function test_check_token_without_email_rejected(): void
    {
        $token = $this->jwt->signAndEncrypt([
            'issued_at' => time(),
            'expires_at' => time() + 900,
            'type' => 'request',
            'payload' => [
                'method' => ['POST'],
                'path' => ['/api/auth/verify-email'],
                'data' => [],
            ],
        ]);

        $response = $this->getJson('/api/auth/verify-email/'.urlencode($token));

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Token inválido: falta el correo electrónico.']);
    }

    public function test_check_invalid_token_fails(): void
    {
        $response = $this->getJson('/api/auth/verify-email/invalid-token');

        $response->assertStatus(400);
    }

    public function test_verify_email_with_correct_password(): void
    {
        $user = User::factory()->create([
            'email' => 'ian@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => null,
        ]);

        $token = $this->jwt->buildEmailVerificationToken('ian@example.com');

        $response = $this->postJson('/api/auth/verify-email', [
            'token' => $token,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Correo electrónico confirmado exitosamente.',
        ]);
        $response->assertJsonStructure(['totp_status', 'temp_token', 'user']);
        $response->assertJson(['totp_status' => 'setup_required']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email_verified_at' => now(),
        ]);
    }

    public function test_verify_email_with_wrong_password_fails(): void
    {
        User::factory()->create([
            'email' => 'ian@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => null,
        ]);

        $token = $this->jwt->buildEmailVerificationToken('ian@example.com');

        $response = $this->postJson('/api/auth/verify-email', [
            'token' => $token,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'La contraseña ingresada es incorrecta.']);
    }

    public function test_verify_with_expired_token_fails(): void
    {
        User::factory()->create([
            'email' => 'ian@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => null,
        ]);

        $token = $this->jwt->signAndEncrypt([
            'issued_at' => time() - 1000,
            'expires_at' => time() - 1,
            'type' => 'request',
            'payload' => [
                'method' => ['POST'],
                'path' => ['/api/auth/verify-email'],
                'data' => ['email' => 'ian@example.com'],
            ],
        ]);

        $response = $this->postJson('/api/auth/verify-email', [
            'token' => $token,
            'password' => 'password123',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Token has expired']);
    }

    public function test_verify_with_non_existent_user_fails(): void
    {
        $token = $this->jwt->buildEmailVerificationToken('nobody@example.com');

        $response = $this->postJson('/api/auth/verify-email', [
            'token' => $token,
            'password' => 'password123',
        ]);

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Usuario no encontrado.']);
    }

    public function test_verify_with_token_for_wrong_email(): void
    {
        User::factory()->create([
            'email' => 'ian@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => null,
        ]);

        $token = $this->jwt->buildEmailVerificationToken('otro@example.com');

        $response = $this->postJson('/api/auth/verify-email', [
            'token' => $token,
            'password' => 'password123',
        ]);

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Usuario no encontrado.']);
    }

    public function test_verify_already_verified_email(): void
    {
        User::factory()->create([
            'email' => 'ian@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $token = $this->jwt->buildEmailVerificationToken('ian@example.com');

        $response = $this->postJson('/api/auth/verify-email', [
            'token' => $token,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'El correo electrónico ya está verificado.']);
    }

    public function test_resend_verification_email(): void
    {
        Notification::fake();

        User::factory()->create([
            'email' => 'ian@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => null,
        ]);

        RateLimiter::clear('verify-email:ian@example.com');

        $response = $this->postJson('/api/auth/resend-verification', [
            'email' => 'ian@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Correo de verificación reenviado.']);

        $user = User::where('email', 'ian@example.com')->first();
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_resend_with_nonexistent_email_returns_422(): void
    {
        RateLimiter::clear('verify-email:nobody@example.com');

        $response = $this->postJson('/api/auth/resend-verification', [
            'email' => 'nobody@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_resend_for_already_verified_user(): void
    {
        User::factory()->create([
            'email' => 'ian@example.com',
            'email_verified_at' => now(),
        ]);

        RateLimiter::clear('verify-email:ian@example.com');

        $response = $this->postJson('/api/auth/resend-verification', [
            'email' => 'ian@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'El correo electrónico ya está verificado.']);
    }

    public function test_resend_verification_is_throttled(): void
    {
        User::factory()->create([
            'email' => 'ian@example.com',
            'email_verified_at' => null,
        ]);

        RateLimiter::clear('verify-email:ian@example.com');

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/auth/resend-verification', [
                'email' => 'ian@example.com',
            ]);
        }

        $response = $this->postJson('/api/auth/resend-verification', [
            'email' => 'ian@example.com',
        ]);

        $response->assertStatus(429);
        $response->assertJson(['message' => 'Demasiadas solicitudes. Intenta de nuevo en 360 minutos.']);
    }

    public function test_verify_email_requires_token_and_password(): void
    {
        $response = $this->postJson('/api/auth/verify-email', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['token', 'password']);
    }

    public function test_resend_requires_email(): void
    {
        $response = $this->postJson('/api/auth/resend-verification', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_complete_verification_flow_returns_auth_token(): void
    {
        $user = User::factory()->create([
            'email' => 'ian@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => null,
        ]);

        $token = $this->jwt->buildEmailVerificationToken('ian@example.com');

        $response = $this->postJson('/api/auth/verify-email', [
            'token' => $token,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);

        $tempToken = $response->json('temp_token');
        $payload = $this->jwt->validateTotpChallengeToken($tempToken);
        $this->assertSame((string) $user->id, $payload['payload']['user_id']);
        $response->assertJson(['totp_status' => 'setup_required']);
    }

    public function test_verify_with_token_missing_email_field(): void
    {
        $token = $this->jwt->signAndEncrypt([
            'issued_at' => time(),
            'expires_at' => time() + 900,
            'type' => 'request',
            'payload' => [
                'method' => ['POST'],
                'path' => ['/api/auth/verify-email'],
                'data' => [],
            ],
        ]);

        $response = $this->postJson('/api/auth/verify-email', [
            'token' => $token,
            'password' => 'irrelevant',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Token inválido.']);
    }
}
