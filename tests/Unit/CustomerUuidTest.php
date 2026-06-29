<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Services\WalletService;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerUuidTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_uuid_primary_key(): void
    {
        $customer = Customer::factory()->create();

        $this->assertTrue(Str::isUuid((string) $customer->id));
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_get_code_uses_uuid_suffix_instead_of_integer_padding(): void
    {
        $customer = Customer::factory()->create();
        $suffix = strtoupper(substr(str_replace('-', '', (string) $customer->id), -8));

        $this->assertStringEndsWith($suffix, $customer->getCode());
        $this->assertStringStartsWith('CSMR', $customer->getCode());
        $this->assertDoesNotMatchRegularExpression('/CSMR0{2,}\d+$/', $customer->getCode());
    }

    public function test_wallet_public_id_uses_customer_phone_with_fastpay_suffix(): void
    {
        $this->seed(ChartOfAccountSeeder::class);

        $customer = Customer::factory()->active()->create(['phone' => '+249911122233']);
        $wallet = app(WalletService::class)->createForCustomer($customer);

        $this->assertNotNull($wallet);
        $this->assertSame('249911122233@fastpay', $wallet->wallet_id);
        $this->assertSame($customer->id, $wallet->customer_id);
    }
}
