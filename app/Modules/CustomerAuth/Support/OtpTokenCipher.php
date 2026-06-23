<?php

namespace App\Modules\CustomerAuth\Support;

class OtpTokenCipher
{
    private const ALGORITHM = 'aes-256-cbc';

    public static function encrypt(string $token): string
    {
        $key = self::cipherKey();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($token, self::ALGORITHM, $key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new \RuntimeException('Failed to encrypt OTP token');
        }

        return bin2hex($iv).':'.bin2hex($encrypted);
    }

    public static function decrypt(string $encryptedToken): string
    {
        $parts = explode(':', $encryptedToken, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new \InvalidArgumentException('Invalid encrypted OTP token format');
        }

        [$ivHex, $dataHex] = $parts;
        $iv = hex2bin($ivHex);
        $data = hex2bin($dataHex);

        if ($iv === false || $data === false) {
            throw new \InvalidArgumentException('Invalid encrypted OTP token format');
        }

        $decrypted = openssl_decrypt($data, self::ALGORITHM, self::cipherKey(), OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new \InvalidArgumentException('Invalid encrypted OTP token');
        }

        return $decrypted;
    }

    private static function cipherKey(): string
    {
        $secret = config('services.jwt.secret', 'change-me-to-a-long-random-secret');

        return hash('sha256', $secret, true);
    }
}
