<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateJwtKeys extends Command
{
    protected $signature = 'make:jwt-keys';

    protected $description = 'Generate a JWT_SIGNING_KEY for token signing and encryption';

    public function handle(): int
    {
        $key = 'JWT_SIGNING_KEY='.base64_encode(random_bytes(32));

        $this->info('JWT_SIGNING_KEY generated:');
        $this->line($key);
        $this->newLine();
        $this->line('Add this line to your .env file.');

        return self::SUCCESS;
    }
}
