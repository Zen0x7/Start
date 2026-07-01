<?php

namespace App\Services;

use App\Models\TotpDevice;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use OTPHP\TOTP;

class TotpService
{
    public function generateSecret(): string
    {
        return TOTP::generate()->getSecret();
    }

    public function verify(string $secret, string $code): bool
    {
        $totp = TOTP::createFromSecret($secret);

        return $totp->verify($code, leeway: 1);
    }

    public function createDevice(User $user, string $plainSecret, ?string $label = null): TotpDevice
    {
        return TotpDevice::create([
            'user_id' => $user->id,
            'secret' => Crypt::encryptString($plainSecret),
            'label' => $label ?? 'Default',
        ]);
    }

    public function getSecret(TotpDevice $device): string
    {
        return Crypt::decryptString($device->secret);
    }

    public function verifyDevice(TotpDevice $device, string $code): bool
    {
        $secret = $this->getSecret($device);

        return $this->verify($secret, $code);
    }

    public function verifyAny(User $user, string $code): ?TotpDevice
    {
        foreach ($user->totpDevices as $device) {
            if ($this->verifyDevice($device, $code)) {
                $device->update(['last_used_at' => now()]);

                return $device;
            }
        }

        return null;
    }

    public function hasDevices(User $user): bool
    {
        return $user->totpDevices()->exists();
    }

    public function provisioningUri(string $secret, string $email, string $issuer = 'Laravel'): string
    {
        $totp = TOTP::createFromSecret($secret);
        $totp->setIssuer($issuer);

        return $totp->getProvisioningUri($email, $issuer);
    }
}
