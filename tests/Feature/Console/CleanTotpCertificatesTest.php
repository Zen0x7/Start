<?php

namespace Tests\Feature\Console;

use App\Models\TotpCertificate;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CleanTotpCertificatesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_deletes_expired_certificates(): void
    {
        $user = User::factory()->create();

        $expired = TotpCertificate::create([
            'user_id' => $user->id,
            'public_key' => 'test',
            'private_key' => 'test',
            'signature' => 'test',
            'expires_at' => now()->subHour(),
        ]);

        $valid = TotpCertificate::create([
            'user_id' => $user->id,
            'public_key' => 'test',
            'private_key' => 'test',
            'signature' => 'test',
            'expires_at' => now()->addHour(),
        ]);

        $this->artisan('totp:clean-certificates')
            ->expectsOutputToContain('Deleted 1 expired certificate(s).')
            ->assertExitCode(0);

        $this->assertModelMissing($expired);
        $this->assertModelExists($valid);
    }
}
