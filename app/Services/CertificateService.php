<?php

namespace App\Services;

use App\Models\TotpCertificate;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Crypt;
use phpseclib3\Crypt\PublicKeyLoader;

class CertificateService
{
    private string $signingKey;

    public function __construct(private Repository $config)
    {
        $this->signingKey = base64_decode((string) $config->get('jwt.signing_key'));
    }

    public function issue(int $userId): TotpCertificate
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $privateKey = '';
        openssl_pkey_export($key, $privateKey);

        $details = openssl_pkey_get_details($key);
        $publicKey = $details['key'];

        openssl_pkey_free($key);

        $expiresAt = now()->addMinutes(5);

        $signature = $this->sign($publicKey, $expiresAt->timestamp);

        return TotpCertificate::create([
            'user_id' => $userId,
            'public_key' => $publicKey,
            'private_key' => Crypt::encryptString($privateKey),
            'signature' => $signature,
            'expires_at' => $expiresAt,
        ]);
    }

    public function publicKeyJwk(TotpCertificate $cert): array
    {
        $key = openssl_pkey_get_public($cert->public_key);

        if ($key === false) {
            throw new \RuntimeException('Invalid public key in certificate');
        }

        $details = openssl_pkey_get_details($key);
        openssl_pkey_free($key);

        $rsa = $details['rsa'];

        return [
            'kty' => 'RSA',
            'n' => rtrim(strtr(base64_encode($rsa['n']), '+/', '-_'), '='),
            'e' => rtrim(strtr(base64_encode($rsa['e']), '+/', '-_'), '='),
        ];
    }

    public function verify(TotpCertificate $cert): bool
    {
        $expected = $this->sign($cert->public_key, $cert->expires_at->timestamp);

        return hash_equals($expected, $cert->signature) && $cert->expires_at->isFuture();
    }

    public function decryptSecret(TotpCertificate $cert, string $encryptedSecret): string
    {
        $privateKeyPem = Crypt::decryptString($cert->private_key);

        $key = PublicKeyLoader::loadPrivateKey($privateKeyPem)
            ->withHash('sha256')
            ->withMGFHash('sha256');

        return $key->decrypt(base64_decode($encryptedSecret));
    }

    private function sign(string $publicKey, int $expiresAt): string
    {
        return hash_hmac('sha256', $publicKey.'|'.$expiresAt, $this->signingKey);
    }
}
