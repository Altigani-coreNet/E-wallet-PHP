<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\TransactionLine;
use App\Models\Wallet;
use App\Modules\Accounting\Services\AccountBalanceService;
use App\Services\AccountingService;
use App\Services\LedgerService;
use App\Services\WalletService;
use App\Support\AccountCode;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    private WalletService $walletService;

    private Wallet $master;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountSeeder::class);
        $this->walletService = app(WalletService::class);
        $this->master = $this->walletService->createMasterWallet();
    }

    public function test_master_fund_posts_bank_to_master_liability(): void
    {
        $this->walletService->cashIn($this->master, 1000, 'Fund master float');

        $this->master->refresh();
        $this->assertSame(1000.0, (float) $this->master->balance);

        $this->assertSame(1000.0, $this->netByCode(AccountCode::BANK));
        $this->assertSame(-1000.0, $this->netByCode(AccountCode::MASTER_LIABILITY));
        $this->assertTrue(app(AccountBalanceService::class)->isSystemBalanced());
    }

    public function test_master_defund_posts_master_to_bank(): void
    {
        $this->walletService->cashIn($this->master, 1000, 'Fund master float');

        $this->walletService->cashOut($this->master, 300, 'Withdraw float');

        $this->master->refresh();
        $this->assertSame(700.0, (float) $this->master->balance);
        $this->assertTrue(app(AccountBalanceService::class)->isSystemBalanced());
    }

    public function test_master_defund_rejects_insufficient_float(): void
    {
        $this->walletService->cashIn($this->master, 100, 'Fund master float');

        $this->expectException(InvalidArgumentException::class);

        $this->walletService->cashOut($this->master, 300, 'Withdraw float');
    }

    public function test_customer_cash_in_issues_from_master_float(): void
    {
        $this->fundMaster(200);
        $wallet = $this->walletService->createForCustomer(Customer::factory()->active()->create());

        $this->walletService->cashIn($wallet, 200, 'Buy balance');

        $wallet->refresh();
        $this->master->refresh();

        $this->assertSame(200.0, (float) $wallet->balance);
        $this->assertSame(0.0, (float) $this->master->balance);

        // Customer liability credited by the issue.
        $this->assertSame(-200.0, $this->netByCode(AccountCode::CUSTOMER_LIABILITY));
        $this->assertTrue(app(AccountBalanceService::class)->isSystemBalanced());
    }

    public function test_customer_cash_in_rejects_when_master_float_insufficient(): void
    {
        $this->fundMaster(100);
        $wallet = $this->walletService->createForCustomer(Customer::factory()->active()->create());

        $this->expectException(InvalidArgumentException::class);

        $this->walletService->cashIn($wallet, 200, 'Buy balance');
    }

    public function test_cash_out_redeems_balance_back_to_master(): void
    {
        $this->fundMaster(300);
        $wallet = $this->walletService->createForCustomer(Customer::factory()->active()->create());
        $this->walletService->cashIn($wallet, 300, 'Fund');

        $this->walletService->cashOut($wallet, 120, 'Withdraw');

        $wallet->refresh();
        $this->master->refresh();

        $this->assertSame(180.0, (float) $wallet->balance);
        $this->assertSame(120.0, (float) $this->master->balance);
        $this->assertTrue(app(AccountBalanceService::class)->isSystemBalanced());
    }

    public function test_transfer_with_fee_credits_fee_income(): void
    {
        [$senderWallet, $recipientWallet] = $this->createPair(500, 0);

        $this->walletService->transfer($senderWallet, $recipientWallet, 50, 'Fee transfer', 0, 2);

        $senderWallet->refresh();
        $recipientWallet->refresh();

        $this->assertSame(450.0, (float) $senderWallet->balance);
        $this->assertSame(48.0, (float) $recipientWallet->balance);

        $this->assertSame(-2.0, $this->netByCode(AccountCode::FEE_INCOME));
        $this->assertTrue(app(AccountBalanceService::class)->isSystemBalanced());
    }

    public function test_opening_capital_records_equity(): void
    {
        app(AccountingService::class)->recordOpeningCapital(1000, 'Seed capital');

        $lines = TransactionLine::query()->where('reference', LedgerService::REF_OPENING_CAPITAL)->get();
        $this->assertCount(2, $lines);
        $this->assertTrue(app(AccountBalanceService::class)->isSystemBalanced());
    }

    public function test_resolve_recipient_matches_wallet_id_phone_and_user_number(): void
    {
        $customer = Customer::factory()->active()->create(['phone' => '+249911122233']);
        $wallet = $this->walletService->createForCustomer($customer);

        $this->assertSame($wallet->id, $this->walletService->resolveRecipient($wallet->wallet_id)->id);
        $this->assertSame($wallet->id, $this->walletService->resolveRecipient($customer->phone)->id);
        $this->assertSame($wallet->id, $this->walletService->resolveRecipient($wallet->user_number)->id);
    }

    private function fundMaster(float $amount): void
    {
        $this->walletService->cashIn($this->master, $amount, 'Fund master float');
        $this->master->refresh();
    }

    private function netByCode(int $code): float
    {
        $lines = TransactionLine::query()
            ->whereHas('account', fn ($q) => $q->where('code', $code))
            ->get();

        return round((float) $lines->sum('debit') - (float) $lines->sum('credit'), 2);
    }

    /**
     * @return array{0: Wallet, 1: Wallet}
     */
    private function createPair(float $senderBalance, float $recipientBalance): array
    {
        $sender = Customer::factory()->active()->create();
        $recipient = Customer::factory()->active()->create();

        $senderWallet = $this->walletService->createForCustomer($sender);
        $recipientWallet = $this->walletService->createForCustomer($recipient);

        $this->fundMaster($senderBalance + $recipientBalance);

        if ($senderBalance > 0) {
            $this->walletService->cashIn($senderWallet, $senderBalance, 'Fund sender');
        }

        if ($recipientBalance > 0) {
            $this->walletService->cashIn($recipientWallet, $recipientBalance, 'Fund recipient');
        }

        return [$senderWallet->fresh(), $recipientWallet->fresh()];
    }
}
