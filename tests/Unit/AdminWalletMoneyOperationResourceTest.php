<?php

namespace Tests\Unit;

use App\Http\Resources\AdminWalletActionResource;
use App\Http\Resources\AdminWalletMoneyOperationResource;
use App\Http\Resources\AdminWalletOwnerResource;
use App\Http\Resources\AdminWalletPaginatedResource;
use App\Http\Resources\AdminWalletResource;
use App\Models\Customer;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\LedgerService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class AdminWalletMoneyOperationResourceTest extends TestCase
{
    public function test_money_operation_resource_wraps_wallet_and_transaction_models(): void
    {
        $wallet = new Wallet([
            'wallet_id' => 'WAL-00000099',
            'user_number' => 'CSMR000099',
            'status' => 'active',
            'balance' => 200,
            'available_balance' => 200,
            'currency_code' => 'SDG',
        ]);
        $wallet->id = '77777777-7777-4777-8777-777777777777';

        $transaction = new WalletTransaction([
            'type' => 'topup',
            'direction' => 'credit',
            'amount' => 200,
            'balance_after' => 200,
            'description' => 'Station cash-in',
        ]);
        $transaction->id = '88888888-8888-4888-8888-888888888888';

        $payload = AdminWalletMoneyOperationResource::make([
            'amount' => 200,
            'description' => 'Station cash-in',
            'wallet' => $wallet,
            'transaction' => $transaction,
            'posting_reference' => LedgerService::REF_WALLET_CASH_IN,
        ])->toArray(Request::create('/'));

        $this->assertSame(200.0, $payload['amount']);
        $this->assertSame('Station cash-in', $payload['description']);
        $this->assertSame(LedgerService::REF_WALLET_CASH_IN, $payload['posting_reference']);
        $this->assertSame('WAL-00000099', $payload['wallet']['wallet_id']);
        $this->assertSame('topup', $payload['transaction']['type']);
        $this->assertSame(200.0, $payload['transaction']['signed_amount']);
    }

    public function test_action_resource_wraps_message_and_wallet(): void
    {
        $wallet = new Wallet([
            'wallet_id' => 'WAL-00000001',
            'status' => 'frozen',
            'balance' => 100,
            'available_balance' => 100,
            'currency_code' => 'SDG',
        ]);
        $wallet->id = '99999999-9999-4999-8999-999999999999';

        $payload = AdminWalletActionResource::make([
            'message' => 'Wallet suspended successfully.',
            'wallet' => $wallet,
        ])->toArray(Request::create('/'));

        $this->assertSame('Wallet suspended successfully.', $payload['message']);
        $this->assertSame('frozen', $payload['wallet']['status']);
    }

    public function test_owner_resource_for_master_wallet(): void
    {
        $wallet = new Wallet([
            'wallet_id' => WalletService::MASTER_WALLET_ID,
            'user_number' => WalletService::MASTER_USER_NUMBER,
            'status' => 'active',
        ]);

        $payload = AdminWalletOwnerResource::make($wallet)->toArray(Request::create('/'));

        $this->assertSame('Master Wallet', $payload['name']);
        $this->assertSame('System equity / funding pool', $payload['description']);
    }

    public function test_owner_resource_for_customer(): void
    {
        $customer = new Customer([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+249900000099',
        ]);
        $customer->id = 99;

        $payload = AdminWalletOwnerResource::make($customer)->toArray(Request::create('/'));

        $this->assertSame('Jane Doe', $payload['name']);
        $this->assertSame('jane@example.com', $payload['email']);
    }

    public function test_paginated_resource_wraps_collection(): void
    {
        $wallet = new Wallet([
            'wallet_id' => 'WAL-00000001',
            'status' => 'active',
            'balance' => 50,
            'available_balance' => 50,
            'currency_code' => 'SDG',
        ]);
        $wallet->id = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

        $paginator = new LengthAwarePaginator([$wallet], 1, 15, 1);

        $payload = AdminWalletPaginatedResource::wrap($paginator, AdminWalletResource::class);

        $this->assertCount(1, $payload['data']);
        $this->assertSame(1, $payload['current_page']);
        $this->assertSame(15, $payload['per_page']);
        $this->assertSame(1, $payload['total']);
        $this->assertSame(1, $payload['last_page']);
    }
}
