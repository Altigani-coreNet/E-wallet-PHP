<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\WalletTransaction;
use App\Modules\CustomerAuth\Services\CustomerWalletService;
use App\Services\WalletService;
use Database\Seeders\ChartOfAccountSeeder;
use Tests\CustomerAuthTestCase;

class CustomerWalletServiceTest extends CustomerAuthTestCase
{
    private CustomerWalletService $customerWalletService;

    private WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountSeeder::class);
        $this->walletService = app(WalletService::class);
        $this->customerWalletService = app(CustomerWalletService::class);

        $master = $this->walletService->createMasterWallet();
        $this->walletService->cashIn($master, 1000000, 'Fund master float');
    }

    public function test_transactions_returns_pagination_meta_and_newest_first(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(1000.00, 0.00);

        foreach ([10, 20, 30] as $amount) {
            $this->walletService->transfer(
                $sender->wallet,
                $recipient->wallet,
                (float) $amount,
                "Transfer {$amount}"
            );
        }

        $result = $this->customerWalletService->transactions($sender, ['per_page' => 2, 'page' => 1]);

        $this->assertSame(2, $result['per_page']);
        $this->assertSame(1, $result['current_page']);
        $this->assertSame(4, $result['total']);
        $this->assertSame(2, $result['last_page']);
        $this->assertCount(2, $result['data']);
        $this->assertSame(30.0, $result['data'][0]['amount']);
        $this->assertSame('debit', $result['data'][0]['direction']);
        $this->assertSame(-30.0, $result['data'][0]['signed_amount']);
        $this->assertArrayHasKey('counterparty', $result['data'][0]);
    }

    public function test_transactions_includes_counterparty_for_transfers(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);

        $this->walletService->transfer(
            $sender->wallet,
            $recipient->wallet,
            75.00,
            'Counterparty test transfer'
        );

        $senderResult = $this->customerWalletService->transactions($sender);
        $this->assertSame($recipient->wallet->wallet_id, $senderResult['data'][0]['counterparty']['wallet_id']);
        $this->assertSame($recipient->name, $senderResult['data'][0]['counterparty']['name']);
        $this->assertSame('credit', $senderResult['data'][0]['counterparty']['direction']);

        $recipientResult = $this->customerWalletService->transactions($recipient);
        $this->assertSame($sender->wallet->wallet_id, $recipientResult['data'][0]['counterparty']['wallet_id']);
        $this->assertSame($sender->name, $recipientResult['data'][0]['counterparty']['name']);
        $this->assertSame('debit', $recipientResult['data'][0]['counterparty']['direction']);
    }

    public function test_show_transaction_returns_details_and_related_transfer_legs(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);

        $this->walletService->transfer(
            $sender->wallet,
            $recipient->wallet,
            40.00,
            'Show transfer',
            0,
            2.00,
            'Show note'
        );

        $debitTx = WalletTransaction::query()
            ->where('wallet_id', $sender->wallet->id)
            ->where('direction', 'debit')
            ->latest('id')
            ->first();

        $result = $this->customerWalletService->showTransaction($sender, $debitTx->id);

        $this->assertTrue($result['transaction']['is_own']);
        $this->assertSame(-40.0, $result['transaction']['signed_amount']);
        $this->assertSame($recipient->wallet->wallet_id, $result['transaction']['counterparty']['wallet_id']);
        $this->assertCount(2, $result['related_transactions']);
        $this->assertSame('debit', $result['related_transactions'][0]['direction']);
        $this->assertSame('credit', $result['related_transactions'][1]['direction']);
        $this->assertTrue($result['related_transactions'][0]['is_own']);
        $this->assertFalse($result['related_transactions'][1]['is_own']);
        $this->assertSame($recipient->name, $result['related_transactions'][1]['wallet']['owner_name']);
    }

    public function test_show_transaction_rejects_other_customer_wallet_rows(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);

        $this->walletService->transfer($sender->wallet, $recipient->wallet, 20.00, 'Private transfer');

        $recipientDebit = WalletTransaction::query()
            ->where('wallet_id', $recipient->wallet->id)
            ->where('direction', 'credit')
            ->latest('id')
            ->first();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->customerWalletService->showTransaction($sender, $recipientDebit->id);
    }

    public function test_transactions_filters_by_type_direction_and_search(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);

        $this->walletService->transfer($sender->wallet, $recipient->wallet, 50.00, 'Unique lunch transfer', 0, 0, 'Burger note');

        $byType = $this->customerWalletService->transactions($sender, [
            'type' => 'transfer',
            'direction' => 'debit',
        ]);
        $this->assertSame(1, $byType['total']);
        $this->assertSame('transfer', $byType['data'][0]['type']);

        $bySearch = $this->customerWalletService->transactions($sender, [
            'search' => 'Burger',
        ]);
        $this->assertSame(1, $bySearch['total']);
        $this->assertSame('Burger note', $bySearch['data'][0]['note']);

        $noMatch = $this->customerWalletService->transactions($sender, [
            'search' => 'nonexistent-term',
        ]);
        $this->assertSame(0, $noMatch['total']);
        $this->assertSame([], $noMatch['data']);
    }

    public function test_transactions_filters_by_date_range(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);

        $this->walletService->transfer($sender->wallet, $recipient->wallet, 25.00, 'Old transfer');

        $oldDebit = WalletTransaction::query()
            ->where('wallet_id', $sender->wallet->id)
            ->where('direction', 'debit')
            ->latest('id')
            ->first();

        $oldDebit->forceFill(['created_at' => now()->subDays(5)])->save();

        $this->walletService->transfer($sender->wallet, $recipient->wallet, 15.00, 'Today transfer');

        $today = now()->toDateString();
        $result = $this->customerWalletService->transactions($sender, [
            'date_from' => $today,
            'date_to' => $today,
        ]);

        $descriptions = collect($result['data'])->pluck('description')->all();
        $this->assertContains('Today transfer', $descriptions);
        $this->assertNotContains('Old transfer', $descriptions);
    }

    /**
     * @return array{0: Customer, 1: Customer}
     */
    private function createFundedCustomers(float $senderBalance, float $recipientBalance): array
    {
        $sender = Customer::factory()->active()->create();
        $recipient = Customer::factory()->active()->create();

        $senderWallet = $this->walletService->walletForCustomer($sender)
            ?? $this->walletService->createForCustomer($sender);
        $recipientWallet = $this->walletService->walletForCustomer($recipient)
            ?? $this->walletService->createForCustomer($recipient);

        if ($senderBalance > 0) {
            $this->walletService->cashIn($senderWallet, $senderBalance, 'Test funding');
        }

        if ($recipientBalance > 0) {
            $this->walletService->cashIn($recipientWallet, $recipientBalance, 'Test funding');
        }

        $sender->setRelation('wallet', $senderWallet->fresh());
        $recipient->setRelation('wallet', $recipientWallet->fresh());

        return [$sender, $recipient];
    }
}
