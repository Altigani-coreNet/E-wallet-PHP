<?php

namespace Tests\Feature;

use App\Mail\CustomerEmailVerificationMail;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\CustomerOtp;
use Illuminate\Support\Facades\Mail;
use Laravel\Passport\Passport;
use Tests\CustomerAuthTestCase;
use Tests\Support\CustomerAuthTestHelper;

class AdminCustomerVerificationEmailTest extends CustomerAuthTestCase
{
    use CustomerAuthTestHelper;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureCustomerAuthTesting();
        $this->admin = Admin::factory()->active()->create();
    }

    public function test_admin_can_send_verification_email_to_unverified_customer(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create([
            'email' => 'admin-verify@example.com',
            'phone' => '+24998'.random_int(1000000, 9999999),
            'phone_verified_at' => now(),
            'email_verified_at' => null,
        ]);

        $response = $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/customers/{$customer->id}/send-verification-email"
        );

        $response->assertOk()
            ->assertJsonPath('success', true);

        Mail::assertSent(CustomerEmailVerificationMail::class, function (CustomerEmailVerificationMail $mail) use ($customer) {
            return $mail->hasTo($customer->email);
        });

        $this->assertDatabaseHas('customers_otp', [
            'identifier' => $customer->email,
            'channel' => 'email',
        ]);
    }

    public function test_admin_send_verification_email_fails_when_already_verified(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create([
            'email' => 'already-verified@example.com',
            'phone' => '+24999'.random_int(1000000, 9999999),
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/customers/{$customer->id}/send-verification-email"
        );

        $response->assertStatus(422);

        Mail::assertNothingSent();
    }

    public function test_customer_can_verify_email_via_link_token(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create([
            'email' => 'link-verify@example.com',
            'phone' => '+24997'.random_int(1000000, 9999999),
            'email_verified_at' => null,
        ]);

        $sendResponse = $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/customers/{$customer->id}/send-verification-email"
        );

        $sendResponse->assertOk();

        $otp = CustomerOtp::query()
            ->where('identifier', $customer->email)
            ->where('channel', 'email')
            ->latest('id')
            ->first();

        $this->assertNotNull($otp);

        $response = $this->postJson('/api/v1/customer/verification/email/verify-link', [
            'token' => $otp->token,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.email_verified', true);

        $customer->refresh();
        $this->assertNotNull($customer->email_verified_at);
    }

    private function actingAsAdminApi(): self
    {
        Passport::actingAs($this->admin, [], 'admin-api');

        return $this;
    }
}
