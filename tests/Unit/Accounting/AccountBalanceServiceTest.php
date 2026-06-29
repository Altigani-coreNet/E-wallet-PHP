<?php

namespace Tests\Unit\Accounting;

use App\Models\ChartOfAccount;
use App\Models\TransactionLine;
use App\Modules\Accounting\Services\AccountBalanceService;
use App\Services\WalletService;
use Tests\CustomerAuthTestCase;
use Tests\Support\AccountingTestHelpers;

class AccountBalanceServiceTest extends CustomerAuthTestCase
{
    use AccountingTestHelpers;

    private AccountBalanceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedChartOfAccounts();
        $this->service = app(AccountBalanceService::class);
    }

    public function test_is_debit_normal_classifies_account_types(): void
    {
        $this->assertTrue($this->service->isDebitNormal('Assets'));
        $this->assertTrue($this->service->isDebitNormal('Expenses'));
        $this->assertTrue($this->service->isDebitNormal('Costs of Goods Sold'));
        $this->assertFalse($this->service->isDebitNormal('Liabilities'));
        $this->assertFalse($this->service->isDebitNormal('Equity'));
        $this->assertFalse($this->service->isDebitNormal('Income'));
    }

    public function test_signed_balance_uses_debit_normal_and_credit_normal_rules(): void
    {
        $this->assertSame(70.0, $this->service->signedBalance(100, 30, 'Assets'));
        $this->assertSame(70.0, $this->service->signedBalance(30, 100, 'Income'));
    }

    public function test_raw_sums_supports_period_opening_and_cumulative_modes(): void
    {
        $account = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);

        $this->postLine($account->id, 50, 0, '2026-01-10');
        $this->postLine($account->id, 20, 0, '2026-02-15');
        $this->postLine($account->id, 10, 0, '2026-03-01');

        $period = $this->service->rawSums($account->id, '2026-02-01', '2026-02-28');
        $this->assertSame(20.0, $period['debit']);
        $this->assertSame(0.0, $period['credit']);

        $opening = $this->service->rawSums($account->id, '2026-02-01', null, AccountBalanceService::MODE_OPENING);
        $this->assertSame(50.0, $opening['debit']);

        $cumulative = $this->service->rawSums($account->id, null, '2026-02-28', AccountBalanceService::MODE_CUMULATIVE);
        $this->assertSame(70.0, $cumulative['debit']);
    }

    public function test_balance_opening_and_closing_for_asset_account(): void
    {
        $account = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);

        $this->postLine($account->id, 100, 0, '2026-01-05');
        $this->postLine($account->id, 40, 0, '2026-02-10');

        $this->assertSame(100.0, $this->service->openingBalance($account->id, '2026-02-01', 'Assets'));
        $this->assertSame(40.0, $this->service->balance(
            $account->id,
            '2026-02-01',
            '2026-02-28',
            AccountBalanceService::MODE_PERIOD,
            'Assets'
        ));
        $this->assertSame(140.0, $this->service->closingBalance($account->id, '2026-02-28', 'Assets'));
    }

    public function test_balance_for_liability_account_uses_credit_minus_debit(): void
    {
        $account = $this->accountByCode(WalletService::WALLET_LIABILITY_ACCOUNT_CODE);
        $this->postLine($account->id, 0, 250, now()->toDateString());

        $this->assertSame(
            250.0,
            $this->service->balance($account->id, date('Y-01-01'), date('Y-m-d'), AccountBalanceService::MODE_PERIOD, 'Liabilities')
        );
    }

    public function test_totals_by_type_name_aggregates_cumulative_balances(): void
    {
        $this->postBalancedWalletTopUp(500);

        $totals = $this->service->totalsByTypeName(date('Y-m-d'));

        $this->assertSame(500.0, $totals['Assets']);
        $this->assertSame(500.0, $totals['Liabilities']);
    }

    public function test_totals_by_type_name_for_period_only_includes_selected_dates(): void
    {
        $income = $this->accountByCode(4000);
        $expense = $this->accountByCode(5000);

        $this->postLine($income->id, 0, 1000, '2026-01-15');
        $this->postLine($expense->id, 200, 0, '2026-01-20');
        $this->postLine($income->id, 0, 500, '2026-03-01');

        $totals = $this->service->totalsByTypeNameForPeriod('2026-01-01', '2026-01-31');

        $this->assertSame(1000.0, $totals['Income']);
        $this->assertSame(200.0, $totals['Expenses']);
    }

    public function test_is_system_balanced_returns_true_for_balanced_entries(): void
    {
        $this->postBalancedWalletTopUp(300);

        $this->assertTrue($this->service->isSystemBalanced(date('Y-m-d')));
    }

    public function test_is_system_balanced_returns_false_when_only_assets_are_posted(): void
    {
        $bank = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);
        $this->postLine($bank->id, 100, 0);

        $this->assertFalse($this->service->isSystemBalanced(date('Y-m-d')));
    }

    public function test_balance_resolves_type_name_from_account_when_not_provided(): void
    {
        $account = ChartOfAccount::query()->byCode(4000)->firstOrFail();
        $this->postLine($account->id, 0, 75);

        $this->assertSame(75.0, $this->service->balance($account->id, date('Y-01-01'), date('Y-m-d')));
    }

    public function test_raw_sums_returns_zero_when_account_has_no_lines(): void
    {
        $account = $this->accountByCode(5010);

        $sums = $this->service->rawSums($account->id, date('Y-01-01'), date('Y-m-d'));

        $this->assertSame(0.0, $sums['debit']);
        $this->assertSame(0.0, $sums['credit']);
    }
}
