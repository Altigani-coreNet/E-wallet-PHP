<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Modules\CustomerAuth\Resources\CustomerAuthResource;
use Illuminate\Http\Request;
use Tests\CustomerAuthTestCase;

class CustomerAuthResourceTest extends CustomerAuthTestCase
{
    public function test_resource_serializes_customer_fields(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
            'phone' => '+249912345678',
            'balance' => 150.5,
            'profile_completed' => true,
        ]);

        $payload = CustomerAuthResource::make($customer)->toArray(Request::create('/'));

        $this->assertSame($customer->id, $payload['id']);
        $this->assertSame('Ahmed', $payload['name']);
        $this->assertSame('ahmed@example.com', $payload['email']);
        $this->assertSame('+249912345678', $payload['phone']);
        $this->assertSame('150.50', $payload['balance']);
        $this->assertTrue($payload['profileCompleted']);
        $this->assertNull($payload['country']);
        $this->assertNull($payload['city']);
    }
}
