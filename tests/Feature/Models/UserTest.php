<?php

namespace Tests\Feature\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_password_is_hashed_automatically(): void
    {
        $user = User::factory()->create([
            'password' => 'plain-text-password',
        ]);

        $this->assertNotSame('plain-text-password', $user->password);
        $this->assertTrue(password_verify('plain-text-password', $user->password));
    }

    public function test_email_verified_at_is_datetime_cast(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->assertInstanceOf(Carbon::class, $user->email_verified_at);
    }

    public function test_email_verified_at_is_null_when_unverified(): void
    {
        $user = User::factory()->unverified()->create();

        $this->assertNull($user->email_verified_at);
    }

    public function test_hidden_attributes_are_not_serialized(): void
    {
        $user = User::factory()->create();
        $json = $user->toArray();

        $this->assertArrayNotHasKey('password', $json);
        $this->assertArrayNotHasKey('remember_token', $json);
    }

    public function test_avatar_returns_gravatar_url(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'profile_photo_path' => null,
        ]);

        $hash = md5('test@example.com');
        $this->assertStringContainsString("gravatar.com/avatar/{$hash}", $user->avatar);
        $this->assertStringContainsString('s=200', $user->avatar);
        $this->assertStringContainsString('s=48', $user->avatar_thumb);
    }

    public function test_avatar_returns_local_path_when_set(): void
    {
        $user = User::factory()->create([
            'profile_photo_path' => 'profile-photos/test.jpg',
        ]);

        $this->assertStringContainsString('storage/profile-photos/test.jpg', $user->avatar);
        $this->assertStringContainsString('storage/profile-photos/test.jpg', $user->avatar_thumb);
    }
}
