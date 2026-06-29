<?php

namespace Tests\Support;

use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountSubType;
use App\Models\TransactionLine;
use App\Services\LedgerService;
use App\Services\WalletService;
use Database\Seeders\ChartOfAccountSeeder;

trait AccountingTestHelpers
{
    protected function seedChartOfAccounts(): void
    {
        $this->seed(ChartOfAccountSeeder::class);
    }

    protected function accountByCode(int $code): ChartOfAccount
    {
        return ChartOfAccount::query()->byCode($code)->firstOrFail();
    }

    protected function postLine(
        int $accountId,
        float $debit,
        float $credit,
        ?string $date = null,
        string $reference = 'TEST',
        int $referenceId = 1,
        int $referenceSubId = 0
    ): TransactionLine {
        return TransactionLine::create([
            'account_id' => $accountId,
            'reference' => $reference,
            'reference_id' => $referenceId,
            'reference_sub_id' => $referenceSubId,
            'date' => $date ?? now()->toDateString(),
            'debit' => $debit,
            'credit' => $credit,
            'created_by' => 0,
        ]);
    }

    protected function postBalancedWalletTopUp(float $amount, ?string $date = null): void
    {
        $bank = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);
        $liability = $this->accountByCode(WalletService::WALLET_LIABILITY_ACCOUNT_CODE);
        $date ??= now()->toDateString();

        $this->postLine($bank->id, $amount, 0, $date, LedgerService::REF_WALLET_TOPUP, 1, 0);
        $this->postLine($liability->id, 0, $amount, $date, LedgerService::REF_WALLET_TOPUP, 1, 1);
    }

    protected function firstSubTypeId(): int
    {
        return ChartOfAccountSubType::query()->value('id');
    }
}
