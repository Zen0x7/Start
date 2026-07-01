<?php

namespace Tests\Feature\Console;

use Tests\TestCase;

class GenerateJwtKeysTest extends TestCase
{
    public function test_command_outputs_signing_key(): void
    {
        $this->artisan('make:jwt-keys')
            ->expectsOutputToContain('JWT_SIGNING_KEY')
            ->assertExitCode(0);
    }

    public function test_generated_key_is_valid_base64(): void
    {
        $this->artisan('make:jwt-keys')
            ->expectsOutputToContain('JWT_SIGNING_KEY=');
    }
}
