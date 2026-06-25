<?php

namespace Tests\Unit;

use App\Http\Resources\AdminCustomerResource;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use Illuminate\Http\Request;
use Tests\TestCase;

class AdminCustomerResourceTest extends TestCase
{
    public function test_resource_returns_flat_localized_country_and_city_names(): void
    {
        app()->setLocale('en');

        $country = new Country([
            'id' => '00000000-0000-4000-8000-000000000099',
            'name' => ['en' => 'Sudan', 'ar' => 'السودان'],
            'code' => 'SD',
        ]);

        $city = new City([
            'id' => '10000000-0000-4000-8000-000000000099',
            'name' => ['en' => 'Khartoum', 'ar' => 'خرطوم'],
            'country_id' => $country->id,
        ]);

        $customer = new Customer([
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'name' => 'Resource Customer',
            'email' => 'resource@example.com',
            'phone' => '+249900000001',
            'status' => Customer::STATUS_PENDING,
            'balance' => 10.5,
        ]);

        $customer->setRelation('country', $country);
        $customer->setRelation('city', $city);

        $payload = AdminCustomerResource::make($customer)->toArray(Request::create('/'));

        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $payload['uuid']);
        $this->assertSame(Customer::STATUS_PENDING, $payload['status']);
        $this->assertSame('Sudan', $payload['country_name']);
        $this->assertSame('Khartoum', $payload['city_name']);
        $this->assertSame('Sudan', $payload['country']['name']);
        $this->assertSame('Khartoum', $payload['city']['name']);
        $this->assertArrayNotHasKey('id', $payload);
    }

    public function test_resource_returns_null_profile_image_url_when_missing(): void
    {
        $customer = new Customer([
            'uuid' => '550e8400-e29b-41d4-a716-446655440001',
            'name' => 'No Image',
            'email' => 'no-image@example.com',
            'phone' => '+249900000002',
            'status' => Customer::STATUS_ACTIVE,
            'profile_image' => null,
        ]);

        $payload = AdminCustomerResource::make($customer)->toArray(Request::create('/'));

        $this->assertNull($payload['profile_image_url']);
    }

    public function test_resource_returns_profile_image_url_when_image_set(): void
    {
        $customer = new Customer([
            'uuid' => '550e8400-e29b-41d4-a716-446655440002',
            'name' => 'With Image',
            'email' => 'with-image@example.com',
            'phone' => '+249900000003',
            'status' => Customer::STATUS_ACTIVE,
            'profile_image' => 'customer_profiles/test.jpg',
        ]);

        $payload = AdminCustomerResource::make($customer)->toArray(Request::create('/'));

        $this->assertIsString($payload['profile_image_url']);
        $this->assertStringContainsString('customer_profiles/test.jpg', $payload['profile_image_url']);
    }
}
