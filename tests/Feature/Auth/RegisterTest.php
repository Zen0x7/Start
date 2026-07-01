<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_register(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ian',
            'email' => 'ian@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Cuenta creada. Revisa tu correo para confirmar tu dirección de correo electrónico.',
            'email' => 'ian@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'ian@example.com',
            'email_verified_at' => null,
        ]);

        $user = User::where('email', 'ian@example.com')->first();
        $this->assertNotNull($user);

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_registration_requires_name(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'ian@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_registration_requires_valid_email(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ian',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'ian@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ian',
            'email' => 'ian@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_registration_requires_minimum_password_length(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ian',
            'email' => 'ian@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_registration_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ian',
            'email' => 'ian@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_verify_email_notification_contains_token(): void
    {
        Notification::fake();

        $this->postJson('/api/auth/register', [
            'name' => 'Ian',
            'email' => 'ian@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'ian@example.com')->first();

        Notification::assertSentTo($user, VerifyEmailNotification::class, function ($notification) {
            $this->assertIsString($notification->token);
            $this->assertNotEmpty($notification->token);

            $mail = $notification->toMail(User::where('email', 'ian@example.com')->first());
            $this->assertStringContainsString('Confirmar Correo Electrónico', $mail->render());

            return true;
        });
    }
}
