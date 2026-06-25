<?php

namespace Tests\Unit;

use App\Models\Customer;
use Tests\TestCase;

class CustomerProfileImageTest extends TestCase
{
    public function test_get_profile_image_api_returns_null_when_no_image(): void
    {
        $customer = new Customer([
            'profile_image' => null,
        ]);

        $this->assertNull($customer->getProfileImageApi());
    }

    public function test_get_profile_image_api_builds_asset_url_when_image_set(): void
    {
        $customer = new Customer([
            'profile_image' => 'customer_profiles/avatar.jpg',
        ]);

        $url = $customer->getProfileImageApi();

        $this->assertIsString($url);
        $this->assertStringContainsString('customer_profiles/avatar.jpg', $url);
    }
}
