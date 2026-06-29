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
            'national_id' => 'NID-RESOURCE-001',
            'balance' => 150.5,
            'profile_completed' => true,
        ]);

        $payload = CustomerAuthResource::make($customer)->toArray(Request::create('/'));

        $this->assertSame($customer->id, $payload['id']);
        $this->assertTrue(\Illuminate\Support\Str::isUuid((string) $payload['id']));
        $this->assertSame('Ahmed', $payload['name']);
        $this->assertSame('ahmed@example.com', $payload['email']);
        $this->assertSame('+249912345678', $payload['phone']);
        $this->assertSame('NID-RESOURCE-001', $payload['nationalId']);
        $this->assertSame('150.50', $payload['balance']);
        $this->assertNull($payload['walletId']);
        $this->assertNull($payload['availableBalance']);
        $this->assertTrue($payload['profileCompleted']);
        $this->assertNull($payload['country']);
        $this->assertNull($payload['city']);
    }

    public function test_resource_includes_wallet_id_and_balance_when_wallet_loaded(): void
    {
        $this->seed(\Database\Seeders\ChartOfAccountSeeder::class);

        $customer = Customer::factory()->active()->create([
            'phone' => '+249912345678',
            'balance' => 99.99,
        ]);

        $wallet = app(\App\Services\WalletService::class)->createForCustomer($customer);
        $wallet->update(['balance' => '250.75', 'available_balance' => '200.50']);

        $customer->load('wallet');

        $payload = CustomerAuthResource::make($customer)->toArray(Request::create('/'));

        $this->assertSame('249912345678@fastpay', $payload['walletId']);
        $this->assertSame('250.75', $payload['balance']);
        $this->assertSame('200.50', $payload['availableBalance']);
    }
}
