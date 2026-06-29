<?php

namespace Tests\Unit;

use App\Modules\CustomerAuth\Support\CustomerJwtService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Tests\TestCase;

class CustomerJwtServiceTest extends TestCase
{
    private CustomerJwtService $jwtService;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.jwt.secret' => 'test-secret-for-customer-jwt',
            'services.jwt.expires_in' => '1h',
            'services.jwt.refresh_expires_in' => '30d',
        ]);

        $this->jwtService = app(CustomerJwtService::class);
    }

    public function test_create_token_includes_access_token_use_and_expires_in(): void
    {
        $result = $this->jwtService->createToken(42, 'customer@example.com');

        $this->assertArrayHasKey('token', $result);
        $this->assertSame('Bearer', $result['tokenType']);
        $this->assertSame(3600, $result['expiresIn']);

        $payload = JWT::decode($result['token'], new Key(config('services.jwt.secret'), 'HS256'));

        $this->assertSame('42', $payload->sub);
        $this->assertSame('customer@example.com', $payload->email);
        $this->assertSame('customer', $payload->type);
        $this->assertSame('access', $payload->token_use);
    }

    public function test_create_refresh_token_includes_refresh_token_use_and_expires_in(): void
    {
        $result = $this->jwtService->createRefreshToken(42, 'customer@example.com');

        $this->assertArrayHasKey('token', $result);
        $this->assertSame(2592000, $result['expiresIn']);

        $payload = $this->jwtService->decodeRefreshToken($result['token']);

        $this->assertSame('42', $payload->sub);
        $this->assertSame('customer@example.com', $payload->email);
        $this->assertSame('customer', $payload->type);
        $this->assertSame('refresh', $payload->token_use);
    }

    public function test_decode_refresh_token_rejects_access_token(): void
    {
        $access = $this->jwtService->createToken(42, 'customer@example.com');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid refresh token');

        $this->jwtService->decodeRefreshToken($access['token']);
    }

    public function test_decode_refresh_token_rejects_legacy_token_without_token_use(): void
    {
        $now = time();
        $legacyToken = JWT::encode([
            'sub' => '42',
            'email' => 'customer@example.com',
            'type' => 'customer',
            'iat' => $now,
            'exp' => $now + 3600,
        ], config('services.jwt.secret'), 'HS256');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid refresh token');

        $this->jwtService->decodeRefreshToken($legacyToken);
    }
}
