<?php

namespace App\Modules\CustomerAuth\Support;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class CustomerJwtService
{
    public function createToken(int|string $id, string $email, string $type = 'customer'): array
    {
        $now = time();
        $payload = [
            'sub' => (string) $id,
            'email' => $email,
            'type' => $type,
            'iat' => $now,
            'exp' => $now + $this->expiresInSeconds(),
        ];

        $token = JWT::encode($payload, $this->secret(), 'HS256');

        return [
            'token' => $token,
            'tokenType' => 'Bearer',
        ];
    }

    public function decode(string $token): object
    {
        return JWT::decode($token, new Key($this->secret(), 'HS256'));
    }

    private function secret(): string
    {
        return config('services.jwt.secret', 'change-me-to-a-long-random-secret');
    }

    private function expiresInSeconds(): int
    {
        $expiresIn = config('services.jwt.expires_in', '7d');

        if (is_numeric($expiresIn)) {
            return (int) $expiresIn;
        }

        if (preg_match('/^(\d+)([smhd])$/', (string) $expiresIn, $matches)) {
            return match ($matches[2]) {
                's' => (int) $matches[1],
                'm' => (int) $matches[1] * 60,
                'h' => (int) $matches[1] * 3600,
                'd' => (int) $matches[1] * 86400,
                default => 604800,
            };
        }

        return 604800;
    }
}
