<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Services\JwtService;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use OTPHP\TOTP;
use Tests\TestCase;

class ProfileTest extends TestCase
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

    private function authToken(User $user): string
    {
        return $this->jwt->buildAuthToken((string) $user->id);
    }

    public function test_show_returns_profile_and_devices(): void
    {
        $user = User::factory()->create();
        $this->totp->createDevice($user, TOTP::generate()->getSecret(), 'My Phone');

        $response = $this->getJson('/api/auth/profile', [
            'Authorization' => 'Bearer '.$this->authToken($user),
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'avatar', 'avatar_thumb'],
            'totp_devices',
        ]);
        $this->assertCount(1, $response->json('totp_devices'));
        $this->assertSame('My Phone', $response->json('totp_devices.0.label'));
        $this->assertStringContainsString('gravatar.com', $response->json('user.avatar'));
    }

    public function test_update_name(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson('/api/auth/profile', [
            'name' => 'New Name',
            'email' => $user->email,
        ], [
            'Authorization' => 'Bearer '.$this->authToken($user),
        ]);

        $response->assertStatus(200);
        $this->assertSame('New Name', $user->fresh()->name);
    }

    public function test_update_email_sends_verification(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->putJson('/api/auth/profile', [
            'name' => $user->name,
            'email' => 'new@example.com',
        ], [
            'Authorization' => 'Bearer '.$this->authToken($user),
        ]);

        $response->assertStatus(200);
        $response->assertJson(['email_changed' => true]);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_update_email_duplicate_fails(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'user@example.com']);

        $response = $this->putJson('/api/auth/profile', [
            'name' => $user->name,
            'email' => 'taken@example.com',
        ], [
            'Authorization' => 'Bearer '.$this->authToken($user),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_update_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $response = $this->postJson('/api/auth/profile/photo', [
            'photo' => $file,
        ], [
            'Authorization' => 'Bearer '.$this->authToken($user),
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['avatar', 'avatar_thumb']);
        $this->assertNotNull($user->fresh()->profile_photo_path);
    }

    public function test_delete_account_with_totp(): void
    {
        $user = User::factory()->create();
        $totp = TOTP::generate();
        $this->totp->createDevice($user, $totp->getSecret());

        $response = $this->postJson('/api/auth/profile/delete', [
            'totp_code' => $totp->now(),
        ], [
            'Authorization' => 'Bearer '.$this->authToken($user),
        ]);

        $response->assertStatus(200);
        $this->assertModelMissing($user);
    }

    public function test_delete_account_without_totp_fails(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/profile/delete', [
            'totp_code' => '000000',
        ], [
            'Authorization' => 'Bearer '.$this->authToken($user),
        ]);

        $response->assertStatus(422);
        $this->assertModelExists($user);
    }

    public function test_delete_totp_device(): void
    {
        $user = User::factory()->create();
        $totp = TOTP::generate();
        $this->totp->createDevice($user, $totp->getSecret(), 'Primary');
        $totp2 = TOTP::generate();
        $device2 = $this->totp->createDevice($user, $totp2->getSecret(), 'Secondary');

        $response = $this->postJson('/api/auth/totp/devices/delete', [
            'device_id' => $device2->id,
            'totp_code' => $totp2->now(),
        ], [
            'Authorization' => 'Bearer '.$this->authToken($user),
        ]);

        $response->assertStatus(200);
        $this->assertModelMissing($device2);
    }

    public function test_delete_totp_device_with_wrong_code_fails(): void
    {
        $user = User::factory()->create();
        $totp = TOTP::generate();
        $this->totp->createDevice($user, $totp->getSecret(), 'Primary');
        $totp2 = TOTP::generate();
        $device2 = $this->totp->createDevice($user, $totp2->getSecret(), 'Secondary');

        $response = $this->postJson('/api/auth/totp/devices/delete', [
            'device_id' => $device2->id,
            'totp_code' => '000000',
        ], [
            'Authorization' => 'Bearer '.$this->authToken($user),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['totp_code']);
    }

    public function test_update_photo_replaces_old(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $file1 = UploadedFile::fake()->image('first.jpg');
        $this->postJson('/api/auth/profile/photo', ['photo' => $file1], [
            'Authorization' => 'Bearer '.$this->authToken($user),
        ]);
        $oldPath = $user->fresh()->profile_photo_path;

        $file2 = UploadedFile::fake()->image('second.jpg');
        $response = $this->postJson('/api/auth/profile/photo', ['photo' => $file2], [
            'Authorization' => 'Bearer '.$this->authToken($user),
        ]);

        $response->assertStatus(200);
        Storage::disk('public')->assertMissing($oldPath);
    }
}
