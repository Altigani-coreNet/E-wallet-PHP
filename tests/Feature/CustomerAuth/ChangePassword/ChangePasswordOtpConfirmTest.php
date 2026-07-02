<?php

namespace Tests\Feature\CustomerAuth\ChangePassword;

use App\Models\Customer;
use App\Models\CustomerActionOtp;
use Illuminate\Support\Facades\Hash;
use Tests\CustomerAuthTestCase;
use Tests\Support\CustomerAuthTestHelper;

class ChangePasswordOtpConfirmTest extends CustomerAuthTestCase
{
    use CustomerAuthTestHelper;

    private const VALID_PASSWORD = 'Password1!';

    private const TEST_PHONE = '+249912345678';

    private const MOCK_OTP = 111111;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureCustomerAuthTesting();
    }

    public function test_can_change_password_with_otp(): void
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

        ['otp_token' => $otpToken] = $this->requestCustomerPasswordChange(
            $authToken,
            self::VALID_PASSWORD,
            $newPassword,
        );

        $confirmResponse = $this->confirmCustomerPasswordChange(
            $authToken,
            $otpToken,
            self::MOCK_OTP,
            self::VALID_PASSWORD,
            $newPassword,
        );

        $confirmResponse->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Password changed successfully',
            ])
            ->assertJsonStructure([
                'data' => ['token', 'refresh_token'],
            ]);

        $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ])->assertStatus(401);

        $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => $newPassword,
        ])->assertOk();
    }

    public function test_confirm_fails_with_wrong_otp(): void
    {
        $newPassword = 'NewSecure1!';

        $customer = Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ]);

        $authToken = $loginResponse->json('data.token');

        ['otp_token' => $otpToken] = $this->requestCustomerPasswordChange(
            $authToken,
            self::VALID_PASSWORD,
            $newPassword,
        );

        $response = $this->confirmCustomerPasswordChange(
            $authToken,
            $otpToken,
            999999,
            self::VALID_PASSWORD,
            $newPassword,
        );

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertTrue(Hash::check(self::VALID_PASSWORD, $customer->fresh()->password));
        $this->assertDatabaseHas('customer_action_otps', [
            'customer_id' => $customer->id,
            'token' => $otpToken,
            'consumed_at' => null,
        ]);
    }

    public function test_confirm_fails_with_expired_token(): void
    {
        $newPassword = 'NewSecure1!';

        $customer = Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ]);

        $authToken = $loginResponse->json('data.token');

        ['otp_token' => $otpToken] = $this->requestCustomerPasswordChange(
            $authToken,
            self::VALID_PASSWORD,
            $newPassword,
        );

        CustomerActionOtp::query()
            ->where('token', $otpToken)
            ->update(['expires_at' => now()->subMinute()]);

        $response = $this->confirmCustomerPasswordChange(
            $authToken,
            $otpToken,
            self::MOCK_OTP,
            self::VALID_PASSWORD,
            $newPassword,
        );

        $response->assertStatus(422);
        $this->assertTrue(Hash::check(self::VALID_PASSWORD, $customer->fresh()->password));
    }

    public function test_confirm_fails_when_password_differs(): void
    {
        $newPassword = 'NewSecure1!';

        $customer = Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ]);

        $authToken = $loginResponse->json('data.token');

        ['otp_token' => $otpToken] = $this->requestCustomerPasswordChange(
            $authToken,
            self::VALID_PASSWORD,
            $newPassword,
        );

        $response = $this->confirmCustomerPasswordChange(
            $authToken,
            $otpToken,
            self::MOCK_OTP,
            self::VALID_PASSWORD,
            'OtherPass1!',
        );

        $response->assertStatus(422)
            ->assertJsonPath('message', 'OTP does not match this password change request.');

        $this->assertTrue(Hash::check(self::VALID_PASSWORD, $customer->fresh()->password));
    }

    public function test_confirm_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/customer/password/change/confirm', [
            'otp_token' => 'token',
            'otp' => self::MOCK_OTP,
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

    public function test_new_request_invalidates_previous_otp(): void
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

        ['otp_token' => $firstToken] = $this->requestCustomerPasswordChange(
            $authToken,
            self::VALID_PASSWORD,
            $newPassword,
        );

        $this->requestCustomerPasswordChange(
            $authToken,
            self::VALID_PASSWORD,
            $newPassword,
        );

        $response = $this->confirmCustomerPasswordChange(
            $authToken,
            $firstToken,
            self::MOCK_OTP,
            self::VALID_PASSWORD,
            $newPassword,
        );

        $response->assertStatus(422);
    }

    public function test_sends_notification_on_success(): void
    {
        $newPassword = 'NewSecure1!';

        $customer = Customer::factory()->active()->create([
            'phone' => self::TEST_PHONE,
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ]);

        $authToken = $loginResponse->json('data.token');

        ['otp_token' => $otpToken] = $this->requestCustomerPasswordChange(
            $authToken,
            self::VALID_PASSWORD,
            $newPassword,
        );

        $this->assertDatabaseCount('customer_action_otps', 1);

        $this->confirmCustomerPasswordChange(
            $authToken,
            $otpToken,
            self::MOCK_OTP,
            self::VALID_PASSWORD,
            $newPassword,
        )->assertOk();

        $this->assertTrue(Hash::check($newPassword, $customer->fresh()->password));
        $this->assertDatabaseHas('customer_action_otps', [
            'customer_id' => $customer->id,
            'token' => $otpToken,
        ]);

        $this->assertNotNull(
            CustomerActionOtp::query()->where('token', $otpToken)->value('consumed_at')
        );
    }
}
