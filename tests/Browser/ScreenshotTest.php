<?php

namespace Tests\Browser;

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
        $email = 'ian' . uniqid() . '@start.test';
        $user = User::factory()->create([
            'name' => 'Ian Torres',
            'email' => $email,
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $jwt = app(JwtService::class);
        $totpService = app(TotpService::class);
        $totp = TOTP::generate();
        $totpService->createDevice($user, $totp->getSecret(), 'iPhone');
        $totpService->createDevice($user, TOTP::generate()->getSecret(), 'Recovery Key');
        $token = $jwt->buildAuthToken((string) $user->id);
        $userId = $user->id;

        $this->browse(function (Browser $browser) use ($token, $userId, $email) {
            $browser->visit('/login')->pause(4000)->screenshot('01-login');
            $browser->visit('/register')->pause(3000)->screenshot('02-register');
            $browser->visit('/forgot-password')->pause(3000)->screenshot('03-forgot-password');
            $browser->visit('/email/verify?email=' . urlencode($email))->pause(3000)->screenshot('04-verify-email');

            $browser->visit('/dashboard')->pause(1000);
            $browser->script([
                "localStorage.setItem('auth_token', '{$token}');",
                "localStorage.setItem('auth_user', '{\"id\":{$userId},\"name\":\"Ian Torres\",\"email\":\"{$email}\"}');",
            ]);
            $browser->refresh()->pause(5000)->screenshot('05-dashboard');

            $browser->visit('/settings')->pause(5000)->screenshot('06-settings');

            $clickKeys = 'document.querySelectorAll(\'button\').forEach(function(b) { if (b.textContent.includes(\'Keys\') || b.textContent.includes(\'Claves\')) b.click(); });';
            $browser->script([$clickKeys]);
            $browser->pause(1000)->screenshot('07-settings-totp');

            $clickActivity = 'document.querySelectorAll(\'button\').forEach(function(b) { if (b.textContent.includes(\'Activity\') || b.textContent.includes(\'Actividad\')) b.click(); });';
            $browser->script([$clickActivity]);
            $browser->pause(1000)->screenshot('08-settings-activity');

            $clickDanger = 'document.querySelectorAll(\'button\').forEach(function(b) { if (b.textContent.includes(\'Destructive\') || b.textContent.includes(\'Eliminar\')) b.click(); });';
            $browser->script([$clickDanger]);
            $browser->pause(1000)->screenshot('09-settings-destructive');

            // Convert PNGs to WebP
            $dir = __DIR__ . '/screenshots';
            foreach (glob($dir . '/*.png') as $png) {
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
