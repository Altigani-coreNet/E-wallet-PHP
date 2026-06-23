<?php

namespace App\Services;

use App\Models\UsersOtp;
use Illuminate\Support\Facades\Hash;

class PasswordResetLinkService
{
    /**
     * HMAC of the plain token for DB storage (deterministic lookup; avoids bcrypt edge cases).
     */
    public static function hashForStorage(string $plainToken): string
    {
        $plain = self::normalizePlainToken($plainToken);
        if ($plain === '') {
            return '';
        }

        return hash_hmac('sha256', $plain, self::signingKey());
    }

    private static function signingKey(): string
    {
        $key = (string) config('app.key', '');
        if ($key === '') {
            throw new \RuntimeException('APP_KEY is not set; cannot secure password reset tokens.');
        }

        return $key;
    }

    /**
     * Normalize token from URL, query string, Authorization bearer, or JSON (trim + decode).
     */
    public static function normalizePlainToken(?string $token): string
    {
        if ($token === null) {
            return '';
        }

        $token = trim($token);
        if ($token === '') {
            return '';
        }

        $decoded = rawurldecode($token);
        if ($decoded !== $token) {
            return trim($decoded);
        }

        return trim(urldecode($token));
    }

    /**
     * Find a non-expired OTP row for this plain token.
     * Prefers HMAC rows (current). Falls back to bcrypt or legacy plain token.
     */
    public static function findActiveOtpByPlainToken(string $plainToken): ?UsersOtp
    {
        $plain = self::normalizePlainToken($plainToken);
        if ($plain === '') {
            return null;
        }

        $hmac = hash_hmac('sha256', $plain, self::signingKey());

        $byHmac = UsersOtp::query()
            ->where('expires_at', '>', now())
            ->where('token', $hmac)
            ->orderByDesc('created_at')
            ->first();

        if ($byHmac) {
            return $byHmac;
        }

        $candidates = UsersOtp::query()
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->limit(300)
            ->get();

        foreach ($candidates as $otp) {
            $stored = (string) $otp->token;
            if ($stored === '') {
                continue;
            }

            if (str_starts_with($stored, '$2y$')
                || str_starts_with($stored, '$2a$')
                || str_starts_with($stored, '$2b$')) {
                if (Hash::check($plain, $stored)) {
                    return $otp;
                }
            } elseif (hash_equals($stored, $plain)) {
                return $otp;
            }
        }

        return null;
    }
}
