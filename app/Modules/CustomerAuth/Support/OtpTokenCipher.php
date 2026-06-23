<?php

namespace App\Modules\CustomerAuth\Support;

class OtpTokenCipher
{
    private const ALGORITHM = 'aes-256-cbc';

    public static function encrypt(string $token): string
    {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt(
            $token,
            self::ALGORITHM,
            self::cipherKey(),
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Failed to encrypt OTP token');
        }

        return bin2hex($iv) . ':' . bin2hex($encrypted);
    }

    public static function decrypt(string $encryptedToken): string
    {
        $parts = explode(':', $encryptedToken, 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new \InvalidArgumentException('Invalid encrypted OTP token format');
        }

        $iv = hex2bin($parts[0]);
        $data = hex2bin($parts[1]);

        if ($iv === false || $data === false) {
            throw new \InvalidArgumentException('Invalid encrypted OTP token format');
        }

        $decrypted = openssl_decrypt(
            $data,
            self::ALGORITHM,
            self::cipherKey(),
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            throw new \InvalidArgumentException('Failed to decrypt OTP token');
        }

        return $decrypted;
    }

    private static function cipherKey(): string
    {
        $secret = config('customer_auth.jwt_secret');

        return hash('sha256', $secret, true);
    }
}
