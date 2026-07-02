<?php

namespace App\Modules\CustomerAuth\Support;

class BiometricSignatureVerifier
{
    public static function verify(
        string $nonce,
        string $signatureBase64Url,
        string $publicKeyPem,
        string $algorithm = 'ES256',
    ): bool {
        if (! in_array($algorithm, ['ES256', 'RS256'], true)) {
            return false;
        }

        $signature = self::base64UrlDecode($signatureBase64Url);
        if ($signature === false || $signature === '') {
            return false;
        }

        $publicKey = openssl_pkey_get_public(self::normalizePublicKey($publicKeyPem));
        if ($publicKey === false) {
            return false;
        }

        $result = openssl_verify($nonce, $signature, $publicKey, OPENSSL_ALGO_SHA256);

        return $result === 1;
    }

    public static function normalizePublicKey(string $publicKey): string
    {
        $trimmed = trim($publicKey);

        if (str_starts_with($trimmed, '-----BEGIN')) {
            return $trimmed;
        }

        $der = base64_decode($trimmed, true);
        if ($der === false) {
            return $trimmed;
        }

        return "-----BEGIN PUBLIC KEY-----\n"
            .chunk_split(base64_encode($der), 64, "\n")
            ."-----END PUBLIC KEY-----\n";
    }

    public static function isValidPublicKey(string $publicKey): bool
    {
        $resource = openssl_pkey_get_public(self::normalizePublicKey($publicKey));

        return $resource !== false;
    }

    public static function detectAlgorithm(string $publicKey): ?string
    {
        $resource = openssl_pkey_get_public(self::normalizePublicKey($publicKey));
        if ($resource === false) {
            return null;
        }

        $details = openssl_pkey_get_details($resource);
        if ($details === false) {
            return null;
        }

        return match ($details['type'] ?? null) {
            OPENSSL_KEYTYPE_EC => 'ES256',
            OPENSSL_KEYTYPE_RSA => 'RS256',
            default => null,
        };
    }

    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data): string|false
    {
        $padding = (4 - strlen($data) % 4) % 4;

        return base64_decode(strtr($data, '-_', '+/').str_repeat('=', $padding), true);
    }
}
