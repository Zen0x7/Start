<?php

namespace Tests\Feature\Models;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LoginAttemptModelTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_attempt_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $attempt = LoginAttempt::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'successful' => true,
        ]);

        $this->assertInstanceOf(User::class, $attempt->user);
        $this->assertSame($user->id, $attempt->user->id);
    }
}
