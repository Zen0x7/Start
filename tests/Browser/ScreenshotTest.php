<?php

namespace Tests\Browser;

use App\Models\LoginAttempt;
use App\Models\TotpUsageLog;
use App\Models\User;
use App\Services\JwtService;
use App\Services\TotpService;
use Laravel\Dusk\Browser;
use OTPHP\TOTP;
use Tests\DuskTestCase;

class ScreenshotTest extends DuskTestCase
{
    public function test_screenshots(): void
    {
        $email = 'ian'.uniqid().'@start.test';
        $user = User::factory()->create([
            'name' => 'Ian Torres',
            'email' => $email,
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $jwt = app(JwtService::class);
        $totpService = app(TotpService::class);
        $totp = TOTP::generate();
        $device = $totpService->createDevice($user, $totp->getSecret(), 'iPhone');
        $totpService->createDevice($user, TOTP::generate()->getSecret(), 'Recovery Key');
        $token = $jwt->buildAuthToken((string) $user->id);
        $userId = $user->id;

        // Seed activity data
        LoginAttempt::create([
            'user_id' => $userId, 'email' => $email, 'successful' => true,
            'ip_address' => '192.168.1.42', 'user_agent' => 'Chrome/130.0',
            'created_at' => now()->subMinutes(5),
        ]);
        LoginAttempt::create([
            'user_id' => $userId, 'email' => $email, 'successful' => false,
            'ip_address' => '10.0.0.1', 'user_agent' => 'Firefox/132.0',
            'created_at' => now()->subHour(),
        ]);
        TotpUsageLog::create([
            'user_id' => $userId, 'totp_device_id' => $device->id, 'action' => 'login',
            'ip_address' => '192.168.1.42', 'user_agent' => 'Chrome/130.0',
            'created_at' => now()->subMinutes(10),
        ]);

        $this->browse(function (Browser $browser) use ($token, $userId, $email, $jwt) {
            $setEn = function (Browser $b) {
                $b->script(["localStorage.setItem('app_locale', '\"en\"');"]);
                $b->refresh()->pause(4000);
            };

            $login = function (Browser $b) use ($token, $userId, $email) {
                $b->script([
                    "localStorage.setItem('auth_token', '{$token}');",
                    "localStorage.setItem('auth_user', '{\"id\":{$userId},\"name\":\"Ian Torres\",\"email\":\"{$email}\"}');",
                    "localStorage.setItem('app_locale', '\"en\"');",
                ]);
                $b->refresh()->pause(5000);
            };

            // Auth pages
            $browser->visit('/login?lang=en');
            $setEn($browser);
            $browser->screenshot('01-login');
            $browser->visit('/register?lang=en');
            $setEn($browser);
            $browser->screenshot('02-register');
            $browser->visit('/email/verify?lang=en&email='.urlencode($email));
            $setEn($browser);
            $browser->screenshot('03-verify-email');

            $verifyToken = $jwt->buildEmailVerificationToken($email);
            $browser->visit('/email/verify/'.urlencode($verifyToken).'?lang=en');
            $setEn($browser);
            $browser->screenshot('04-confirm-email');
            $browser->visit('/forgot-password?lang=en');
            $setEn($browser);
            $browser->screenshot('05-forgot-password');
            $browser->visit('/reset-password/'.urlencode($verifyToken).'?lang=en');
            $setEn($browser);
            $browser->screenshot('06-reset-password');

            // TOTP setup — requires auth
            $browser->visit('/totp/setup?lang=en');
            $login($browser);
            $browser->screenshot('07-totp-setup');

            // TOTP verify — requires auth
            $browser->visit('/totp/verify?lang=en');
            $login($browser);
            $browser->screenshot('08-totp-verify');

            // Dashboard
            $browser->visit('/dashboard?lang=en');
            $login($browser);
            $browser->screenshot('09-dashboard');

            // Settings
            $browser->visit('/settings?lang=en');
            $login($browser);
            $browser->screenshot('10-settings');

            $browser->script(["document.querySelectorAll('button').forEach(function(b) { if (b.textContent.includes('Keys')) b.click(); });"]);
            $browser->pause(1000)->screenshot('11-settings-keys');
            $browser->script(["document.querySelectorAll('button').forEach(function(b) { if (b.textContent.includes('Activity')) b.click(); });"]);
            $browser->pause(1000)->screenshot('12-settings-activity');
            $browser->script(["document.querySelectorAll('button').forEach(function(b) { if (b.textContent.includes('Destructive')) b.click(); });"]);
            $browser->pause(1000)->screenshot('13-settings-destructive');

            // Convert PNGs to WebP
            $dir = __DIR__.'/screenshots';
            foreach (glob($dir.'/*.png') as $png) {
                $webp = preg_replace('/\.png$/', '.webp', $png);
                $im = @imagecreatefrompng($png);
                if ($im) {
                    imagepalettetotruecolor($im);
                    imagewebp($im, $webp, 85);
                    imagedestroy($im);
                    unlink($png);
                }
            }
        });
    }
}
