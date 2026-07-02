<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerRejection;
use App\Models\Log;
use App\Modules\CustomerAuth\Resources\CustomerActivityLogResource;
use Illuminate\Support\Facades\Hash;
use Tests\CustomerAuthTestCase;
use Tests\Support\CustomerAuthTestHelper;

class CustomerActivityLogTest extends CustomerAuthTestCase
{
    use CustomerAuthTestHelper;

    private const VALID_PASSWORD = 'Password1!';

    private const MOCK_OTP = 111111;

    private Country $country;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureCustomerAuthTesting();
        [$this->country, $this->city] = $this->seedCountryAndCity();
    }

    public function test_empty_activity_for_new_customer(): void
    {
        $customer = Customer::factory()->active()->create([
            'phone' => '+24991'.random_int(1000000, 9999999),
            'password' => Hash::make(self::VALID_PASSWORD),
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);

        $response = $this->getJson(
            '/api/v1/customer/activity',
            $this->customerAuthHeaders($customer),
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data', [])
            ->assertJsonPath('data.total', 0);
    }

    public function test_lists_password_changed_after_confirm(): void
    {
        $phone = '+24992'.random_int(1000000, 9999999);

        Customer::factory()->active()->create([
            'phone' => $phone,
            'password' => Hash::make(self::VALID_PASSWORD),
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);

        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => $phone,
            'password' => self::VALID_PASSWORD,
        ]);

        $authToken = $loginResponse->json('data.token');
        $newPassword = 'NewSecure1!';

        ['otp_token' => $otpToken] = $this->requestCustomerPasswordChange(
            $authToken,
            self::VALID_PASSWORD,
            $newPassword,
        );

        $this->confirmCustomerPasswordChange(
            $authToken,
            $otpToken,
            self::MOCK_OTP,
            self::VALID_PASSWORD,
            $newPassword,
        )->assertOk();

        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => $phone,
            'password' => $newPassword,
        ]);

        $response = $this->getJson(
            '/api/v1/customer/activity',
            [
                'Authorization' => 'Bearer '.$loginResponse->json('data.token'),
                'Accept' => 'application/json',
            ],
        );

        $response->assertOk()
            ->assertJsonPath('data.data.0.action', 'password_changed')
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        ['id', 'action', 'message', 'label', 'created_at', 'time'],
                    ],
                ],
            ]);

        $firstItem = $response->json('data.data.0');
        $this->assertStringContainsString('changed account password', $firstItem['message']);
        $this->assertArrayNotHasKey('old_values', $firstItem);
        $this->assertArrayNotHasKey('new_values', $firstItem);
        $this->assertArrayNotHasKey('metadata', $firstItem);
    }

    public function test_forgot_password_logs_reset_requested_and_reset(): void
    {
        $phone = '+24993'.random_int(1000000, 9999999);

        $customer = Customer::factory()->active()->create([
            'phone' => $phone,
            'password' => Hash::make('OldPass1!'),
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);

        $this->postJson('/api/v1/customer/password/forgot', [
            'phone' => $phone,
        ])->assertCreated();

        $this->assertDatabaseHas('logs', [
            'loggable_id' => $customer->id,
            'loggable_type' => Customer::class,
            'action' => 'password_reset_requested',
        ]);

        $otpToken = $this->verifiedSmsOtpToken($phone);

        $this->postJson('/api/v1/customer/password/reset', [
            'phone' => $phone,
            'password' => self::VALID_PASSWORD,
            'password_confirmation' => self::VALID_PASSWORD,
            'otp_token' => $otpToken,
        ])->assertOk();

        $this->assertDatabaseHas('logs', [
            'loggable_id' => $customer->id,
            'action' => 'password_reset',
        ]);

        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => $phone,
            'password' => self::VALID_PASSWORD,
        ]);

        $response = $this->getJson(
            '/api/v1/customer/activity',
            [
                'Authorization' => 'Bearer '.$loginResponse->json('data.token'),
                'Accept' => 'application/json',
            ],
        );

        $actions = collect($response->json('data.data'))->pluck('action')->all();

        $this->assertContains('password_reset', $actions);
        $this->assertContains('password_reset_requested', $actions);

        $resetAt = collect($response->json('data.data'))
            ->firstWhere('action', 'password_reset')['created_at'] ?? null;
        $requestedAt = collect($response->json('data.data'))
            ->firstWhere('action', 'password_reset_requested')['created_at'] ?? null;

        $this->assertNotNull($resetAt);
        $this->assertNotNull($requestedAt);
        $this->assertGreaterThanOrEqual($requestedAt, $resetAt);
    }

    public function test_profile_update_active_customer_appears_in_activity(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'active-'.uniqid().'@example.com',
            'phone' => '+24994'.random_int(1000000, 9999999),
            'status' => Customer::STATUS_ACTIVE,
            'profile_completed' => true,
            'password' => Hash::make(self::VALID_PASSWORD),
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);

        $this->postJson(
            '/api/v1/customer/profile/update',
            [
                'firstName' => 'Updated Name',
                'birthDate' => '1990-01-01',
                'gender' => 'male',
                'cityId' => $this->city->id,
                'country_code' => '249',
            ],
            $this->customerAuthHeaders($customer),
        )->assertOk();

        $response = $this->getJson(
            '/api/v1/customer/activity',
            $this->customerAuthHeaders($customer),
        );

        $response->assertOk()
            ->assertJsonPath('data.data.0.action', 'change_request_submitted');

        $this->assertNotEmpty($response->json('data.data.0.message'));
    }

    public function test_profile_resubmit_appears_in_activity(): void
    {
        $customer = $this->createRejectedCustomer(['national_id']);

        $this->postJson(
            '/api/v1/customer/profile/update-rejected-fields',
            ['national_id' => 'NEW-NID-'.uniqid()],
            $this->customerAuthHeaders($customer),
        )->assertOk();

        $response = $this->getJson(
            '/api/v1/customer/activity',
            $this->customerAuthHeaders($customer),
        );

        $response->assertOk()
            ->assertJsonPath('data.data.0.action', 'profile_resubmitted');
    }

    public function test_admin_events_are_excluded_from_activity(): void
    {
        $customer = Customer::factory()->active()->create([
            'phone' => '+24995'.random_int(1000000, 9999999),
            'password' => Hash::make(self::VALID_PASSWORD),
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);

        $admin = Admin::factory()->active()->create();

        $customer->logs()->create([
            'action' => 'approved',
            'metadata' => ['message' => 'Admin approved KYC'],
            'user_id' => $admin->id,
            'user_type' => Admin::class,
        ]);

        $customer->logs()->create([
            'action' => 'registered',
            'metadata' => ['message' => 'Registered account'],
        ]);

        $customer->logs()->create([
            'action' => 'password_changed',
            'metadata' => ['message' => 'Customer changed account password.'],
        ]);

        $response = $this->getJson(
            '/api/v1/customer/activity',
            $this->customerAuthHeaders($customer),
        );

        $response->assertOk();

        $actions = collect($response->json('data.data'))->pluck('action')->all();

        $this->assertSame(['password_changed'], $actions);
    }

    public function test_can_filter_by_action(): void
    {
        $customer = Customer::factory()->active()->create([
            'phone' => '+24996'.random_int(1000000, 9999999),
            'password' => Hash::make(self::VALID_PASSWORD),
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);

        $customer->logs()->create([
            'action' => 'password_changed',
            'metadata' => ['message' => 'Password changed'],
        ]);

        $customer->logs()->create([
            'action' => 'change_request_submitted',
            'metadata' => ['message' => 'Profile update requested'],
        ]);

        $response = $this->getJson(
            '/api/v1/customer/activity?action=password_changed',
            $this->customerAuthHeaders($customer),
        );

        $response->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.action', 'password_changed');
    }

    public function test_invalid_action_filter_returns_422(): void
    {
        $customer = Customer::factory()->active()->create([
            'phone' => '+24997'.random_int(1000000, 9999999),
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $response = $this->getJson(
            '/api/v1/customer/activity?action=approved',
            $this->customerAuthHeaders($customer),
        );

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid action filter. Allowed values: password_changed, password_reset, password_reset_requested, change_request_submitted, profile_resubmitted');
    }

    public function test_pagination_metadata(): void
    {
        $customer = Customer::factory()->active()->create([
            'phone' => '+24998'.random_int(1000000, 9999999),
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        for ($i = 0; $i < 3; $i++) {
            $customer->logs()->create([
                'action' => 'password_changed',
                'metadata' => ['message' => "Event {$i}"],
            ]);
        }

        $response = $this->getJson(
            '/api/v1/customer/activity?per_page=2',
            $this->customerAuthHeaders($customer),
        );

        $response->assertOk()
            ->assertJsonPath('data.per_page', 2)
            ->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.last_page', 2)
            ->assertJsonCount(2, 'data.data');
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/v1/customer/activity')
            ->assertStatus(401);
    }

    public function test_customer_only_sees_own_activity(): void
    {
        $customerA = Customer::factory()->active()->create([
            'phone' => '+24999'.random_int(100000, 999999),
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $customerB = Customer::factory()->active()->create([
            'phone' => '+24999'.random_int(100000, 999999),
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $customerA->logs()->create([
            'action' => 'password_changed',
            'metadata' => ['message' => 'A changed password'],
        ]);

        $customerB->logs()->create([
            'action' => 'password_changed',
            'metadata' => ['message' => 'B changed password'],
        ]);

        $response = $this->getJson(
            '/api/v1/customer/activity',
            $this->customerAuthHeaders($customerA),
        );

        $response->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.message', 'A changed password');
    }

    public function test_repeated_reads_are_idempotent(): void
    {
        $customer = Customer::factory()->active()->create([
            'phone' => '+24990'.random_int(1000000, 9999999),
            'password' => Hash::make(self::VALID_PASSWORD),
        ]);

        $customer->logs()->create([
            'action' => 'password_changed',
            'metadata' => ['message' => 'Changed'],
        ]);

        $headers = $this->customerAuthHeaders($customer);

        $first = $this->getJson('/api/v1/customer/activity', $headers);
        $second = $this->getJson('/api/v1/customer/activity', $headers);

        $first->assertOk();
        $second->assertOk();

        $this->assertSame($first->json('data'), $second->json('data'));
        $this->assertSame(1, Log::query()->where('loggable_id', $customer->id)->count());
    }

    public function test_activity_log_resource_excludes_pii_fields(): void
    {
        $customer = Customer::factory()->active()->create();

        $log = $customer->logs()->create([
            'action' => 'change_request_submitted',
            'old_values' => ['national_id' => 'SECRET-NID'],
            'new_values' => ['national_id' => 'NEW-NID'],
            'metadata' => ['message' => 'Profile update requested'],
        ]);

        $payload = (new CustomerActivityLogResource($log))->resolve();

        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('action', $payload);
        $this->assertArrayHasKey('message', $payload);
        $this->assertArrayNotHasKey('old_values', $payload);
        $this->assertArrayNotHasKey('new_values', $payload);
        $this->assertArrayNotHasKey('metadata', $payload);
        $this->assertArrayNotHasKey('user_id', $payload);
    }

    private function verifiedSmsOtpToken(string $phone): string
    {
        $smsResponse = $this->postJson('/api/v1/customer/otp/sms', [
            'phone' => $phone,
        ]);

        $smsResponse->assertCreated();

        $verifyResponse = $this->postJson('/api/v1/customer/otp/verify', [
            'token' => $smsResponse->json('data.token'),
            'code' => 111111,
        ]);

        $verifyResponse->assertCreated();

        return $verifyResponse->json('data.otp_token');
    }

    /**
     * @param  list<string>  $invalidFields
     */
    private function createRejectedCustomer(array $invalidFields): Customer
    {
        $customer = Customer::factory()->create([
            'email' => 'rejected-'.uniqid().'@example.com',
            'phone' => '+24994'.random_int(1000000, 9999999),
            'status' => Customer::STATUS_REJECTED,
            'profile_completed' => true,
            'password' => Hash::make(self::VALID_PASSWORD),
            'national_id' => 'OLD-NID',
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);

        CustomerRejection::create([
            'customer_id' => $customer->id,
            'rejection_reason' => 'National ID is invalid.',
            'invalid_fields' => $invalidFields,
            'missing_attachments' => null,
            'rejected_by' => Admin::factory()->active()->create()->id,
        ]);

        return $customer;
    }
}
