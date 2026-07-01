<?php

namespace App\Services\Admin;

use App\Models\ChartOfAccount;
use App\Models\Partner;
use App\Modules\Accounting\Services\AccountBalanceService;
use App\Services\IdempotencyService;
use App\Services\LedgerService;
use App\Services\ProviderSettlementService;
use InvalidArgumentException;

class AdminProviderSettlementService
{
    public function __construct(
        private readonly ProviderSettlementService $providerSettlementService,
        private readonly AccountBalanceService $balanceService,
        private readonly IdempotencyService $idempotencyService,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function index(array $filters = []): array
    {
        $query = Partner::query()
            ->with('chartOfAccount')
            ->whereNotNull('account_id')
            ->where('status', 'approved');

        if ($search = ($filters['search'] ?? null)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('business_name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')->get()->map(function (Partner $partner) {
            $account = $partner->chartOfAccount;

            return [
                'partner_id' => $partner->id,
                'partner_name' => $partner->name,
                'account_id' => $partner->account_id,
                'account_code' => $account?->code,
                'account_name' => $account?->name,
                'payable_balance' => $account
                    ? $this->balanceService->balance((int) $account->id)
                    : 0.0,
            ];
        })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function settle(
        int $adminUserId,
        string $partnerId,
        float $amount,
        ?string $description = null,
        ?string $idempotencyKey = null,
    ): array {
        return $this->idempotencyService->execute(
            $adminUserId,
            'admin.provider_settlement:'.$partnerId,
            $idempotencyKey,
            function () use ($partnerId, $amount, $description, $adminUserId) {
                $partner = Partner::query()->with('chartOfAccount')->findOrFail($partnerId);

                if (! $partner->account_id) {
                    throw new InvalidArgumentException('Partner does not have a linked payable account.');
                }

                $this->providerSettlementService->settle(
                    $partner,
                    $amount,
                    $description,
                    $adminUserId
                );

                $payableAfter = $this->balanceService->balance((int) $partner->account_id);
                $bankAccount = ChartOfAccount::query()->byCode(\App\Support\AccountCode::BANK)->firstOrFail();
                $bankAfter = $this->balanceService->balance((int) $bankAccount->id);

                return [
                    'partner_id' => $partner->id,
                    'amount' => round($amount, 2),
                    'description' => $description,
                    'payable_balance_after' => $payableAfter,
                    'bank_balance_after' => $bankAfter,
                    'posting_reference' => LedgerService::REF_PROVIDER_SETTLEMENT,
                ];
            }
        );
    }
}
