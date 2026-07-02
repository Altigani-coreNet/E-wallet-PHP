<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerRejection;
use App\Modules\CustomerAuth\Services\CustomerAttachmentService;
use App\Services\WalletService;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\CustomerAuthTestCase;
use Tests\Support\AdminWalletTestHelpers;
use Tests\Support\CustomerAuthTestHelper;

class CustomerProfileRejectionTest extends CustomerAuthTestCase
{
    use AdminWalletTestHelpers;
    use CustomerAuthTestHelper;

    private Country $country;

    private City $city;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureCustomerAuthTesting();
        $this->seed(ChartOfAccountSeeder::class);
        $this->createMasterWallet(1_000_000);
        [$this->country, $this->city] = $this->seedCountryAndCity();

        $dir = public_path('customer_profile_images');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        Storage::fake('public');
    }

    public function test_rejected_customer_can_fetch_rejected_fields(): void
    {
        $customer = $this->createRejectedCustomer(['national_id']);

        $response = $this->getJson(
            '/api/v1/customer/profile/rejected-fields',
            $this->customerAuthHeaders($customer)
        );

        $response->assertOk()
            ->assertJsonPath('data.rejection.invalid_fields.0', 'national_id')
            ->assertJsonPath('data.rejection.rejection_reason', 'National ID is invalid.');
    }

    public function test_rejected_customer_can_resubmit_and_returns_pending(): void
    {
        $customer = $this->createRejectedCustomer(['national_id']);

        $response = $this->postJson(
            '/api/v1/customer/profile/update-rejected-fields',
            ['national_id' => 'NEW-NID-'.uniqid()],
            $this->customerAuthHeaders($customer)
        );

        $response->assertOk();

        $customer->refresh();
        $this->assertSame(Customer::STATUS_PENDING, $customer->status);
    }

    public function test_profile_includes_rejection_for_rejected_customer(): void
    {
        $customer = $this->createRejectedCustomer(['email']);

        $response = $this->getJson(
            '/api/v1/customer/profile',
            $this->customerAuthHeaders($customer)
        );

        $response->assertOk()
            ->assertJsonPath('data.rejection.rejection_reason', 'National ID is invalid.')
            ->assertJsonPath('data.customer.status', Customer::STATUS_REJECTED);
    }

    public function test_rejected_customer_cannot_transfer(): void
    {
        $customer = $this->createRejectedCustomer(['name']);
        app(WalletService::class)->createForCustomer($customer);

        $recipient = Customer::factory()->create([
            'email' => 'recipient-'.uniqid().'@example.com',
            'phone' => '+24992'.random_int(1000000, 9999999),
            'status' => Customer::STATUS_ACTIVE,
            'profile_completed' => true,
            'password' => Hash::make('Password1!'),
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);
        app(WalletService::class)->createForCustomer($recipient);

        $this->postJson(
            '/api/v1/customer/wallet/transfer',
            [
                'recipient_wallet_id' => $recipient->wallet->wallet_id,
                'amount' => 10,
            ],
            $this->customerAuthHeaders($customer)
        )->assertForbidden();
    }

    public function test_complete_profile_without_passport_returns_422(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'no-passport-'.uniqid().'@example.com',
            'phone' => '+24995'.random_int(1000000, 9999999),
            'profile_completed' => false,
            'password' => Hash::make('Password1!'),
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);

        $this->post('/api/v1/customer/profile/complete', [
            'firstName' => 'Passport Test',
            'nationalId' => 'NID-NO-PASS-'.uniqid(),
            'email' => $customer->email,
            'birthDate' => '1990-05-15',
            'gender' => 'male',
            'cityId' => $this->city->id,
            'country_code' => '249',
            'picture' => UploadedFile::fake()->image('avatar.jpg'),
        ], array_merge($this->customerAuthHeaders($customer), ['Accept' => 'application/json']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['passport']);
    }

    public function test_complete_profile_with_picture_and_passport_creates_attachments(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'with-passport-'.uniqid().'@example.com',
            'phone' => '+24996'.random_int(1000000, 9999999),
            'profile_completed' => false,
            'password' => Hash::make('Password1!'),
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);

        $this->post('/api/v1/customer/profile/complete', [
            'firstName' => 'Passport Test',
            'nationalId' => 'NID-PASS-'.uniqid(),
            'email' => $customer->email,
            'birthDate' => '1990-05-15',
            'gender' => 'male',
            'cityId' => $this->city->id,
            'country_code' => '249',
            'picture' => UploadedFile::fake()->image('avatar.jpg'),
            'passport' => UploadedFile::fake()->create('passport.pdf', 100, 'application/pdf'),
        ], array_merge($this->customerAuthHeaders($customer), ['Accept' => 'application/json']))
            ->assertOk();

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Customer::class,
            'attachable_id' => $customer->id,
            'url_type' => CustomerAttachmentService::URL_TYPE_PROFILE_IMAGE,
        ]);
        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Customer::class,
            'attachable_id' => $customer->id,
            'url_type' => CustomerAttachmentService::URL_TYPE_PASSPORT_DOCUMENT,
        ]);
    }

    public function test_reject_with_passport_document_requires_passport_on_resubmit(): void
    {
        $customer = $this->createRejectedCustomer([], ['passport']);

        $this->postJson(
            '/api/v1/customer/profile/update-rejected-fields',
            ['national_id' => 'UPDATED-NID-'.uniqid()],
            $this->customerAuthHeaders($customer)
        )->assertStatus(422)
            ->assertJsonValidationErrors(['passport']);

        $response = $this->post(
            '/api/v1/customer/profile/update-rejected-fields',
            [
                'passport' => UploadedFile::fake()->create('passport.pdf', 100, 'application/pdf'),
            ],
            array_merge($this->customerAuthHeaders($customer), ['Accept' => 'application/json'])
        );

        $response->assertOk();

        $customer->refresh();
        $this->assertSame(Customer::STATUS_PENDING, $customer->status);
    }

    public function test_get_rejected_fields_returns_attachment_urls(): void
    {
        $customer = $this->createRejectedCustomer(['national_id'], ['picture']);

        app(CustomerAttachmentService::class)->uploadProfileImage(
            $customer,
            UploadedFile::fake()->image('profile.jpg')
        );
        app(CustomerAttachmentService::class)->uploadPassportDocument(
            $customer,
            UploadedFile::fake()->create('passport.pdf', 100, 'application/pdf')
        );

        $response = $this->getJson(
            '/api/v1/customer/profile/rejected-fields',
            $this->customerAuthHeaders($customer)
        );

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'attachments' => ['profile_image', 'passport'],
                ],
            ])
            ->assertJsonPath('data.rejection.missing_attachments.0', 'picture');
    }

    public function test_get_rejected_fields_maps_legacy_missing_attachment_keys(): void
    {
        $customer = $this->createRejectedCustomer(['national_id'], ['profile_image', 'passport_document']);

        $response = $this->getJson(
            '/api/v1/customer/profile/rejected-fields',
            $this->customerAuthHeaders($customer)
        );

        $response->assertOk()
            ->assertJsonPath('data.rejection.missing_attachments', ['picture', 'passport']);
    }

    public function test_active_customer_profile_update_creates_change_request(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'active-update-'.uniqid().'@example.com',
            'phone' => '+24993'.random_int(1000000, 9999999),
            'status' => Customer::STATUS_ACTIVE,
            'profile_completed' => true,
            'password' => Hash::make('Password1!'),
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);

        $response = $this->postJson(
            '/api/v1/customer/profile/update',
            [
                'firstName' => 'Updated Name',
                'birthDate' => '1990-01-01',
                'gender' => 'male',
                'cityId' => $this->city->id,
                'country_code' => '249',
            ],
            $this->customerAuthHeaders($customer)
        );

        $response->assertOk();

        $customer->refresh();
        $this->assertSame(Customer::STATUS_REQUESTING_UPDATED, $customer->status);
        $this->assertDatabaseHas('change_requests', [
            'changeable_type' => Customer::class,
            'changeable_id' => $customer->id,
            'status' => 'pending',
        ]);
    }

    /**
     * @param  list<string>  $invalidFields
     * @param  list<string>|null  $missingAttachments
     */
    private function createRejectedCustomer(array $invalidFields, ?array $missingAttachments = null): Customer
    {
        $customer = Customer::factory()->create([
            'email' => 'rejected-'.uniqid().'@example.com',
            'phone' => '+24994'.random_int(1000000, 9999999),
            'status' => Customer::STATUS_REJECTED,
            'profile_completed' => true,
            'password' => Hash::make('Password1!'),
            'national_id' => 'OLD-NID',
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
        ]);

        CustomerRejection::create([
            'customer_id' => $customer->id,
            'rejection_reason' => 'National ID is invalid.',
            'invalid_fields' => $invalidFields,
            'missing_attachments' => $missingAttachments,
            'rejected_by' => \App\Models\Admin::factory()->active()->create()->id,
        ]);

        return $customer;
    }
}
