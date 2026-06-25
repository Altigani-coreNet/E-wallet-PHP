<?php

namespace Tests\Support;

use App\Models\Admin;
use App\Models\Advertisement;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Modules\CustomerAuth\Support\CustomerJwtService;
use App\Notifications\TestUserNotification;
use Firebase\JWT\JWT;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

trait CustomerAuthTestHelper
{
    protected Country $testCountry;

    protected City $testCity;

    protected function configureCustomerAuthTesting(): void
    {
        config([
            'services.otp.mock_code' => 111111,
            'services.jwt.secret' => 'test-secret-for-customer-auth',
            'services.jwt.expires_in' => '7d',
            'services.jwt.refresh_grace' => '7d',
        ]);
    }

    protected function seedCountryAndCity(): array
    {
        $countryId = '00000000-0000-4000-8000-000000000001';
        $cityId = '10000000-0000-4000-8000-000000000001';

        $this->testCountry = Country::query()->create([
            'id' => $countryId,
            'name' => ['en' => 'Sudan', 'ar' => 'Sudan'],
            'short_name' => 'SD',
            'code' => '249',
            'status' => true,
        ]);

        $this->testCity = City::query()->create([
            'id' => $cityId,
            'name' => ['en' => 'Khartoum', 'ar' => 'Khartoum'],
            'country_id' => $countryId,
            'status' => true,
        ]);

        return [$this->testCountry, $this->testCity];
    }

    protected function customerAuthHeaders(Customer $customer): array
    {
        $jwt = app(CustomerJwtService::class)->createToken(
            $customer->id,
            $customer->email ?: $customer->phone,
        );

        return [
            'Authorization' => 'Bearer '.$jwt['token'],
            'Accept' => 'application/json',
        ];
    }

    protected function sendAndVerifySmsOtp(string $phone): string
    {
        $sms = $this->postJson('/api/v1/customer/otp/sms', ['phone' => $phone]);
        $sms->assertCreated();

        $rawToken = $sms->json('data.token');

        $verify = $this->postJson('/api/v1/customer/otp/verify', [
            'token' => $rawToken,
            'code' => 111111,
        ]);
        $verify->assertCreated();

        return $verify->json('data.otp_token');
    }

    protected function registerCustomer(string $phone, string $password = 'SecurePass1!'): array
    {
        $otpToken = $this->sendAndVerifySmsOtp($phone);

        $response = $this->postJson('/api/v1/customer/auth/register', [
            'phone' => $phone,
            'password' => $password,
            'password_confirmation' => $password,
            'otp_token' => $otpToken,
        ]);

        return [
            'response' => $response,
            'token' => $response->json('data.token'),
            'customer' => Customer::query()->where('phone', $phone)->first(),
        ];
    }

    protected function createExpiredCustomerToken(Customer $customer, int $expiredSecondsAgo): string
    {
        $now = time();
        $payload = [
            'sub' => (string) $customer->id,
            'email' => $customer->email ?: $customer->phone,
            'type' => 'customer',
            'iat' => $now - $expiredSecondsAgo - 3600,
            'exp' => $now - $expiredSecondsAgo,
        ];

        return JWT::encode($payload, config('services.jwt.secret'), 'HS256');
    }

    protected function seedActiveBanner(string $countryId): Advertisement
    {
        return Advertisement::query()->create([
            'name' => 'Summer Promo',
            'image' => 'banners/promo.jpg',
            'country_id' => $countryId,
            'status' => 'active',
        ]);
    }

    protected function seedPublicNotification(Admin $admin): DatabaseNotification
    {
        $id = (string) Str::uuid();

        return DatabaseNotification::query()->create([
            'id' => $id,
            'type' => TestUserNotification::class,
            'notifiable_type' => Admin::class,
            'notifiable_id' => $admin->id,
            'target_type' => 'public',
            'source' => 'admin_management',
            'title' => 'Welcome',
            'description' => 'Your account is ready',
            'topic' => 'general',
            'data' => json_encode([
                'title' => 'Welcome',
                'body' => 'Your account is ready',
                'meta' => [
                    'source' => 'admin_management',
                    'topic' => 'general',
                    'target_type' => 'public',
                ],
                'sent_at' => now()->toIso8601String(),
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function assertCustomerSuccessEnvelope(TestResponse $response, int $status = 200): TestResponse
    {
        return $response->assertStatus($status)->assertJsonStructure([
            'success',
            'message',
            'data',
        ])->assertJson(['success' => true]);
    }
}
