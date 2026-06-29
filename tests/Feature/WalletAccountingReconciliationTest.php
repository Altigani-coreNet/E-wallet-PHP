<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Wallet;
use App\Modules\Accounting\Services\AccountBalanceService;
use App\Modules\Accounting\Services\FinancialReportService;
use App\Services\AccountingService;
use App\Services\WalletService;
use App\Support\AccountCode;
use Database\Seeders\ChartOfAccountSeeder;
use Tests\CustomerAuthTestCase;
use Tests\Support\AdminWalletTestHelpers;

class WalletAccountingReconciliationTest extends CustomerAuthTestCase
{
    use AdminWalletTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountSeeder::class);
        app(WalletService::class)->createMasterWallet();
    }

    public function test_full_wallet_lifecycle_keeps_system_balanced_and_reports_consistent(): void
    {
        app(AccountingService::class)->recordOpeningCapital(1000, 'Opening equity');

        // Operator funds the master float with real cash (DR Bank / CR Master).
        $master = Wallet::query()->where('wallet_id', WalletService::MASTER_WALLET_ID)->firstOrFail();
        app(WalletService::class)->cashIn($master, 200, 'Fund master float');

        $sender = Customer::factory()->active()->create();
        $recipient = Customer::factory()->active()->create();

        $senderWallet = app(WalletService::class)->createForCustomer($sender);
        $recipientWallet = app(WalletService::class)->createForCustomer($recipient);

        app(WalletService::class)->cashIn($senderWallet, 200, 'Buy balance');
        config(['services.wallet.transfer_fee' => 2]);
        app(WalletService::class)->transfer($senderWallet, $recipientWallet, 50, 'Pay friend');
        app(WalletService::class)->cashOut($recipientWallet, 20, 'Cash out');

        $balanceService = app(AccountBalanceService::class);
        $this->assertTrue($balanceService->isSystemBalanced());

        $today = date('Y-m-d');
        $startOfYear = date('Y-01-01');

        $trialBalance = app(FinancialReportService::class)->trialBalance($startOfYear, $today);
        $this->assertTrue($trialBalance['is_balanced']);

        $balanceSheet = app(FinancialReportService::class)->balanceSheet($startOfYear, $today);
        $this->assertTrue($balanceSheet['is_balanced']);

        $senderWallet->refresh();
        $recipientWallet->refresh();

        $this->assertSame(150.0, (float) $senderWallet->balance);
        $this->assertSame(28.0, (float) $recipientWallet->balance);

        $master->refresh();

        $bankBalance = $balanceService->balance(
            (int) \App\Models\ChartOfAccount::query()->byCode(AccountCode::BANK)->value('id')
        );
        $customerLiabilities = (float) $senderWallet->balance + (float) $recipientWallet->balance;
        $masterFloat = (float) $master->balance;
        $feeIncome = $balanceService->balance(
            (int) \App\Models\ChartOfAccount::query()->byCode(AccountCode::FEE_INCOME)->value('id')
        );

        // Bank (less opening equity) is backed 1:1 by the master float + customer
        // liabilities + earned fee income.
        $this->assertSame(
            round($customerLiabilities + $masterFloat + $feeIncome, 2),
            round($bankBalance - 1000, 2)
        );
    }
}
