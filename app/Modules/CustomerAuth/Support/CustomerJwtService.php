<?php

namespace App\Modules\CustomerAuth\Support;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;

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

    public function decodeForRefresh(string $token): object
    {
        try {
            return $this->decode($token);
        } catch (ExpiredException) {
            $previousLeeway = JWT::$leeway;
            JWT::$leeway = $this->refreshGraceSeconds();

            try {
                return JWT::decode($token, new Key($this->secret(), 'HS256'));
            } finally {
                JWT::$leeway = $previousLeeway;
            }
        } catch (SignatureInvalidException|\UnexpectedValueException $e) {
            throw $e;
        }
    }

    public function refreshGraceSeconds(): int
    {
        return $this->parseDuration(config('services.jwt.refresh_grace', config('services.jwt.expires_in', '7d')));
    }

    private function secret(): string
    {
        return config('services.jwt.secret', 'change-me-to-a-long-random-secret');
    }

    private function expiresInSeconds(): int
    {
        return $this->parseDuration(config('services.jwt.expires_in', '7d'));
    }

    private function parseDuration(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        if (preg_match('/^(\d+)([smhd])$/', (string) $value, $matches)) {
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
