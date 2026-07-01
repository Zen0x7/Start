<?php

namespace App\Console\Commands;

use App\Models\TotpCertificate;
use Illuminate\Console\Command;

class CleanTotpCertificates extends Command
{
    protected $signature = 'totp:clean-certificates';

    protected $description = 'Remove expired TOTP setup certificates';

    public function handle(): int
    {
        $deleted = TotpCertificate::where('expires_at', '<', now())->delete();

        $this->info("Deleted {$deleted} expired certificate(s).");

        return self::SUCCESS;
    }
}
