<?php

namespace App\Modules\CustomerAuth\Support;

class BiometricChallengeCipher
{
    private const ALGORITHM = 'aes-256-cbc';

    /**
     * @param  array{nonce: string, device_id: string, jti: string, exp: int}  $payload
     */
    public static function encrypt(array $payload): string
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        $key = self::cipherKey();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($json, self::ALGORITHM, $key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new \RuntimeException('Failed to encrypt biometric challenge token');
        }

        return bin2hex($iv).':'.bin2hex($encrypted);
    }

    /**
     * @return array{nonce: string, device_id: string, jti: string, exp: int}
     */
    public static function decrypt(string $encryptedToken): array
    {
        $parts = explode(':', $encryptedToken, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new \InvalidArgumentException('Invalid biometric challenge token format');
        }

        [$ivHex, $dataHex] = $parts;
        $iv = hex2bin($ivHex);
        $data = hex2bin($dataHex);

        if ($iv === false || $data === false) {
            throw new \InvalidArgumentException('Invalid biometric challenge token format');
        }

        $decrypted = openssl_decrypt($data, self::ALGORITHM, self::cipherKey(), OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new \InvalidArgumentException('Invalid biometric challenge token');
        }

        $payload = json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);

        if (
            ! is_array($payload)
            || ! isset($payload['nonce'], $payload['device_id'], $payload['jti'], $payload['exp'])
            || ! is_string($payload['nonce'])
            || ! is_string($payload['device_id'])
            || ! is_string($payload['jti'])
            || ! is_int($payload['exp'])
        ) {
            throw new \InvalidArgumentException('Invalid biometric challenge payload');
        }

        return $payload;
    }

    private static function cipherKey(): string
    {
        $secret = config('services.jwt.secret', 'change-me-to-a-long-random-secret');

        return hash('sha256', 'biometric-challenge:'.$secret, true);
    }
}
