<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Modules\CustomerAuth\Support\CustomerJwtService;
use App\Services\WalletService;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\CustomerAuthTestCase;
use Tests\Support\AdminWalletTestHelpers;
use Tests\Support\CustomerAuthTestHelper;

class CustomerNotificationWorkflowE2eTest extends CustomerAuthTestCase
{
    use AdminWalletTestHelpers;
    use CustomerAuthTestHelper;

    private const VALID_PASSWORD = 'Password1!';

    private const NEW_PASSWORD = 'Password2!';

    private const TEST_PHONE = '+249912345680';

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->configureCustomerAuthTesting();
        $this->seed(ChartOfAccountSeeder::class);
        $this->createMasterWallet(1_000_000);
        $this->admin = Admin::factory()->active()->create();
    }

    public function test_complete_notification_workflow_from_register_through_transfer(): void
    {
        [, $city] = $this->createCountryAndCity();

        // 1. Register
        $otpToken = $this->verifiedSmsOtpToken(self::TEST_PHONE);

        $registerResponse = $this->postJson('/api/v1/customer/auth/register', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
            'password_confirmation' => self::VALID_PASSWORD,
            'otp_token' => $otpToken,
        ]);

        $registerResponse->assertCreated()
            ->assertJsonPath('data.profile_completed', false);

        $customer = Customer::query()->where('phone', self::TEST_PHONE)->firstOrFail();
        $authToken = $registerResponse->json('data.token');

        $this->assertNotificationCount($customer, 0);

        // 2. Complete profile -> application received notification
        $this->withHeaders(['Authorization' => 'Bearer '.$authToken])
            ->post('/api/v1/customer/profile/complete', [
                'firstName' => 'Workflow User',
                'nationalId' => 'NID-WORKFLOW-001',
                'email' => 'workflow@example.com',
                'birthDate' => '1990-05-15',
                'gender' => 'male',
                'cityId' => $city->id,
                'country_code' => '249',
                'picture' => UploadedFile::fake()->image('avatar.jpg'),
                'passport' => UploadedFile::fake()->create('passport.pdf', 100, 'application/pdf'),
            ], [
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJsonPath('data.profile_completed', true);

        $customer->refresh();
        $this->assertNotificationCount($customer, 1);
        $this->assertLatestNotificationTitle($customer, 'Application received - we\'re on it');

        // 3. Login -> notifications still visible via inbox API
        $loginResponse = $this->postJson('/api/v1/customer/auth/login', [
            'phone' => self::TEST_PHONE,
            'password' => self::VALID_PASSWORD,
        ]);

        $loginResponse->assertOk();
        $authToken = $loginResponse->json('data.token');

        $this->withHeaders(['Authorization' => 'Bearer '.$authToken])
            ->getJson('/api/v1/customer/notifications')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.title', 'Application received - we\'re on it')
            ->assertJsonPath('data.data.0.topic', 'alert');

        $this->withHeaders(['Authorization' => 'Bearer '.$authToken])
            ->getJson('/api/v1/customer/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data', 1);

        // 4. Change password -> security notification
        $changePasswordResponse = $this->withHeaders(['Authorization' => 'Bearer '.$authToken])
            ->postJson('/api/v1/customer/password/change', [
                'current_password' => self::VALID_PASSWORD,
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ]);

        $changePasswordResponse->assertOk()
            ->assertJsonPath('success', true);

        $authToken = $changePasswordResponse->json('data.token');

        $customer->refresh();
        $this->assertNotificationCount($customer, 2);
        $this->assertNotificationsInclude($customer, 'Password updated', 'Application received - we\'re on it');

        $this->withHeaders(['Authorization' => 'Bearer '.$authToken])
            ->getJson('/api/v1/customer/notifications')
            ->assertOk()
            ->assertJsonPath('data.total', 2);

        $this->assertNotificationsInclude($customer, 'Password updated', 'Application received - we\'re on it');

        // Activate sender so wallet money routes are allowed
        $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/customers/{$customer->id}/status",
            ['status' => Customer::STATUS_ACTIVE]
        )->assertOk();

        $customer->refresh();
        $senderWallet = app(WalletService::class)->walletForCustomer($customer);
        $this->assertNotNull($senderWallet);

        // 5. Admin cash-in -> wallet credited notification
        $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/wallets/{$senderWallet->id}/cash-in",
            ['amount' => 500, 'description' => 'Station top-up'],
            ['Idempotency-Key' => 'workflow-cash-in-'.uniqid()]
        )->assertOk()
            ->assertJsonPath('data.amount', 500);

        $customer->refresh();
        $this->assertNotificationCount($customer, 3);
        $this->assertNotificationsInclude(
            $customer,
            'Money added to your wallet',
            'Password updated',
            'Application received - we\'re on it',
        );

        $this->withHeaders(['Authorization' => 'Bearer '.$authToken])
            ->getJson('/api/v1/customer/notifications')
            ->assertOk()
            ->assertJsonFragment(['description' => 'Your wallet was credited with 500.00 SDG.']);

        // 6. Transfer -> sender and recipient each get a notification
        $recipient = Customer::factory()->active()->create([
            'name' => 'Recipient User',
            'phone' => '+249912345681',
        ]);
        $recipientWallet = app(WalletService::class)->createForCustomer($recipient);
        $transferIdempotencyKey = 'workflow-transfer-'.uniqid();

        $otpResponse = $this->withHeaders(['Authorization' => 'Bearer '.$authToken])
            ->postJson('/api/v1/customer/wallet/transfer/otp', [
                'recipient_wallet_id' => $recipientWallet->wallet_id,
                'amount' => 100,
                'description' => 'Workflow transfer',
            ], ['Idempotency-Key' => $transferIdempotencyKey]);

        $otpResponse->assertCreated();

        $this->withHeaders(['Authorization' => 'Bearer '.$authToken])
            ->postJson('/api/v1/customer/wallet/transfer', [
                'recipient_wallet_id' => $recipientWallet->wallet_id,
                'amount' => 100,
                'description' => 'Workflow transfer',
                'otp_token' => $otpResponse->json('data.otp_token'),
                'otp' => 111111,
            ], ['Idempotency-Key' => $transferIdempotencyKey])
            ->assertOk()
            ->assertJsonPath('data.amount', 100);

        $customer->refresh();
        $recipient->refresh();

        $this->assertNotificationCount($customer, 4);
        $this->assertNotificationsInclude(
            $customer,
            'Transfer sent',
            'Money added to your wallet',
            'Password updated',
            'Application received - we\'re on it',
        );

        $this->withHeaders(['Authorization' => 'Bearer '.$authToken])
            ->getJson('/api/v1/customer/notifications')
            ->assertOk()
            ->assertJsonFragment(['description' => 'You sent 100.00 SDG to Recipient User.']);

        $this->assertNotificationCount($recipient, 1);
        $this->assertNotificationsInclude($recipient, 'Money received');

        $this->withCustomerAuthHeaders($recipient)
            ->getJson('/api/v1/customer/notifications')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.title', 'Money received')
            ->assertJsonFragment(['description' => 'You received 100.00 SDG from Workflow User.']);
    }

    private function assertNotificationCount(Customer $customer, int $expected): void
    {
        $this->withCustomerAuthHeaders($customer)
            ->getJson('/api/v1/customer/notifications')
            ->assertOk()
            ->assertJsonPath('data.total', $expected);
    }

    private function assertNotificationsInclude(Customer $customer, string ...$titles): void
    {
        $response = $this->withCustomerAuthHeaders($customer)
            ->getJson('/api/v1/customer/notifications')
            ->assertOk();

        $actualTitles = collect($response->json('data.data'))->pluck('title')->all();

        foreach ($titles as $title) {
            $this->assertContains(
                $title,
                $actualTitles,
                'Expected notification title not found. Got: '.implode(', ', $actualTitles)
            );
        }
    }

    private function assertLatestNotificationTitle(Customer $customer, string $title): void
    {
        $this->withCustomerAuthHeaders($customer)
            ->getJson('/api/v1/customer/notifications')
            ->assertOk()
            ->assertJsonPath('data.data.0.title', $title);
    }

    private function withCustomerAuthHeaders(Customer $customer): self
    {
        $auth = app(CustomerJwtService::class)->createToken(
            $customer->id,
            $customer->email ?: $customer->phone,
        );

        return $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
            'Accept' => 'application/json',
        ]);
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

    /**
     * @return array{0: Country, 1: City}
     */
    private function createCountryAndCity(): array
    {
        $country = Country::query()->create([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Sudan'],
            'short_name' => 'SD',
            'code' => '249',
            'status' => true,
        ]);

        $city = City::query()->create([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Khartoum'],
            'country_id' => $country->id,
            'status' => true,
        ]);

        return [$country, $city];
    }

    private function verifiedSmsOtpToken(string $phone): string
    {
        $smsResponse = $this->postJson('/api/v1/customer/otp/sms', ['phone' => $phone]);
        $smsResponse->assertCreated();

        $verifyResponse = $this->postJson('/api/v1/customer/otp/verify', [
            'token' => $smsResponse->json('data.token'),
            'code' => 111111,
        ]);

        $verifyResponse->assertCreated();

        return $verifyResponse->json('data.otp_token');
    }
}
