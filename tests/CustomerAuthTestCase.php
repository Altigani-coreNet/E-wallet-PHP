<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CustomerAuthTestingDatabase;

abstract class CustomerAuthTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        CustomerAuthTestingDatabase::ensureExists();

        parent::setUp();

        $profileImageDir = public_path('customer_profile_images');
        if (! is_dir($profileImageDir)) {
            mkdir($profileImageDir, 0777, true);
        }
    }

    protected function connectionsToTransact(): array
    {
        return [CustomerAuthTestingDatabase::CONNECTION];
    }
}
