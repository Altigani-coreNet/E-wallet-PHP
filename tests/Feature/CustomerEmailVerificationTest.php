<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Support\Facades\Mail;
use Tests\CustomerAuthTestCase;
use Tests\Support\CustomerAuthTestHelper;

class CustomerEmailVerificationTest extends CustomerAuthTestCase
{
    use CustomerAuthTestHelper;

    private const TEST_EMAIL = 'verify@example.com';

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureCustomerAuthTesting();
    }

    public function test_register_sets_phone_verified_at(): void
    {
        $phone = '+24991'.random_int(1000000, 9999999);
        $result = $this->registerCustomer($phone);

        $result['response']->assertCreated()
            ->assertJsonPath('data.customer.phoneVerified', true);

        $customer = $result['customer'];
        $this->assertNotNull($customer->phone_verified_at);
        $this->assertNull($customer->email_verified_at);
    }

    public function test_email_verification_send_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/customer/verification/email/send');

        $response->assertUnauthorized();
    }

    public function test_email_verification_send_requires_customer_email(): void
    {
        $customer = Customer::factory()->create([
            'email' => '',
            'phone' => '+24992'.random_int(1000000, 9999999),
        ]);

        $response = $this->withHeaders($this->customerAuthHeaders($customer))
            ->postJson('/api/v1/customer/verification/email/send');

        $response->assertStatus(422);
    }

    public function test_email_verification_send_returns_token(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create([
            'email' => self::TEST_EMAIL,
            'phone' => '+24993'.random_int(1000000, 9999999),
        ]);

        $response = $this->withHeaders($this->customerAuthHeaders($customer))
            ->postJson('/api/v1/customer/verification/email/send');

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertDatabaseHas('customers_otp', [
            'identifier' => self::TEST_EMAIL,
            'channel' => 'email',
        ]);
    }

    public function test_email_verification_confirm_sets_email_verified_at(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create([
            'email' => self::TEST_EMAIL,
            'phone' => '+24994'.random_int(1000000, 9999999),
            'phone_verified_at' => now(),
        ]);

        $sendResponse = $this->withHeaders($this->customerAuthHeaders($customer))
            ->postJson('/api/v1/customer/verification/email/send');

        $rawToken = $sendResponse->json('data.token');

        $response = $this->withHeaders($this->customerAuthHeaders($customer))
            ->postJson('/api/v1/customer/verification/email/confirm', [
                'token' => $rawToken,
                'code' => 111111,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email_verified', true)
            ->assertJsonPath('data.customer.emailVerified', true);

        $customer->refresh();
        $this->assertNotNull($customer->email_verified_at);
    }

    public function test_email_verification_confirm_fails_with_invalid_code(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create([
            'email' => self::TEST_EMAIL,
            'phone' => '+24995'.random_int(1000000, 9999999),
        ]);

        $sendResponse = $this->withHeaders($this->customerAuthHeaders($customer))
            ->postJson('/api/v1/customer/verification/email/send');

        $response = $this->withHeaders($this->customerAuthHeaders($customer))
            ->postJson('/api/v1/customer/verification/email/confirm', [
                'token' => $sendResponse->json('data.token'),
                'code' => 999999,
            ]);

        $response->assertStatus(400);

        $customer->refresh();
        $this->assertNull($customer->email_verified_at);
    }

    public function test_email_verification_confirm_rejects_mismatched_identifier(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create([
            'email' => self::TEST_EMAIL,
            'phone' => '+24996'.random_int(1000000, 9999999),
        ]);

        $otherEmailResponse = $this->postJson('/api/v1/customer/otp/email', [
            'email' => 'other@example.com',
        ]);

        $verifyResponse = $this->postJson('/api/v1/customer/otp/verify', [
            'token' => $otherEmailResponse->json('data.token'),
            'code' => 111111,
        ]);

        $verifyResponse->assertCreated();

        $response = $this->withHeaders($this->customerAuthHeaders($customer))
            ->postJson('/api/v1/customer/verification/email/confirm', [
                'token' => $otherEmailResponse->json('data.token'),
                'code' => 111111,
            ]);

        $response->assertStatus(400);

        $customer->refresh();
        $this->assertNull($customer->email_verified_at);
    }

    public function test_email_verification_confirm_is_idempotent_when_already_verified(): void
    {
        $verifiedAt = now()->subDay();

        $customer = Customer::factory()->create([
            'email' => self::TEST_EMAIL,
            'phone' => '+24997'.random_int(1000000, 9999999),
            'email_verified_at' => $verifiedAt,
        ]);

        $response = $this->withHeaders($this->customerAuthHeaders($customer))
            ->postJson('/api/v1/customer/verification/email/confirm', [
                'token' => 'unused-token',
                'code' => 111111,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.email_verified', true);

        $customer->refresh();
        $this->assertNotNull($customer->email_verified_at);
        $this->assertEquals($verifiedAt->toDateTimeString(), $customer->email_verified_at->toDateTimeString());
    }
}
