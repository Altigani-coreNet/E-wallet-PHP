<?php

namespace Tests\Feature;

use App\Mail\CustomerSetPasswordMail;
use App\Models\Admin;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerPasswordSetupToken;
use App\Modules\CustomerAuth\Services\CustomerPasswordSetupService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Passport\Passport;
use Tests\CustomerAuthTestCase;
use Tests\Support\CustomerAuthTestHelper;

class CustomerPasswordSetupTest extends CustomerAuthTestCase
{
    use CustomerAuthTestHelper;

    private const VALID_PASSWORD = 'Password1!';

    private const NEW_PASSWORD = 'NewPass1!';

    private Admin $admin;

    private Country $country;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureCustomerAuthTesting();
        [$this->country, $this->city] = $this->seedCountryAndCity();
        $this->admin = Admin::factory()->active()->create();
    }

    public function test_admin_create_sends_set_password_invite_and_creates_token(): void
    {
        Mail::fake();

        $response = $this->actingAsAdminApi()->postJson('/api/v2/admin/customers', [
            'name' => 'Invite Customer',
            'email' => 'invite@example.com',
            'phone' => '+249911300001',
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);

        $response->assertCreated();

        $customer = Customer::query()->where('email', 'invite@example.com')->firstOrFail();

        $this->assertDatabaseHas('customer_password_setup_tokens', [
            'customer_id' => $customer->id,
            'used_at' => null,
        ]);

        Mail::assertSent(CustomerSetPasswordMail::class, fn ($mail) => $mail->hasTo('invite@example.com'));
    }

    public function test_validate_endpoint_accepts_active_token(): void
    {
        $customer = $this->createCustomer([
            'email' => 'validate@example.com',
            'phone' => '+249911300002',
        ]);

        $plainToken = app(CustomerPasswordSetupService::class)->issueToken($customer);

        $this->getJson('/api/v1/customer/set-password/validate?token='.urlencode($plainToken))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.valid', true);
    }

    public function test_validate_endpoint_rejects_invalid_token(): void
    {
        $this->getJson('/api/v1/customer/set-password/validate?token=not-a-real-token')
            ->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    public function test_customer_can_set_password_activates_account_and_logs_in(): void
    {
        $customer = $this->createCustomer([
            'email' => 'setpw@example.com',
            'phone' => '+249911300003',
            'status' => Customer::STATUS_PENDING,
            'password' => null,
        ]);

        $plainToken = app(CustomerPasswordSetupService::class)->issueToken($customer);

        $this->postJson('/api/v1/customer/set-password', [
            'token' => $plainToken,
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertOk()
            ->assertJsonPath('success', true);

        $customer->refresh();
        $this->assertSame(Customer::STATUS_ACTIVE, $customer->status);
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $customer->password));

        $this->assertDatabaseMissing('customer_password_setup_tokens', [
            'customer_id' => $customer->id,
            'used_at' => null,
        ]);

        $this->postJson('/api/v1/customer/auth/login', [
            'phone' => '+249911300003',
            'password' => self::NEW_PASSWORD,
        ])->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_token_cannot_be_reused(): void
    {
        $customer = $this->createCustomer([
            'email' => 'reuse@example.com',
            'phone' => '+249911300004',
            'status' => Customer::STATUS_PENDING,
        ]);

        $plainToken = app(CustomerPasswordSetupService::class)->issueToken($customer);

        $this->postJson('/api/v1/customer/set-password', [
            'token' => $plainToken,
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertOk();

        $this->postJson('/api/v1/customer/set-password', [
            'token' => $plainToken,
            'password' => 'AnotherPass1!',
            'password_confirmation' => 'AnotherPass1!',
        ])->assertStatus(400);
    }

    public function test_expired_token_is_rejected(): void
    {
        $customer = $this->createCustomer([
            'email' => 'expired@example.com',
            'phone' => '+249911300005',
        ]);

        $plainToken = app(CustomerPasswordSetupService::class)->issueToken($customer);

        CustomerPasswordSetupToken::query()
            ->where('customer_id', $customer->id)
            ->update(['expires_at' => now()->subHour()]);

        $this->postJson('/api/v1/customer/set-password', [
            'token' => $plainToken,
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertStatus(400);
    }

    public function test_password_confirmation_must_match(): void
    {
        $customer = $this->createCustomer([
            'email' => 'mismatch@example.com',
            'phone' => '+249911300006',
        ]);

        $plainToken = app(CustomerPasswordSetupService::class)->issueToken($customer);

        $this->postJson('/api/v1/customer/set-password', [
            'token' => $plainToken,
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => 'Different1!',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_admin_can_resend_password_invite(): void
    {
        Mail::fake();

        $customer = $this->createCustomer([
            'email' => 'resend@example.com',
            'phone' => '+249911300007',
        ]);

        $this->actingAsAdminApi()
            ->postJson("/api/v2/admin/customers/{$customer->id}/resend-password-invite")
            ->assertOk()
            ->assertJsonPath('success', true);

        Mail::assertSent(CustomerSetPasswordMail::class, fn ($mail) => $mail->hasTo('resend@example.com'));
    }

    private function createCustomer(array $attributes = []): Customer
    {
        return Customer::query()->create(array_merge([
            'name' => 'Test Customer',
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+2499'.fake()->unique()->numerify('#######'),
            'password' => Hash::make(self::VALID_PASSWORD),
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
            'status' => Customer::STATUS_PENDING,
            'profile_completed' => true,
        ], $attributes));
    }

    private function actingAsAdminApi(): self
    {
        Passport::actingAs($this->admin, [], 'admin-api');

        return $this->withHeaders([
            'Accept' => 'application/json',
            'Accept-Language' => 'en',
            'X-App-Locale' => 'en',
        ]);
    }
}
