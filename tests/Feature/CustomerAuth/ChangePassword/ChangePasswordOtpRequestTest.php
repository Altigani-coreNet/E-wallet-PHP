<?php

namespace Tests\Feature\CustomerAuth\ChangePassword;

use App\Models\Customer;
use App\Models\CustomerActionOtp;
use Illuminate\Support\Facades\Hash;
use Tests\CustomerAuthTestCase;
use Tests\Support\CustomerAuthTestHelper;

class ChangePasswordOtpRequestTest extends CustomerAuthTestCase
{
    use CustomerAuthTestHelper;

    private const VALID_PASSWORD = 'Password1!';

    private const TEST_PHONE = '+249912345678';

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureCustomerAuthTesting();
    }

    public function test_request_returns_otp_token_without_changing_password(): void
    {
        $newPassword = 'NewSecure1!';

        Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ]);

        $authToken = $loginResponse->json('data.token');
        $customer = Customer::query()->where('phone', self::TEST_PHONE)->firstOrFail();

        ['response' => $requestResponse] = $this->requestCustomerPasswordChange(
            $authToken,
            self::VALID_PASSWORD,
            $newPassword,
        );

        $requestResponse->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Password change OTP sent successfully',
            ])
            ->assertJsonStructure([
                'data' => ['otp_token', 'expires_at'],
            ]);

        $this->assertTrue(Hash::check(self::VALID_PASSWORD, $customer->fresh()->password));
        $this->assertDatabaseHas('customer_action_otps', [
            'customer_id' => $customer->id,
            'purpose' => CustomerActionOtp::PURPOSE_PASSWORD_CHANGE,
            'consumed_at' => null,
        ]);
    }

    public function test_request_fails_with_wrong_current_password(): void
    {
        Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ]);

        ['response' => $response] = $this->requestCustomerPasswordChange(
            $loginResponse->json('data.token'),
            'WrongPass1!',
            'NewSecure1!',
        );

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Current password is incorrect',
            ]);

        $this->assertDatabaseCount('customer_action_otps', 0);
    }

    public function test_request_fails_when_new_equals_current(): void
    {
        Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ]);

        ['response' => $response] = $this->requestCustomerPasswordChange(
            $loginResponse->json('data.token'),
            self::VALID_PASSWORD,
            self::VALID_PASSWORD,
        );

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'New password must be different from the current password',
            ]);
    }

    public function test_request_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/customer/password/change/request', [
            'current_password' => self::VALID_PASSWORD,
            'password' => 'NewSecure1!',
            'password_confirmation' => 'NewSecure1!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized',
            ]);
    }

    public function test_request_validation_requires_strong_password(): void
    {
        Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$loginResponse->json('data.token'),
        ])->postJson('/api/v1/customer/password/change/request', [
            'current_password' => self::VALID_PASSWORD,
            'password' => 'weakpass',
            'password_confirmation' => 'weakpass',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }
}
