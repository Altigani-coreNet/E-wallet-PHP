<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Partner;
use App\Models\Service;
use App\Modules\CustomerAuth\Support\CustomerJwtService;
use Illuminate\Support\Str;
use Tests\CustomerAuthTestCase;
use Tests\Support\CustomerServicesCatalogTestHelper;

class CustomerServicesCatalogApiTest extends CustomerAuthTestCase
{
    use CustomerServicesCatalogTestHelper;

    private const TEST_PHONE = '+249912345679';

    public function test_services_catalog_routes_require_auth(): void
    {
        $this->getJson('/api/v1/customer/services/catalog')->assertStatus(401);
        $this->getJson('/api/v1/customer/services/home')->assertStatus(401);
        $this->getJson('/api/v1/customer/services/'.Str::uuid())->assertStatus(401);
        $this->getJson('/api/v1/customer/partners/'.Str::uuid())->assertStatus(401);
    }

    public function test_can_fetch_services_catalog(): void
    {
        $catalog = $this->seedCustomerServicesCatalog();
        $customer = Customer::factory()->create(['phone' => self::TEST_PHONE]);

        $response = $this->withCustomerToken($customer)
            ->getJson('/api/v1/customer/services/catalog');

        $response->assertOk()
            ->assertJson([
                'status' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'categories' => [
                        [
                            'id',
                            'name_en',
                            'name_ar',
                            'code',
                            'category_image',
                            'services' => [
                                [
                                    'id',
                                    'service_name',
                                    'service_type',
                                    'products',
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('data.categories.0.id', $catalog['category']->id)
            ->assertJsonPath('data.categories.0.services.0.id', $catalog['service']->id);
    }

    public function test_can_fetch_home_services(): void
    {
        $catalog = $this->seedCustomerServicesCatalog();
        $customer = Customer::factory()->create(['phone' => self::TEST_PHONE]);

        $response = $this->withCustomerToken($customer)
            ->getJson('/api/v1/customer/services/home?limit=5');

        $response->assertOk()
            ->assertJson([
                'status' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'categories',
                    'quick_action' => [
                        'id',
                        'services',
                    ],
                ],
            ])
            ->assertJsonPath('data.categories.0.id', $catalog['category']->id);
    }

    public function test_can_fetch_service_details(): void
    {
        $catalog = $this->seedCustomerServicesCatalog();
        $customer = Customer::factory()->create(['phone' => self::TEST_PHONE]);

        $response = $this->withCustomerToken($customer)
            ->getJson('/api/v1/customer/services/'.$catalog['service']->id);

        $response->assertOk()
            ->assertJson([
                'status' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'service' => [
                        'id',
                        'service_name',
                        'service_type',
                        'category',
                        'products',
                    ],
                ],
            ])
            ->assertJsonPath('data.service.id', $catalog['service']->id)
            ->assertJsonPath('data.service.products.0.id', $catalog['product']->id);
    }

    public function test_service_details_returns_not_found_for_inactive_service(): void
    {
        $catalog = $this->seedCustomerServicesCatalog();
        $customer = Customer::factory()->create(['phone' => self::TEST_PHONE]);

        Service::query()->whereKey($catalog['service']->id)->update(['is_active' => false]);

        $response = $this->withCustomerToken($customer)
            ->getJson('/api/v1/customer/services/'.$catalog['service']->id);

        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Service not found or not available.',
            ]);
    }

    public function test_can_fetch_partner_profile(): void
    {
        $catalog = $this->seedCustomerServicesCatalog();
        $customer = Customer::factory()->create(['phone' => self::TEST_PHONE]);

        $response = $this->withCustomerToken($customer)
            ->getJson('/api/v1/customer/partners/'.$catalog['partner']->id);

        $response->assertOk()
            ->assertJson([
                'status' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'partner' => [
                        'id',
                        'name',
                        'merchant_code',
                        'status',
                        'is_active',
                    ],
                ],
            ])
            ->assertJsonPath('data.partner.id', $catalog['partner']->id)
            ->assertJsonPath('data.partner.name', 'Test Partner');
    }

    public function test_partner_returns_not_found_for_unapproved_partner(): void
    {
        $catalog = $this->seedCustomerServicesCatalog();
        $customer = Customer::factory()->create(['phone' => self::TEST_PHONE]);

        Partner::query()->whereKey($catalog['partner']->id)->update(['status' => 'pending']);

        $response = $this->withCustomerToken($customer)
            ->getJson('/api/v1/customer/partners/'.$catalog['partner']->id);

        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Partner not found or not available.',
            ]);
    }

    public function test_catalog_filters_by_country_id(): void
    {
        $catalog = $this->seedCustomerServicesCatalog();
        $customer = Customer::factory()->create(['phone' => self::TEST_PHONE]);

        $otherCountryId = (string) Str::uuid();

        $response = $this->withCustomerToken($customer)
            ->getJson('/api/v1/customer/services/catalog?country_id='.$otherCountryId);

        $response->assertOk()
            ->assertJsonPath('data.categories', []);
    }

    private function withCustomerToken(Customer $customer): self
    {
        $auth = app(CustomerJwtService::class)->createToken(
            $customer->id,
            $customer->email ?: $customer->phone,
        );

        return $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ]);
    }
}
