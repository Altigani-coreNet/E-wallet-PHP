<?php

namespace App\Modules\CustomerAuth\Support;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class CustomerJwtService
{
    public function createToken(int|string $customerId, string $emailOrPhone): array
    {
        $now = time();
        $expiresIn = $this->parseExpiresIn(config('customer_auth.jwt_expires_in'));

        $payload = [
            'sub' => (string) $customerId,
            'email' => $emailOrPhone,
            'type' => 'customer',
            'iat' => $now,
            'exp' => $now + $expiresIn,
        ];

        $token = JWT::encode($payload, config('customer_auth.jwt_secret'), 'HS256');

        return [
            'token' => $token,
            'tokenType' => 'Bearer',
        ];
    }

    public function decode(string $token): object
    {
        return JWT::decode(
            $token,
            new Key(config('customer_auth.jwt_secret'), 'HS256')
        );
    }

    private function parseExpiresIn(string $expiresIn): int
    {
        if (preg_match('/^(\d+)d$/', $expiresIn, $matches)) {
            return (int) $matches[1] * 86400;
        }

        if (preg_match('/^(\d+)h$/', $expiresIn, $matches)) {
            return (int) $matches[1] * 3600;
        }

        if (is_numeric($expiresIn)) {
            return (int) $expiresIn;
        }

        return 7 * 86400;
    }
}
