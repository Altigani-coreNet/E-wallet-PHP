<?php

namespace Tests\Unit;

use App\Models\Attachments;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Modules\CustomerAuth\Services\CustomerAttachmentService;
use App\Services\WalletService;
use Illuminate\Support\Str;
use Tests\CustomerAuthTestCase;

class CustomerProfileCompletionTest extends CustomerAuthTestCase
{
    public function test_registered_only_customer_has_low_completion(): void
    {
        $customer = Customer::factory()->create([
            'phone' => '+249911100001',
            'national_id' => null,
            'birth_date' => null,
            'gender' => null,
            'country_id' => null,
            'city_id' => null,
            'profile_completed' => false,
            'status' => Customer::STATUS_PENDING,
        ]);

        $result = Customer::calculateProfileCompletion($customer);

        $this->assertSame(10, $result['completion']);
        $this->assertContains('Complete your personal profile information.', $result['missing']);
        $this->assertContains('Submit your KYC profile for review.', $result['missing']);
        $this->assertContains('Account is pending approval.', $result['missing']);
        $this->assertSame(0, $result['documents']['uploaded']);
    }

    public function test_profile_complete_pending_customer_scores_eighty_two_percent(): void
    {
        $this->seed(\Database\Seeders\ChartOfAccountSeeder::class);

        [$country, $city] = $this->createCountryAndCity();

        $customer = Customer::factory()->create([
            'phone' => '+249911100002',
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
            'national_id' => 'NID-001',
            'birth_date' => '1990-05-15',
            'gender' => 'male',
            'country_id' => $country->id,
            'city_id' => $city->id,
            'profile_image' => 'customer_profile_images/test.jpg',
            'profile_completed' => true,
            'status' => Customer::STATUS_PENDING,
        ]);

        $this->createAttachment($customer, CustomerAttachmentService::URL_TYPE_PASSPORT_DOCUMENT);
        app(WalletService::class)->createForCustomer($customer->fresh());

        $customer->load(['wallet', 'attachments']);

        $result = Customer::calculateProfileCompletion($customer);

        $this->assertSame(82, $result['completion']);
        $this->assertContains('Account is pending approval.', $result['missing']);
        $this->assertSame(2, $result['documents']['uploaded']);
    }

    public function test_active_customer_with_full_kyc_scores_one_hundred_percent(): void
    {
        $this->seed(\Database\Seeders\ChartOfAccountSeeder::class);

        [$country, $city] = $this->createCountryAndCity();

        $customer = Customer::factory()->active()->create([
            'phone' => '+249911100003',
            'name' => 'Sara',
            'email' => 'sara@example.com',
            'national_id' => 'NID-002',
            'birth_date' => '1992-08-20',
            'gender' => 'female',
            'country_id' => $country->id,
            'city_id' => $city->id,
            'profile_image' => 'customer_profile_images/test.jpg',
        ]);

        $this->createAttachment($customer, CustomerAttachmentService::URL_TYPE_PASSPORT_DOCUMENT);
        app(WalletService::class)->createForCustomer($customer->fresh());

        $customer->load(['wallet', 'attachments']);

        $result = Customer::calculateProfileCompletion($customer);

        $this->assertSame(100, $result['completion']);
        $this->assertSame([], $result['missing']);
        $this->assertSame(Customer::STATUS_ACTIVE, $result['status']);
    }

    public function test_rejected_customer_includes_rejection_reason_in_missing(): void
    {
        $customer = Customer::factory()->create([
            'phone' => '+249911100004',
            'profile_completed' => true,
            'status' => Customer::STATUS_REJECTED,
        ]);

        $customer->setRelation('rejections', collect([
            new \App\Models\CustomerRejection([
                'rejection_reason' => 'Passport image is unclear.',
            ]),
        ]));

        $result = Customer::calculateProfileCompletion($customer);

        $this->assertContains(
            'Account approval was rejected. Reason: Passport image is unclear.',
            $result['missing'],
        );
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

    private function createAttachment(Customer $customer, string $urlType): Attachments
    {
        return $customer->attachments()->create([
            'url' => 'customer_documents/sample.pdf',
            'url_type' => $urlType,
            'type' => 'document',
        ]);
    }
}
