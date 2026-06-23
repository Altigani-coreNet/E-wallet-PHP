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
    }

    protected function connectionsToTransact(): array
    {
        return [CustomerAuthTestingDatabase::CONNECTION];
    }
}
