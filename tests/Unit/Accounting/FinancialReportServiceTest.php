<?php

namespace Tests\Unit\Accounting;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Modules\Accounting\Services\FinancialReportService;
use App\Services\LedgerService;
use App\Services\WalletService;
use Tests\CustomerAuthTestCase;
use Tests\Support\AccountingTestHelpers;

class FinancialReportServiceTest extends CustomerAuthTestCase
{
    use AccountingTestHelpers;

    private FinancialReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedChartOfAccounts();
        $this->service = app(FinancialReportService::class);
    }

    public function test_ledger_returns_rows_with_running_balance_for_single_account(): void
    {
        $bank = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);

        $this->postLine($bank->id, 100, 0, '2026-01-05');
        $this->postLine($bank->id, 50, 0, '2026-02-10');
        $this->postLine($bank->id, 0, 20, '2026-02-20');

        $report = $this->service->ledger($bank->id, null, '2026-02-01', '2026-02-28');

        $this->assertSame(150.0, $report['rows'][0]['balance']);
        $this->assertSame(130.0, $report['rows'][1]['balance']);
        $this->assertSame(50.0, $report['totals']['debit']);
        $this->assertSame(20.0, $report['totals']['credit']);
        $this->assertCount(2, $report['rows']);
        $this->assertSame($bank->id, $report['rows'][0]['account_id']);
    }

    public function test_ledger_all_accounts_returns_rows_from_multiple_accounts(): void
    {
        $bank = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);
        $liability = $this->accountByCode(WalletService::WALLET_LIABILITY_ACCOUNT_CODE);

        $this->postLine($bank->id, 150, 0, '2026-02-01', LedgerService::REF_WALLET_TOPUP, '1', 0);
        $this->postLine($liability->id, 0, 150, '2026-02-01', LedgerService::REF_WALLET_TOPUP, 1, 1);

        $report = $this->service->ledger(null, null, '2026-02-01', '2026-02-28');

        $this->assertCount(2, $report['rows']);
        $this->assertSame(150.0, $report['totals']['debit']);
        $this->assertSame(150.0, $report['totals']['credit']);
    }

    public function test_ledger_filters_by_customer_reference_id(): void
    {
        $customerA = Customer::factory()->active()->create(['name' => 'Customer A']);
        $customerB = Customer::factory()->active()->create(['name' => 'Customer B']);
        $bank = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);

        $this->postLine($bank->id, 100, 0, '2026-02-01', LedgerService::REF_WALLET_TOPUP, $customerA->id);
        $this->postLine($bank->id, 200, 0, '2026-02-02', LedgerService::REF_WALLET_TOPUP, $customerB->id);

        $report = $this->service->ledger(null, $customerA->id, '2026-02-01', '2026-02-28');

        $this->assertCount(1, $report['rows']);
        $this->assertSame('Customer A', $report['rows'][0]['name']);
        $this->assertSame(100.0, $report['totals']['debit']);
    }

    public function test_ledger_uses_credit_normal_running_balance_for_liability_account(): void
    {
        $liability = $this->accountByCode(WalletService::WALLET_LIABILITY_ACCOUNT_CODE);
        $this->postLine($liability->id, 0, 80, '2026-02-01');

        $report = $this->service->ledger($liability->id, null, '2026-02-01', '2026-02-28');

        $this->assertSame(80.0, $report['rows'][0]['balance']);
    }

    public function test_ledger_transaction_type_labels_wallet_references(): void
    {
        $bank = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);
        $this->postLine($bank->id, 25, 0, '2026-02-01', LedgerService::REF_WALLET_TOPUP, '1');
        $this->postLine($bank->id, 10, 0, '2026-02-02', LedgerService::REF_WALLET_TRANSFER, '2');

        $report = $this->service->ledger($bank->id, null, '2026-02-01', '2026-02-28');

        $this->assertSame('Wallet Top-up', $report['rows'][0]['transaction_type']);
        $this->assertSame('Wallet Transfer', $report['rows'][1]['transaction_type']);
    }

    public function test_balance_sheet_returns_balanced_totals_and_nested_sections(): void
    {
        $this->postBalancedWalletTopUp(500, '2026-02-01');

        $report = $this->service->balanceSheet('2026-02-01', '2026-02-28');

        $this->assertTrue($report['is_balanced']);
        $this->assertSame(500.0, $report['totals']['total_assets']);
        $this->assertSame(500.0, $report['totals']['total_liabilities']);
        $this->assertSame(500.0, $report['totals']['total_liabilities_and_equity']);
        $this->assertNotEmpty($report['sections']['assets']['sub_types']);
        $this->assertNotEmpty($report['sections']['liabilities']['sub_types']);
        $this->assertArrayHasKey('filter', $report);
        $this->assertSame('2026-02-01', $report['filter']['start_date']);
    }

    public function test_balance_sheet_includes_net_income_in_equity_total(): void
    {
        $income = $this->accountByCode(4000);
        $expense = $this->accountByCode(5000);

        $this->postLine($income->id, 0, 1000, '2026-02-01');
        $this->postLine($expense->id, 250, 0, '2026-02-05');

        $report = $this->service->balanceSheet('2026-02-01', '2026-02-28');

        $this->assertSame(750.0, $report['sections']['equity']['net_income']);
        $this->assertSame(750.0, $report['totals']['total_equity']);
        $this->assertSame(0.0, $report['totals']['total_assets']);
        $this->assertFalse($report['is_balanced']);
    }

    public function test_balance_sheet_excludes_transactions_outside_period(): void
    {
        $bank = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);
        $this->postLine($bank->id, 900, 0, '2026-01-01');
        $this->postLine($bank->id, 100, 0, '2026-02-01');

        $report = $this->service->balanceSheet('2026-02-01', '2026-02-28');

        $this->assertSame(100.0, $report['totals']['total_assets']);
    }

    public function test_profit_and_loss_calculates_gross_and_net_profit(): void
    {
        $income = $this->accountByCode(4000);
        $cogsAccount = ChartOfAccount::query()->whereHas('accountType', fn ($q) => $q->where('name', 'Costs of Goods Sold'))->first();
        $expense = $this->accountByCode(5000);

        $this->postLine($income->id, 0, 2000, '2026-02-01');
        if ($cogsAccount) {
            $this->postLine($cogsAccount->id, 500, 0, '2026-02-02');
        }
        $this->postLine($expense->id, 300, 0, '2026-02-03');

        $report = $this->service->profitAndLoss('2026-02-01', '2026-02-28');

        $this->assertSame(2000.0, $report['income']['total']);
        $this->assertSame(300.0, $report['expenses']['total']);
        if ($cogsAccount) {
            $this->assertSame(500.0, $report['costs_of_goods_sold']['total']);
            $this->assertSame(1500.0, $report['gross_profit']);
            $this->assertSame(1200.0, $report['net_profit']);
        } else {
            $this->assertSame(1700.0, $report['net_profit']);
        }
    }

    public function test_profit_and_loss_excludes_transactions_outside_period(): void
    {
        $income = $this->accountByCode(4000);
        $this->postLine($income->id, 0, 900, '2026-01-01');
        $this->postLine($income->id, 0, 100, '2026-02-01');

        $report = $this->service->profitAndLoss('2026-02-01', '2026-02-28');

        $this->assertSame(100.0, $report['income']['total']);
        $this->assertSame(100.0, $report['net_profit']);
    }

    public function test_trial_balance_lists_active_accounts_and_balances_debits_and_credits(): void
    {
        $bank = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);
        $liability = $this->accountByCode(WalletService::WALLET_LIABILITY_ACCOUNT_CODE);

        $this->postLine($bank->id, 400, 0, '2026-02-01');
        $this->postLine($liability->id, 0, 400, '2026-02-01');

        $report = $this->service->trialBalance('2026-02-01', '2026-02-28');

        $this->assertTrue($report['is_balanced']);
        $this->assertSame(400.0, $report['total_debit']);
        $this->assertSame(400.0, $report['total_credit']);
        $this->assertCount(2, $report['rows']);
    }

    public function test_trial_balance_skips_accounts_without_activity(): void
    {
        $bank = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);
        $this->postLine($bank->id, 50, 0, '2026-02-01');

        $report = $this->service->trialBalance('2026-02-01', '2026-02-28');

        $this->assertCount(1, $report['rows']);
        $this->assertFalse($report['is_balanced']);
    }
}
