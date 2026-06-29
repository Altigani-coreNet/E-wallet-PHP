<?php

namespace Tests\Unit;

use App\Http\Resources\AdminWalletResource;
use App\Http\Resources\AdminWalletTransactionResource;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Tests\TestCase;

class AdminWalletResourceTest extends TestCase
{
    public function test_wallet_resource_includes_owner_and_summary(): void
    {
        $merchant = new Merchant([
            'name' => 'Test Merchant',
            'business_name' => 'Test Business',
        ]);
        $merchant->id = '11111111-1111-4111-8111-111111111111';

        $customer = new Customer([
            'name' => 'Wallet Owner',
            'email' => 'owner@example.com',
            'phone' => '+249900000010',
            'merchant_id' => $merchant->id,
        ]);
        $customer->id = 42;

        $wallet = new Wallet([
            'wallet_id' => 'WAL-00000042',
            'user_number' => 'CSMR000042',
            'status' => 'active',
            'balance' => 150.25,
            'available_balance' => 150.25,
            'currency_code' => 'SDG',
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
        ]);
        $wallet->id = '22222222-2222-4222-8222-222222222222';
        $wallet->setAttribute('summary', [
            'transaction_count' => 3,
            'total_credits' => 200.0,
            'total_debits' => 49.75,
        ]);

        $customer->setRelation('merchant', $merchant);
        $wallet->setRelation('customer', $customer);

        $payload = AdminWalletResource::make($wallet)->toArray(Request::create('/'));

        $this->assertSame('WAL-00000042', $payload['wallet_id']);
        $this->assertSame('user', $payload['type']);
        $this->assertFalse($payload['is_master']);
        $this->assertSame('active', $payload['status']);
        $this->assertSame(150.25, $payload['balance']);
        $this->assertSame('Wallet Owner', $payload['owner']['name']);
        $this->assertSame('Test Business', $payload['owner']['merchant_name']);
        $this->assertSame(3, $payload['summary']['transaction_count']);
    }

    public function test_transaction_resource_includes_signed_amount_and_counterparty(): void
    {
        $wallet = new Wallet([
            'wallet_id' => 'WAL-00000001',
            'user_number' => 'CSMR000001',
        ]);
        $wallet->id = '33333333-3333-4333-8333-333333333333';

        $customer = new Customer([
            'name' => 'Sender',
            'phone' => '+249900000011',
        ]);
        $customer->id = 1;
        $wallet->setRelation('customer', $customer);

        $transaction = new WalletTransaction([
            'type' => 'transfer',
            'direction' => 'debit',
            'amount' => 120.00,
            'balance_after' => 880.00,
            'reference' => 'WALLET_TRANSFER',
            'description' => 'Transfer out',
        ]);
        $transaction->id = '44444444-4444-4444-8444-444444444444';
        $transaction->setAttribute('signed_amount', -120.0);
        $transaction->setAttribute('counterparty', [
            'wallet_id' => 'WAL-00000002',
            'owner_name' => 'Recipient',
        ]);
        $transaction->setRelation('wallet', $wallet);

        $payload = AdminWalletTransactionResource::make($transaction)->toArray(Request::create('/'));

        $this->assertSame('transfer', $payload['type']);
        $this->assertSame('debit', $payload['direction']);
        $this->assertSame(-120.0, $payload['signed_amount']);
        $this->assertSame('Sender', $payload['owner']['name']);
        $this->assertSame('WAL-00000002', $payload['counterparty']['wallet_id']);
    }

    public function test_transaction_resource_computes_signed_amount_when_missing(): void
    {
        $transaction = new WalletTransaction([
            'type' => 'topup',
            'direction' => 'credit',
            'amount' => 50.00,
            'balance_after' => 50.00,
        ]);
        $transaction->id = '55555555-5555-4555-8555-555555555555';

        $payload = AdminWalletTransactionResource::make($transaction)->toArray(Request::create('/'));

        $this->assertSame(50.0, $payload['signed_amount']);
    }

    public function test_wallet_resource_marks_master_wallet(): void
    {
        $wallet = new Wallet([
            'wallet_id' => WalletService::MASTER_WALLET_ID,
            'user_number' => WalletService::MASTER_USER_NUMBER,
            'status' => 'active',
            'balance' => 1000000,
            'available_balance' => 1000000,
            'currency_code' => 'SDG',
            'customer_id' => null,
        ]);
        $wallet->id = '66666666-6666-4666-8666-666666666666';

        $payload = AdminWalletResource::make($wallet)->toArray(\Illuminate\Http\Request::create('/'));

        $this->assertSame('master', $payload['type']);
        $this->assertTrue($payload['is_master']);
        $this->assertSame('Master Wallet', $payload['owner']['name']);
    }
}
