<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\TransactionLine;
use App\Models\Wallet;
use App\Models\WalletTransaction;
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
    }

    public function test_wallet_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/customer/wallet/dashboard')->assertUnauthorized();
        $this->postJson('/api/v1/customer/wallet/transfer/by-wallet-id')->assertUnauthorized();
        $this->postJson('/api/v1/customer/wallet/transfer/by-phone')->assertUnauthorized();
    }

    public function test_dashboard_returns_wallet_balance_and_last_transaction(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);

        $this->transferByWalletId($sender, $recipient->wallet->wallet_id, 150.00, 'Lunch')
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
            ->assertJsonPath('data.last_transaction.balance_after', 350);
    }

    public function test_transfer_by_wallet_id_moves_funds_and_posts_balanced_ledger(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(1000.00, 0.00);

        $beforeTransferLines = TransactionLine::query()->count();

        $response = $this->transferByWalletId($sender, $recipient->wallet->wallet_id, 250.00, 'Test transfer');

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

        $this->assertCount(2, $newLines);
        $this->assertSame(
            round((float) $newLines->sum('debit'), 2),
            round((float) $newLines->sum('credit'), 2)
        );
        $this->assertGreaterThan($beforeTransferLines, TransactionLine::query()->count());
    }

    public function test_transfer_by_phone_resolves_recipient_wallet(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(800.00, 0.00);

        $response = $this->transferByPhone($sender, $recipient->phone, 200.00, 'Phone transfer');

        $response->assertOk()
            ->assertJsonPath('data.amount', 200)
            ->assertJsonPath('data.recipient.name', $recipient->name)
            ->assertJsonPath('data.sender_wallet.balance', 600);

        $recipient->wallet->refresh();
        $this->assertSame(200.0, (float) $recipient->wallet->balance);
    }

    public function test_transfer_fails_with_insufficient_balance(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(100.00, 0.00);

        $response = $this->transferByWalletId($sender, $recipient->wallet->wallet_id, 150.00);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Insufficient wallet balance for this transfer.');

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

        $this->transferByWalletId($sender, $recipient->wallet->wallet_id, 100.00)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Your account has been suspended. Please contact support.');
    }

    public function test_suspended_recipient_cannot_receive_transfer(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);
        $recipient->update(['status' => Customer::STATUS_SUSPENDED]);

        $this->transferByWalletId($sender, $recipient->wallet->wallet_id, 100.00)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Recipient account is suspended and cannot receive money.');
    }

    public function test_self_transfer_is_rejected(): void
    {
        [$sender] = $this->createFundedCustomers(500.00, 0.00);

        $this->transferByWalletId($sender, $sender->wallet->wallet_id, 50.00)
            ->assertStatus(422)
            ->assertJsonPath('message', 'You cannot transfer money to your own wallet.');
    }

    public function test_transfer_fails_for_unknown_wallet_id(): void
    {
        [$sender] = $this->createFundedCustomers(500.00, 0.00);

        $this->transferByWalletId($sender, 'WAL-DOES-NOT-EXIST', 50.00)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Recipient wallet was not found.');
    }

    public function test_transfer_fails_for_unknown_phone(): void
    {
        [$sender] = $this->createFundedCustomers(500.00, 0.00);

        $this->transferByPhone($sender, '+249900000999', 50.00)
            ->assertStatus(422)
            ->assertJsonPath('message', 'No customer was found with this phone number.');
    }

    public function test_idempotency_key_prevents_double_spend(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);
        $idempotencyKey = 'transfer-test-'.uniqid();

        $first = $this->transferByWalletId(
            $sender,
            $recipient->wallet->wallet_id,
            120.00,
            'Idempotent transfer',
            $idempotencyKey
        );

        $second = $this->transferByWalletId(
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
            $walletService->topUpFromBank($senderWallet, $senderBalance, 'Test funding');
        }

        if ($recipientBalance > 0) {
            $walletService->topUpFromBank($recipientWallet, $recipientBalance, 'Test funding');
        }

        $sender->setRelation('wallet', $senderWallet->fresh());
        $recipient->setRelation('wallet', $recipientWallet->fresh());

        return [$sender, $recipient];
    }

    private function transferByWalletId(
        Customer $sender,
        string $recipientWalletId,
        float $amount,
        ?string $description = null,
        ?string $idempotencyKey = null
    ): TestResponse {
        $headers = [];
        if ($idempotencyKey !== null) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        return $this->withCustomerToken($sender)
            ->withHeaders($headers)
            ->postJson('/api/v1/customer/wallet/transfer/by-wallet-id', array_filter([
                'recipient_wallet_id' => $recipientWalletId,
                'amount' => $amount,
                'description' => $description,
            ]));
    }

    private function transferByPhone(
        Customer $sender,
        string $recipientPhone,
        float $amount,
        ?string $description = null,
        ?string $idempotencyKey = null
    ): TestResponse {
        $headers = [];
        if ($idempotencyKey !== null) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        return $this->withCustomerToken($sender)
            ->withHeaders($headers)
            ->postJson('/api/v1/customer/wallet/transfer/by-phone', array_filter([
                'recipient_phone' => $recipientPhone,
                'amount' => $amount,
                'description' => $description,
            ]));
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
