<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Partner;
use App\Modules\Accounting\Services\ChartOfAccountService;
use App\Support\AccountCode;
use RuntimeException;

class PartnerPayableAccountService
{
    public const PAYABLE_CODE_MIN = 2110;

    public const PAYABLE_CODE_MAX = 2199;

    public function allocateForPartner(Partner $partner): ChartOfAccount
    {
        if ($partner->account_id) {
            $existing = ChartOfAccount::query()->find($partner->account_id);
            if ($existing) {
                return $existing;
            }
        }

        $liabilityTemplate = ChartOfAccount::query()->byCode(AccountCode::CUSTOMER_LIABILITY)->first();
        if (! $liabilityTemplate) {
            throw new RuntimeException('Customer Wallet Liability account (2000) is not configured.');
        }

        $nextCode = $this->nextPayableCode();

        $account = ChartOfAccount::query()->create([
            'name' => 'Provider Payable — '.$partner->name,
            'code' => $nextCode,
            'type' => $liabilityTemplate->type,
            'sub_type' => $liabilityTemplate->sub_type,
            'is_enabled' => 1,
            'description' => 'Third-party provider payable for partner '.$partner->id,
            'created_by' => ChartOfAccountService::SYSTEM_OWNER,
        ]);

        $partner->update(['account_id' => $account->id]);

        return $account;
    }

    private function nextPayableCode(): int
    {
        $latest = ChartOfAccount::query()
            ->whereBetween('code', [self::PAYABLE_CODE_MIN, self::PAYABLE_CODE_MAX])
            ->orderByDesc('code')
            ->value('code');

        $next = $latest ? ((int) $latest + 10) : self::PAYABLE_CODE_MIN;

        if ($next > self::PAYABLE_CODE_MAX) {
            throw new RuntimeException('Provider payable account range 2110–2199 is exhausted.');
        }

        return $next;
    }
}
