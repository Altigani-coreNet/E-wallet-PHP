<?php

namespace Tests\Unit;

use App\Models\Partner;
use App\Models\TransactionLine;
use App\Modules\Accounting\Services\AccountBalanceService;
use App\Services\AccountingService;
use App\Services\LedgerService;
use App\Services\PartnerPayableAccountService;
use App\Services\ProviderSettlementService;
use App\Services\WalletService;
use App\Support\AccountCode;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\Support\AdminWalletTestHelpers;
use Tests\TestCase;

class ProviderSettlementServiceTest extends TestCase
{
    use AdminWalletTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountSeeder::class);
        app(AccountingService::class)->recordOpeningCapital(1000, 'Opening equity');
        app(WalletService::class)->createMasterWallet();
    }

    public function test_settle_posts_dr_payable_cr_bank(): void
    {
        $partner = $this->createPartnerWithPayable();
        $payable = $partner->chartOfAccount;

        $this->fundPayableViaBillAccrual($partner, 100);

        $bankAccount = \App\Models\ChartOfAccount::query()->byCode(AccountCode::BANK)->firstOrFail();
        $bankBefore = app(AccountBalanceService::class)->balance((int) $bankAccount->id);

        app(ProviderSettlementService::class)->settle($partner, 100, 'Settle Electricity Co');

        $this->assertSame(0.0, app(AccountBalanceService::class)->balance((int) $payable->id));
        $this->assertSame(100.0, round($bankBefore - app(AccountBalanceService::class)->balance((int) $bankAccount->id), 2));
        $this->assertTrue(app(AccountBalanceService::class)->isSystemBalanced());

        $lines = TransactionLine::query()->where('reference', LedgerService::REF_PROVIDER_SETTLEMENT)->get();
        $this->assertSame(100.0, round((float) $lines->sum('debit'), 2));
        $this->assertSame(100.0, round((float) $lines->sum('credit'), 2));
    }

    public function test_partial_settlement_leaves_remaining_payable(): void
    {
        $partner = $this->createPartnerWithPayable();
        $payable = $partner->chartOfAccount;

        $this->fundPayableViaBillAccrual($partner, 100);

        app(ProviderSettlementService::class)->settle($partner, 60);

        $this->assertSame(40.0, app(AccountBalanceService::class)->balance((int) $payable->id));
    }

    public function test_settlement_exceeding_payable_is_rejected(): void
    {
        $partner = $this->createPartnerWithPayable();
        $this->fundPayableViaBillAccrual($partner, 40);

        $this->expectException(InvalidArgumentException::class);

        app(ProviderSettlementService::class)->settle($partner, 100);
    }

    private function createPartnerWithPayable(): Partner
    {
        $partner = Partner::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Settle Partner',
            'email' => Str::lower(Str::random(8)).'@example.com',
            'merchant_code' => 'MRC_'.Str::random(8),
            'is_active' => true,
            'status' => 'approved',
        ]);

        app(PartnerPayableAccountService::class)->allocateForPartner($partner);

        return $partner->fresh(['chartOfAccount']);
    }

    private function fundPayableViaBillAccrual(Partner $partner, float $amount): void
    {
        $wallet = $this->createFundedWallet(
            \App\Models\Customer::factory()->active()->create(),
            $amount
        );

        app(WalletService::class)->billPay(
            $wallet,
            $partner->chartOfAccount,
            $amount,
            0,
            'Seed payable',
            'seed-'.$partner->id
        );
    }

    private function netByCode(int $code): float
    {
        $lines = TransactionLine::query()
            ->whereHas('account', fn ($q) => $q->where('code', $code))
            ->get();

        return round((float) $lines->sum('credit') - (float) $lines->sum('debit'), 2);
    }
}
