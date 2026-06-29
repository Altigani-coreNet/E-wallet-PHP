<?php

namespace App\Services;

use App\Support\AccountCode;
use InvalidArgumentException;

class AccountingService
{
    public const SCOPE_OPENING_CAPITAL = 'accounting.opening_capital';

    public function __construct(
        private readonly LedgerService $ledgerService,
        private readonly IdempotencyService $idempotencyService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function recordOpeningCapital(
        float $amount,
        ?string $description = null,
        ?string $idempotencyKey = null,
        int $createdBy = 0
    ): array {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Opening capital amount must be greater than zero.');
        }

        return $this->idempotencyService->execute(
            0,
            self::SCOPE_OPENING_CAPITAL,
            $idempotencyKey,
            function () use ($amount, $description, $createdBy) {
                $this->ledgerService->post(
                    [
                        ['account_code' => AccountCode::BANK, 'debit' => $amount, 'sub_id' => 0],
                        ['account_code' => AccountCode::OWNER_EQUITY, 'credit' => $amount, 'sub_id' => 1],
                    ],
                    LedgerService::REF_OPENING_CAPITAL,
                    0,
                    createdBy: $createdBy
                );

                return [
                    'amount' => $amount,
                    'description' => $description ?? 'Opening capital',
                    'posting_reference' => LedgerService::REF_OPENING_CAPITAL,
                ];
            }
        );
    }
}
