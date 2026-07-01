<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\TransactionLine;
use Carbon\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * IFRS double-entry helper — posts balanced debit/credit rows to transaction_lines.
 */
class LedgerService
{
    public const REF_WALLET_TOPUP = 'WALLET_TOPUP';

    public const REF_WALLET_TRANSFER = 'WALLET_TRANSFER';

    public const REF_WALLET_CASH_IN = 'WALLET_CASH_IN';

    public const REF_WALLET_CASH_OUT = 'WALLET_CASH_OUT';

    public const REF_OPENING_CAPITAL = 'OPENING_CAPITAL';

    public const REF_BILL_PAYMENT = 'BILL_PAYMENT';

    public const REF_PROVIDER_SETTLEMENT = 'PROVIDER_SETTLEMENT';

    public function account(int $code): ChartOfAccount
    {
        $account = ChartOfAccount::query()->byCode($code)->first();

        if (! $account) {
            throw new RuntimeException("Chart of account with code {$code} was not found.");
        }

        return $account;
    }

    /**
     * @param  array<int, array{account_code?: int, account_id?: int, debit?: float|string, credit?: float|string, sub_id?: int}>  $lines
     */
    public function post(
        array $lines,
        string $reference,
        int|string $referenceId = '0',
        ?Carbon $date = null,
        int $createdBy = 0,
        ?string $operationId = null
    ): void {
        if ($lines === []) {
            throw new RuntimeException('Ledger entry must contain at least one line.');
        }

        $operationId ??= (string) Str::uuid();

        $postingDate = ($date ?? now())->toDateString();
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $rows = [];

        foreach ($lines as $index => $line) {
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            if ($debit < 0 || $credit < 0) {
                throw new RuntimeException('Debit and credit amounts must be non-negative.');
            }

            if ($debit > 0 && $credit > 0) {
                throw new RuntimeException('A ledger line cannot have both debit and credit amounts.');
            }

            if ($debit === 0.0 && $credit === 0.0) {
                throw new RuntimeException('A ledger line must have a debit or credit amount.');
            }

            $accountId = $line['account_id'] ?? null;
            if ($accountId === null) {
                $accountCode = $line['account_code'] ?? null;
                if ($accountCode === null) {
                    throw new RuntimeException("Ledger line at index {$index} is missing account_id or account_code.");
                }
                $accountId = $this->account((int) $accountCode)->id;
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            $rows[] = [
                'account_id' => (int) $accountId,
                'reference' => $reference,
                'reference_id' => (string) $referenceId,
                'operation_id' => $operationId,
                'reference_sub_id' => (int) ($line['sub_id'] ?? $index),
                'date' => $postingDate,
                'debit' => $debit,
                'credit' => $credit,
                'created_by' => $createdBy,
            ];
        }

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new RuntimeException(sprintf(
                'Unbalanced ledger entry for %s:%d — debits %.2f != credits %.2f.',
                $reference,
                $referenceId,
                $totalDebit,
                $totalCredit
            ));
        }

        foreach ($rows as $row) {
            TransactionLine::create($row);
        }
    }
}
