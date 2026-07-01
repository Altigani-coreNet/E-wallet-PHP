<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Partner;
use App\Modules\Accounting\Services\AccountBalanceService;
use App\Support\AccountCode;
use InvalidArgumentException;

class ProviderSettlementService
{
    public function __construct(
        private readonly LedgerService $ledgerService,
        private readonly AccountBalanceService $balanceService,
    ) {
    }

    public function settle(
        Partner $partner,
        float $amount,
        ?string $description = null,
        int $createdBy = 0
    ): void {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Settlement amount must be greater than zero.');
        }

        if (! $partner->account_id) {
            throw new InvalidArgumentException('Partner does not have a linked payable account.');
        }

        $payableAccount = ChartOfAccount::query()->findOrFail($partner->account_id);
        $payableBalance = $this->balanceService->balance((int) $payableAccount->id);

        if ($payableBalance < $amount) {
            throw new InvalidArgumentException('Settlement amount exceeds provider payable balance.');
        }

        $this->ledgerService->post(
            [
                ['account_id' => $payableAccount->id, 'debit' => $amount, 'sub_id' => 0],
                ['account_code' => AccountCode::BANK, 'credit' => $amount, 'sub_id' => 1],
            ],
            LedgerService::REF_PROVIDER_SETTLEMENT,
            (string) $partner->id,
            createdBy: $createdBy
        );
    }
}
