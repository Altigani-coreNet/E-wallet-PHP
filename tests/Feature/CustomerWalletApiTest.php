<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\TransactionLine;
use App\Models\WalletTransaction;
use App\Modules\Accounting\Services\AccountBalanceService;
use App\Modules\CustomerAuth\Support\CustomerJwtService;
use App\Services\LedgerService;
use App\Services\WalletService;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Testing\TestResponse;
use Tests\CustomerAuthTestCase;

class CustomerWalletApiTest extends CustomerAuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountSeeder::class);
        // Pre-fund the master float so customer cash-ins can be issued from it.
        $master = app(WalletService::class)->createMasterWallet();
        app(WalletService::class)->cashIn($master, 1000000, 'Fund master float');
    }

    public function test_wallet_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/customer/wallet/dashboard')->assertUnauthorized();
        $this->getJson('/api/v1/customer/wallet/transactions')->assertUnauthorized();
        $this->getJson('/api/v1/customer/wallet/query?identifier=test')->assertUnauthorized();
        $this->getJson('/api/v1/customer/wallet/resolve-recipient?identifier=test')->assertUnauthorized();
        $this->postJson('/api/v1/customer/wallet/transfer')->assertUnauthorized();
        $this->postJson('/api/v1/customer/wallet/withdraw')->assertUnauthorized();
    }

    public function test_dashboard_returns_wallet_balance_and_last_transaction(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);

        $this->transfer($sender, $recipient->wallet->wallet_id, 150.00, 'Lunch')
            ->assertOk();

        $response = $this->withCustomerToken($sender)
            ->getJson('/api/v1/customer/wallet/dashboard');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.wallet.balance', 350)
            ->assertJsonPath('data.wallet.available_balance', 350)
            ->assertJsonPath('data.last_transaction.type', 'transfer')
            ->assertJsonPath('data.last_transaction.direction', 'debit')
            ->assertJsonPath('data.last_transaction.amount', 150)
            ->assertJsonPath('data.last_transaction.balance_after', 350)
            ->assertJsonPath('data.recent_transactions.0.type', 'transfer');
    }

    public function test_dashboard_returns_up_to_five_recent_transactions_newest_first(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(1000.00, 0.00);

        foreach ([10, 20, 30, 40, 50, 60] as $amount) {
            $this->transfer($sender, $recipient->wallet->wallet_id, (float) $amount, "Transfer {$amount}")
                ->assertOk();
        }

        $response = $this->withCustomerToken($sender)->getJson('/api/v1/customer/wallet/dashboard');

        $response->assertOk()
            ->assertJsonCount(5, 'data.recent_transactions')
            ->assertJsonPath('data.recent_transactions.0.amount', 60)
            ->assertJsonPath('data.recent_transactions.4.amount', 20)
            ->assertJsonPath('data.last_transaction.amount', 60);
    }

    public function test_wallet_query_by_phone_returns_recipient_data(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);

        $response = $this->withCustomerToken($sender)->getJson(
            '/api/v1/customer/wallet/query?identifier='.rawurlencode($recipient->phone)
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.wallet_id', $recipient->wallet->wallet_id)
            ->assertJsonPath('data.name', $recipient->name)
            ->assertJsonPath('data.email', $recipient->email)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_wallet_query_by_wallet_id_returns_recipient_data(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);

        $response = $this->withCustomerToken($sender)->getJson(
            '/api/v1/customer/wallet/query?identifier='.rawurlencode($recipient->wallet->wallet_id)
        );

        $response->assertOk()
            ->assertJsonPath('data.wallet_id', $recipient->wallet->wallet_id)
            ->assertJsonPath('data.name', $recipient->name)
            ->assertJsonPath('data.email', $recipient->email);
    }

    public function test_transfer_with_note_persists_note_on_transaction(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);

        $response = $this->withCustomerToken($sender)->postJson('/api/v1/customer/wallet/transfer', [
            'recipient_wallet_id' => $recipient->wallet->wallet_id,
            'amount' => 25,
            'description' => 'Lunch money',
            'note' => 'Thanks for dinner',
        ], ['Idempotency-Key' => 'note-transfer-'.uniqid()]);

        $response->assertOk()
            ->assertJsonPath('data.note', 'Thanks for dinner')
            ->assertJsonPath('data.transaction.note', 'Thanks for dinner');

        $debit = WalletTransaction::query()
            ->where('wallet_id', $sender->wallet->id)
            ->where('direction', 'debit')
            ->latest('id')
            ->first();

        $this->assertSame('Thanks for dinner', $debit?->note);
    }

    public function test_transfer_rejects_phone_as_recipient_wallet_id(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);

        $this->withCustomerToken($sender)->postJson('/api/v1/customer/wallet/transfer', [
            'recipient_wallet_id' => $recipient->phone,
            'amount' => 50,
        ], ['Idempotency-Key' => 'phone-transfer-'.uniqid()])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Recipient wallet was not found.');
    }

    public function test_resolve_recipient_returns_wallet_data(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);

        $response = $this->withCustomerToken($sender)->getJson(
            '/api/v1/customer/wallet/resolve-recipient?identifier='.rawurlencode($recipient->phone)
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.wallet_id', $recipient->wallet->wallet_id)
            ->assertJsonPath('data.owner_name', $recipient->name);
    }

    public function test_transfer_moves_funds_and_posts_balanced_ledger(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(1000.00, 0.00);

        $beforeTransferLines = TransactionLine::query()->count();

        $response = $this->transfer($sender, $recipient->wallet->wallet_id, 250.00, 'Test transfer');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.amount', 250)
            ->assertJsonPath('data.sender_wallet.balance', 750)
            ->assertJsonPath('data.recipient.wallet_id', $recipient->wallet->wallet_id);

        $sender->wallet->refresh();
        $recipient->wallet->refresh();

        $this->assertSame(750.0, (float) $sender->wallet->balance);
        $this->assertSame(250.0, (float) $recipient->wallet->balance);

        $this->assertSame(2, WalletTransaction::query()->where('type', 'transfer')->count());

        $newLines = TransactionLine::query()
            ->where('reference', LedgerService::REF_WALLET_TRANSFER)
            ->where('reference_id', $sender->id)
            ->get();

        $this->assertGreaterThanOrEqual(2, $newLines->count());
        $this->assertSame(
            round((float) $newLines->sum('debit'), 2),
            round((float) $newLines->sum('credit'), 2)
        );
        $this->assertGreaterThan($beforeTransferLines, TransactionLine::query()->count());
        $this->assertTrue(app(AccountBalanceService::class)->isSystemBalanced());
    }

    public function test_transfer_with_fee_credits_fee_income(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);

        $this->transfer($sender, $recipient->wallet->wallet_id, 50.00, 'Fee test', null, 2.00)
            ->assertOk()
            ->assertJsonPath('data.recipient_amount', 48)
            ->assertJsonPath('data.sender_wallet.balance', 450);

        $recipient->wallet->refresh();
        $this->assertSame(48.0, (float) $recipient->wallet->balance);
        $this->assertTrue(app(AccountBalanceService::class)->isSystemBalanced());
    }

    public function test_transfer_fails_with_insufficient_balance(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(100.00, 0.00);

        $response = $this->transfer($sender, $recipient->wallet->wallet_id, 150.00);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Insufficient wallet balance for this operation.');

        $sender->wallet->refresh();
        $recipient->wallet->refresh();

        $this->assertSame(100.0, (float) $sender->wallet->balance);
        $this->assertSame(0.0, (float) $recipient->wallet->balance);
        $this->assertSame(0, WalletTransaction::query()->where('type', 'transfer')->count());
    }

    public function test_suspended_sender_cannot_transfer(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);
        $sender->update(['status' => Customer::STATUS_SUSPENDED]);

        $this->transfer($sender, $recipient->wallet->wallet_id, 100.00)
            ->assertStatus(403)
            ->assertJsonPath('message', 'Your account has been suspended. Please contact support.');
    }

    public function test_suspended_recipient_cannot_receive_transfer(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);
        $recipient->update(['status' => Customer::STATUS_SUSPENDED]);

        $this->transfer($sender, $recipient->wallet->wallet_id, 100.00)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Recipient account is suspended and cannot receive money.');
    }

    public function test_wallet_query_rejects_own_phone(): void
    {
        [$sender] = $this->createFundedCustomers(500.00, 0.00);

        $this->withCustomerToken($sender)->getJson(
            '/api/v1/customer/wallet/query?identifier='.rawurlencode($sender->phone)
        )->assertStatus(422)
            ->assertJsonPath('message', 'You cannot use your own wallet or phone number as the recipient.');
    }

    public function test_wallet_query_rejects_own_wallet_id(): void
    {
        [$sender] = $this->createFundedCustomers(500.00, 0.00);

        $this->withCustomerToken($sender)->getJson(
            '/api/v1/customer/wallet/query?identifier='.rawurlencode($sender->wallet->wallet_id)
        )->assertStatus(422)
            ->assertJsonPath('message', 'You cannot use your own wallet or phone number as the recipient.');
    }

    public function test_resolve_recipient_rejects_own_phone(): void
    {
        [$sender] = $this->createFundedCustomers(500.00, 0.00);

        $this->withCustomerToken($sender)->getJson(
            '/api/v1/customer/wallet/resolve-recipient?identifier='.rawurlencode($sender->phone)
        )->assertStatus(422)
            ->assertJsonPath('message', 'You cannot use your own wallet or phone number as the recipient.');
    }

    public function test_self_transfer_is_rejected(): void
    {
        [$sender] = $this->createFundedCustomers(500.00, 0.00);

        $this->transfer($sender, $sender->wallet->wallet_id, 50.00)
            ->assertStatus(422)
            ->assertJsonPath('message', 'You cannot use your own wallet or phone number as the recipient.');
    }

    public function test_transfer_fails_for_unknown_wallet_id(): void
    {
        [$sender] = $this->createFundedCustomers(500.00, 0.00);

        $this->transfer($sender, '249900000999@fastpay', 50.00)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Recipient wallet was not found.');
    }

    public function test_resolve_recipient_fails_for_unknown_identifier(): void
    {
        [$sender] = $this->createFundedCustomers(500.00, 0.00);

        $this->withCustomerToken($sender)->getJson(
            '/api/v1/customer/wallet/resolve-recipient?identifier=+249900000999'
        )->assertStatus(422)
            ->assertJsonPath('message', 'Recipient wallet was not found.');
    }

    public function test_idempotency_key_prevents_double_spend(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);
        $idempotencyKey = 'transfer-test-'.uniqid();

        $first = $this->transfer(
            $sender,
            $recipient->wallet->wallet_id,
            120.00,
            'Idempotent transfer',
            $idempotencyKey
        );

        $second = $this->transfer(
            $sender,
            $recipient->wallet->wallet_id,
            120.00,
            'Idempotent transfer',
            $idempotencyKey
        );

        $first->assertOk();
        $second->assertOk()
            ->assertJsonPath('data.sender_wallet.balance', 380);

        $this->assertSame($first->json('data'), $second->json('data'));

        $sender->wallet->refresh();
        $recipient->wallet->refresh();

        $this->assertSame(380.0, (float) $sender->wallet->balance);
        $this->assertSame(120.0, (float) $recipient->wallet->balance);
        $this->assertSame(2, WalletTransaction::query()->where('type', 'transfer')->count());
    }

    public function test_customer_can_withdraw_balance(): void
    {
        [$sender] = $this->createFundedCustomers(200.00, 0.00);

        $response = $this->withCustomerToken($sender)->postJson('/api/v1/customer/wallet/withdraw', [
            'amount' => 80,
            'description' => 'Cash out',
        ], ['Idempotency-Key' => 'withdraw-'.uniqid()]);

        $response->assertOk()
            ->assertJsonPath('data.amount', 80)
            ->assertJsonPath('data.wallet.balance', 120);

        $this->assertTrue(app(AccountBalanceService::class)->isSystemBalanced());
    }

    public function test_transactions_returns_paginated_list_newest_first(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(1000.00, 0.00);

        foreach ([10, 20, 30, 40, 50] as $amount) {
            $this->transfer($sender, $recipient->wallet->wallet_id, (float) $amount, "Transfer {$amount}")
                ->assertOk();
        }

        $response = $this->withCustomerToken($sender)->getJson('/api/v1/customer/wallet/transactions?per_page=3');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.per_page', 3)
            ->assertJsonPath('data.current_page', 1)
            ->assertJsonPath('data.total', 6)
            ->assertJsonPath('data.last_page', 2)
            ->assertJsonCount(3, 'data.data')
            ->assertJsonPath('data.data.0.amount', 50)
            ->assertJsonPath('data.data.0.direction', 'debit')
            ->assertJsonPath('data.data.0.type', 'transfer');
    }

    public function test_transactions_page_two_returns_next_slice(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(1000.00, 0.00);

        foreach ([10, 20, 30, 40, 50] as $amount) {
            $this->transfer($sender, $recipient->wallet->wallet_id, (float) $amount, "Transfer {$amount}")
                ->assertOk();
        }

        $response = $this->withCustomerToken($sender)->getJson('/api/v1/customer/wallet/transactions?per_page=3&page=2');

        $response->assertOk()
            ->assertJsonPath('data.current_page', 2)
            ->assertJsonCount(3, 'data.data')
            ->assertJsonPath('data.data.0.amount', 20);
    }

    public function test_transactions_search_filters_by_description_and_note(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);

        $this->transfer($sender, $recipient->wallet->wallet_id, 25.00, 'Lunch payment', null, null, 'Pizza note')
            ->assertOk();
        $this->transfer($sender, $recipient->wallet->wallet_id, 15.00, 'Coffee run')
            ->assertOk();

        $byDescription = $this->withCustomerToken($sender)->getJson(
            '/api/v1/customer/wallet/transactions?search='.rawurlencode('Lunch')
        );
        $byDescription->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.description', 'Lunch payment');

        $byNote = $this->withCustomerToken($sender)->getJson(
            '/api/v1/customer/wallet/transactions?search='.rawurlencode('Pizza')
        );
        $byNote->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.note', 'Pizza note');
    }

    public function test_transactions_filters_by_type_and_direction(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);

        $this->transfer($sender, $recipient->wallet->wallet_id, 50.00, 'Filtered transfer')
            ->assertOk();

        $debitTransfers = $this->withCustomerToken($sender)->getJson(
            '/api/v1/customer/wallet/transactions?type=transfer&direction=debit'
        );
        $debitTransfers->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.type', 'transfer')
            ->assertJsonPath('data.data.0.direction', 'debit');

        $credits = $this->withCustomerToken($sender)->getJson(
            '/api/v1/customer/wallet/transactions?direction=credit'
        );
        $credits->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.direction', 'credit');
    }

    public function test_transactions_filters_by_date_range(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);

        $this->transfer($sender, $recipient->wallet->wallet_id, 30.00, 'In range')
            ->assertOk();

        $oldTransaction = WalletTransaction::query()
            ->where('wallet_id', $sender->wallet->id)
            ->where('direction', 'debit')
            ->latest('id')
            ->first();

        $oldTransaction->forceFill(['created_at' => now()->subDays(10)])->save();

        $this->transfer($sender, $recipient->wallet->wallet_id, 20.00, 'Recent only')
            ->assertOk();

        $today = now()->toDateString();
        $response = $this->withCustomerToken($sender)->getJson(
            '/api/v1/customer/wallet/transactions?date_from='.$today.'&date_to='.$today
        );

        $response->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonMissing(['description' => 'In range']);
    }

    public function test_transactions_only_returns_authenticated_customer_wallet(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 100.00);

        $this->transfer($sender, $recipient->wallet->wallet_id, 40.00, 'Sender debit')
            ->assertOk();

        $senderResponse = $this->withCustomerToken($sender)->getJson('/api/v1/customer/wallet/transactions');
        $recipientResponse = $this->withCustomerToken($recipient)->getJson('/api/v1/customer/wallet/transactions');

        $senderResponse->assertOk()
            ->assertJsonPath('data.data.0.direction', 'debit')
            ->assertJsonPath('data.data.0.description', 'Sender debit');

        $recipientResponse->assertOk()
            ->assertJsonPath('data.data.0.direction', 'credit')
            ->assertJsonPath('data.data.0.description', 'Sender debit');

        $senderIds = collect($senderResponse->json('data.data'))->pluck('id')->all();
        $recipientIds = collect($recipientResponse->json('data.data'))->pluck('id')->all();

        $this->assertEmpty(array_intersect($senderIds, $recipientIds));
    }

    public function test_transactions_validation_rejects_invalid_filters(): void
    {
        [$sender] = $this->createFundedCustomers(100.00, 0.00);

        $this->withCustomerToken($sender)->getJson('/api/v1/customer/wallet/transactions?type=invalid')
            ->assertStatus(422);

        $this->withCustomerToken($sender)->getJson('/api/v1/customer/wallet/transactions?direction=side')
            ->assertStatus(422);

        $this->withCustomerToken($sender)->getJson('/api/v1/customer/wallet/transactions?per_page=101')
            ->assertStatus(422);
    }

    /**
     * @return array{0: Customer, 1?: Customer}
     */
    private function createFundedCustomers(float $senderBalance, float $recipientBalance): array
    {
        $walletService = app(WalletService::class);

        $sender = Customer::factory()->active()->create();
        $recipient = Customer::factory()->active()->create();

        $sender->load('wallet');
        $recipient->load('wallet');

        $senderWallet = $walletService->walletForCustomer($sender) ?? $walletService->createForCustomer($sender);
        $recipientWallet = $walletService->walletForCustomer($recipient) ?? $walletService->createForCustomer($recipient);

        if ($senderBalance > 0) {
            $walletService->cashIn($senderWallet, $senderBalance, 'Test funding');
        }

        if ($recipientBalance > 0) {
            $walletService->cashIn($recipientWallet, $recipientBalance, 'Test funding');
        }

        $sender->setRelation('wallet', $senderWallet->fresh());
        $recipient->setRelation('wallet', $recipientWallet->fresh());

        return [$sender, $recipient];
    }

    private function transfer(
        Customer $sender,
        string $recipientWalletId,
        float $amount,
        ?string $description = null,
        ?string $idempotencyKey = null,
        ?float $fee = null,
        ?string $note = null
    ): TestResponse {
        $headers = [];
        if ($idempotencyKey !== null) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        return $this->withCustomerToken($sender)
            ->withHeaders($headers)
            ->postJson('/api/v1/customer/wallet/transfer', array_filter([
                'recipient_wallet_id' => $recipientWalletId,
                'amount' => $amount,
                'fee' => $fee,
                'description' => $description,
                'note' => $note,
            ], fn ($value) => $value !== null));
    }

    private function withCustomerToken(Customer $customer): static
    {
        $auth = app(CustomerJwtService::class)->createToken(
            $customer->id,
            $customer->email ?: $customer->phone,
        );

        return $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ]);
    }
}
