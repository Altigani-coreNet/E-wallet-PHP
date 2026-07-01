<?php

namespace Tests\Unit;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\TransactionLine;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Modules\Accounting\Services\AccountBalanceService;
use App\Services\LedgerService;
use App\Services\PartnerPayableAccountService;
use App\Services\WalletService;
use App\Support\AccountCode;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\Support\AdminWalletTestHelpers;
use Tests\TestCase;

class WalletServiceBillPayTest extends TestCase
{
    use AdminWalletTestHelpers;
    use RefreshDatabase;

    private WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountSeeder::class);
        $this->walletService = app(WalletService::class);
        $this->walletService->createMasterWallet();
    }

    public function test_bill_pay_posts_balanced_ledger_and_debits_wallet(): void
    {
        $wallet = $this->createFundedWallet(Customer::factory()->active()->create(), 200);
        $payable = $this->createPartnerPayable('Electricity Co');

        $this->walletService->billPay($wallet, $payable, 100, 0, 'Electricity bill', 'bill-001');

        $wallet->refresh();
        $this->assertSame(100.0, (float) $wallet->balance);

        $lines = TransactionLine::query()->where('reference', LedgerService::REF_BILL_PAYMENT)->get();
        $this->assertSame(100.0, round((float) $lines->sum('debit'), 2));
        $this->assertSame(100.0, round((float) $lines->sum('credit'), 2));

        $transaction = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->where('type', 'bill_payment')
            ->first();

        $this->assertNotNull($transaction);
        $this->assertSame('debit', $transaction->direction);
        $this->assertSame(100.0, (float) $transaction->amount);
        $this->assertTrue(app(AccountBalanceService::class)->isSystemBalanced());
    }

    public function test_bill_pay_with_fee_credits_fee_income(): void
    {
        config(['services.wallet.bill_payment_fee' => 2]);

        $wallet = $this->createFundedWallet(Customer::factory()->active()->create(), 200);
        $payable = $this->createPartnerPayable('Telecom Co');
        $fee = $this->walletService->billPaymentFee();

        $this->walletService->billPay($wallet, $payable, 100, $fee, 'Bill with fee', 'bill-002');

        $wallet->refresh();
        $this->assertSame(98.0, (float) $wallet->balance);
        $this->assertSame(-2.0, $this->netByCode(AccountCode::FEE_INCOME));
        $this->assertSame(-100.0, $this->netByAccountId($payable->id));
        $this->assertTrue(app(AccountBalanceService::class)->isSystemBalanced());
    }

    public function test_bill_pay_rejects_insufficient_balance(): void
    {
        $wallet = $this->createFundedWallet(Customer::factory()->active()->create(), 50);
        $payable = $this->createPartnerPayable('Water Co');

        $this->expectException(InvalidArgumentException::class);

        $this->walletService->billPay($wallet, $payable, 100);
    }

    private function createPartnerPayable(string $name): ChartOfAccount
    {
        $partner = Partner::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'email' => Str::lower(Str::random(8)).'@example.com',
            'merchant_code' => 'MRC_'.Str::random(8),
            'is_active' => true,
            'status' => 'approved',
        ]);

        return app(PartnerPayableAccountService::class)->allocateForPartner($partner);
    }

    private function netByCode(int $code): float
    {
        $lines = TransactionLine::query()
            ->whereHas('account', fn ($q) => $q->where('code', $code))
            ->get();

        return round((float) $lines->sum('debit') - (float) $lines->sum('credit'), 2);
    }

    private function netByAccountId(int $accountId): float
    {
        $lines = TransactionLine::query()->where('account_id', $accountId)->get();

        return round((float) $lines->sum('debit') - (float) $lines->sum('credit'), 2);
    }
}
