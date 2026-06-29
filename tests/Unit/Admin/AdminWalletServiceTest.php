<?php

namespace Tests\Unit\Admin;

use App\Models\Customer;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Admin\AdminWalletService;
use Database\Seeders\ChartOfAccountSeeder;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\CustomerAuthTestCase;
use Tests\Support\AdminWalletTestHelpers;

class AdminWalletServiceTest extends CustomerAuthTestCase
{
    use AdminWalletTestHelpers;

    private AdminWalletService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountSeeder::class);
        $this->service = app(AdminWalletService::class);
    }

    public function test_list_excludes_closed_wallets_by_default(): void
    {
        $customer = Customer::factory()->active()->create();
        $activeWallet = $this->createFundedWallet($customer, 100);

        $closedCustomer = Customer::factory()->active()->create();
        $closedWallet = $this->createFundedWallet($closedCustomer, 50);
        $closedWallet->update(['status' => 'closed']);

        $paginated = $this->service->list([], 15);

        $ids = collect($paginated->items())->pluck('id')->all();

        $this->assertContains($activeWallet->id, $ids);
        $this->assertNotContains($closedWallet->id, $ids);
    }

    public function test_list_includes_closed_wallets_when_status_filter_applied(): void
    {
        $customer = Customer::factory()->active()->create();
        $closedWallet = $this->createFundedWallet($customer, 50);
        $closedWallet->update(['status' => 'closed']);

        $paginated = $this->service->list(['status' => 'closed'], 15);

        $this->assertTrue(
            collect($paginated->items())->contains(fn (Wallet $wallet) => $wallet->id === $closedWallet->id)
        );
    }

    public function test_list_filters_by_search_on_wallet_id(): void
    {
        $customer = Customer::factory()->active()->create(['name' => 'Searchable User']);
        $wallet = $this->createFundedWallet($customer, 100);

        $paginated = $this->service->list(['search' => $wallet->wallet_id], 15);

        $this->assertSame(1, $paginated->total());
        $this->assertSame($wallet->id, $paginated->items()[0]->id);
    }

    public function test_show_returns_wallet_summary_totals(): void
    {
        [$senderWallet, $recipientWallet] = $this->createTransferPair(1000, 0);
        $this->transferBetweenWallets($senderWallet, $recipientWallet, 250, 'Unit test transfer');

        $wallet = $this->service->show($senderWallet->id);

        $this->assertSame($senderWallet->id, $wallet->id);
        $this->assertSame(2, $wallet->summary['transaction_count']);
        $this->assertSame(250.0, $wallet->summary['total_debits']);
        $this->assertSame(1000.0, $wallet->summary['total_credits']);
    }

    public function test_set_status_updates_wallet(): void
    {
        $customer = Customer::factory()->active()->create();
        $wallet = $this->createFundedWallet($customer, 100);

        $updated = $this->service->setStatus($wallet->id, 'frozen');

        $this->assertSame('frozen', $updated->status);
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'status' => 'frozen',
        ]);
    }

    public function test_set_status_rejects_invalid_status(): void
    {
        $customer = Customer::factory()->active()->create();
        $wallet = $this->createFundedWallet($customer, 100);

        $this->expectException(InvalidArgumentException::class);

        $this->service->setStatus($wallet->id, 'invalid-status');
    }

    public function test_set_status_blocks_master_wallet_suspend_or_close(): void
    {
        $master = $this->createMasterWallet(5000);

        $this->expectException(InvalidArgumentException::class);
        $this->service->setStatus($master->id, 'frozen');
    }

    public function test_list_filters_by_wallet_type_master(): void
    {
        $master = $this->createMasterWallet(1000);
        $customer = Customer::factory()->active()->create();
        $userWallet = $this->createFundedWallet($customer, 100);

        $masters = $this->service->list(['wallet_type' => 'master'], 50);
        $users = $this->service->list(['wallet_type' => 'user'], 50);

        $masterIds = collect($masters->items())->pluck('id')->all();
        $userIds = collect($users->items())->pluck('id')->all();

        $this->assertContains($master->id, $masterIds);
        $this->assertNotContains($userWallet->id, $masterIds);
        $this->assertContains($userWallet->id, $userIds);
        $this->assertNotContains($master->id, $userIds);
    }

    public function test_wallet_transactions_returns_paginated_rows_with_counterparty(): void
    {
        [$senderWallet, $recipientWallet] = $this->createTransferPair(800, 0);
        $this->transferBetweenWallets($senderWallet, $recipientWallet, 200, 'Counterparty test');

        $paginated = $this->service->walletTransactions($senderWallet->id, [], 15);

        $this->assertSame(2, $paginated->total());

        $transfer = collect($paginated->items())->first(
            fn (WalletTransaction $tx) => $tx->type === 'transfer' && $tx->direction === 'debit'
        );

        $this->assertNotNull($transfer);
        $this->assertSame(-200.0, $transfer->signed_amount);
        $this->assertNotNull($transfer->counterparty);
        $this->assertSame($recipientWallet->wallet_id, $transfer->counterparty['wallet_id']);
    }

    public function test_all_transactions_filters_by_direction(): void
    {
        [$senderWallet, $recipientWallet] = $this->createTransferPair(500, 0);
        $this->transferBetweenWallets($senderWallet, $recipientWallet, 100, 'Direction filter');

        $debits = $this->service->allTransactions(['direction' => 'debit'], 50);
        $credits = $this->service->allTransactions(['direction' => 'credit'], 50);

        // Funding the sender records: master fund (credit) + master issue leg (debit)
        // + sender topup (credit); the transfer adds sender (debit) + recipient (credit).
        $this->assertSame(2, $debits->total());
        $this->assertSame(3, $credits->total());
        $this->assertTrue(collect($debits->items())->every(fn (WalletTransaction $tx) => $tx->direction === 'debit'));
        $this->assertTrue(collect($credits->items())->every(fn (WalletTransaction $tx) => $tx->direction === 'credit'));
    }

    public function test_export_wallets_returns_spreadsheet_download(): void
    {
        $customer = Customer::factory()->active()->create();
        $this->createFundedWallet($customer, 100);

        $response = $this->service->exportWallets([]);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertStringContainsString('.xlsx', $response->getFile()->getFilename());
    }

    public function test_export_transactions_returns_spreadsheet_download(): void
    {
        [$senderWallet, $recipientWallet] = $this->createTransferPair(300, 0);
        $this->transferBetweenWallets($senderWallet, $recipientWallet, 75, 'Export test');

        $response = $this->service->exportTransactions([]);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertStringContainsString('.xlsx', $response->getFile()->getFilename());
    }
}
