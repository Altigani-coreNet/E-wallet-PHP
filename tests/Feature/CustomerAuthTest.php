<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    private string $countryId;
    private string $cityId;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'customer_auth.jwt_secret' => 'test-jwt-secret-for-customer-auth',
            'customer_auth.otp_mock_code' => 111111,
        ]);

        $currencyId = (string) Str::uuid();
        DB::table('currencies')->insert([
            'id' => $currencyId,
            'country' => 'Sudan',
            'name' => 'Sudanese Pound',
            'symbol' => json_encode(['en' => 'SDG']),
            'currency_code' => json_encode(['en' => 'SDG']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->countryId = (string) Str::uuid();
        Country::query()->create([
            'id' => $this->countryId,
            'name' => ['en' => 'Sudan'],
            'short_name' => 'SD',
            'code' => '249',
            'currency_id' => $currencyId,
            'status' => true,
        ]);

        $this->cityId = (string) Str::uuid();
        City::query()->create([
            'id' => $this->cityId,
            'name' => ['en' => 'Khartoum'],
            'country_id' => $this->countryId,
            'status' => true,
        ]);
    }

    public function test_full_customer_registration_flow(): void
    {
        $phone = '+249912345678';

        $smsResponse = $this->postJson('/v1/customer/otp/sms', ['phone' => $phone]);
        $smsResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token']]);

        $token = $smsResponse->json('data.token');

        $verifyResponse = $this->postJson('/v1/customer/otp/verify', [
            'token' => $token,
            'code' => 111111,
        ]);
        $verifyResponse->assertOk()
            ->assertJsonPath('data.verified', true)
            ->assertJsonPath('data.has_account', false);

        $otpToken = $verifyResponse->json('data.otp_token');

        $registerResponse = $this->postJson('/v1/customer/auth/register', [
            'phone' => $phone,
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'otp_token' => $otpToken,
        ]);
        $registerResponse->assertOk()
            ->assertJsonPath('data.profile_completed', false)
            ->assertJsonStructure(['data' => ['token', 'customer']]);

        $jwt = $registerResponse->json('data.token');

        $profileResponse = $this->withToken($jwt)->getJson('/v1/customer/profile');
        $profileResponse->assertOk()
            ->assertJsonPath('data.profile_completed', false);

        $completeResponse = $this->withToken($jwt)->patchJson('/v1/customer/profile/complete', [
            'firstName' => 'Ahmed',
            'email' => 'ahmed@example.com',
            'birthDate' => '1990-05-15',
            'gender' => 'male',
            'cityId' => $this->cityId,
            'country_code' => '249',
        ]);
        $completeResponse->assertOk()
            ->assertJsonPath('data.profile_completed', true)
            ->assertJsonPath('data.customer.email', 'ahmed@example.com');

        $this->assertDatabaseHas('customers', [
            'phone' => $phone,
            'email' => 'ahmed@example.com',
            'profile_completed' => true,
        ]);
    }

    public function test_register_rejects_duplicate_phone(): void
    {
        $phone = '+249912345679';

        Customer::query()->create([
            'phone' => $phone,
            'password' => bcrypt('Secret123!'),
            'profile_completed' => false,
        ]);

        $token = $this->postJson('/v1/customer/otp/sms', ['phone' => $phone])
            ->json('data.token');

        $otpToken = $this->postJson('/v1/customer/otp/verify', [
            'token' => $token,
            'code' => 111111,
        ])->json('data.otp_token');

        $response = $this->postJson('/v1/customer/auth/register', [
            'phone' => $phone,
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'otp_token' => $otpToken,
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_register_rejects_invalid_otp_token(): void
    {
        $response = $this->postJson('/v1/customer/auth/register', [
            'phone' => '+249912345680',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'otp_token' => 'not-valid',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }
}
