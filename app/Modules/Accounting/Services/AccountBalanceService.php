<?php

namespace App\Modules\Accounting\Services;

use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountType;
use App\Models\TransactionLine;
use Illuminate\Support\Facades\DB;

class AccountBalanceService
{
    public const MODE_PERIOD = 'period';

    public const MODE_CUMULATIVE = 'cumulative';

    public const MODE_OPENING = 'opening';

    public function isDebitNormal(string $typeName): bool
    {
        $normalized = strtolower(trim($typeName));

        return in_array($normalized, [
            'assets',
            'expenses',
            'costs of goods sold',
        ], true);
    }

    public function signedBalance(float $totalDebit, float $totalCredit, string $typeName): float
    {
        if ($this->isDebitNormal($typeName)) {
            return round($totalDebit - $totalCredit, 2);
        }

        return round($totalCredit - $totalDebit, 2);
    }

    /**
     * @return array{debit: float, credit: float}
     */
    public function rawSums(
        int $accountId,
        ?string $startDate = null,
        ?string $endDate = null,
        string $mode = self::MODE_PERIOD
    ): array {
        $query = TransactionLine::query()->where('account_id', $accountId);

        if ($mode === self::MODE_OPENING) {
            if ($startDate) {
                $query->whereDate('date', '<', $startDate);
            }
        } elseif ($mode === self::MODE_CUMULATIVE) {
            if ($endDate) {
                $query->whereDate('date', '<=', $endDate);
            }
        } else {
            if ($startDate) {
                $query->whereDate('date', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('date', '<=', $endDate);
            }
        }

        $row = $query->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        return [
            'debit' => round((float) ($row->total_debit ?? 0), 2),
            'credit' => round((float) ($row->total_credit ?? 0), 2),
        ];
    }

    public function balance(
        int $accountId,
        ?string $startDate = null,
        ?string $endDate = null,
        string $mode = self::MODE_PERIOD,
        ?string $typeName = null
    ): float {
        $typeName ??= $this->resolveTypeName($accountId);
        $sums = $this->rawSums($accountId, $startDate, $endDate, $mode);

        return $this->signedBalance($sums['debit'], $sums['credit'], $typeName);
    }

    public function openingBalance(int $accountId, string $startDate, ?string $typeName = null): float
    {
        return $this->balance($accountId, $startDate, null, self::MODE_OPENING, $typeName);
    }

    public function closingBalance(int $accountId, string $endDate, ?string $typeName = null): float
    {
        return $this->balance($accountId, null, $endDate, self::MODE_CUMULATIVE, $typeName);
    }

    /**
     * @return array<string, float>
     */
    public function totalsByTypeName(?string $endDate = null): array
    {
        $query = TransactionLine::query()
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'transaction_lines.account_id')
            ->join('chart_of_account_types', 'chart_of_account_types.id', '=', 'chart_of_accounts.type')
            ->select(
                'chart_of_account_types.name as type_name',
                DB::raw('COALESCE(SUM(transaction_lines.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(transaction_lines.credit), 0) as total_credit')
            )
            ->groupBy('chart_of_account_types.id', 'chart_of_account_types.name');

        if ($endDate) {
            $query->whereDate('transaction_lines.date', '<=', $endDate);
        }

        $totals = [
            'Assets' => 0.0,
            'Liabilities' => 0.0,
            'Equity' => 0.0,
            'Income' => 0.0,
            'Costs of Goods Sold' => 0.0,
            'Expenses' => 0.0,
        ];

        foreach ($query->get() as $row) {
            $typeName = (string) $row->type_name;
            $totals[$typeName] = $this->signedBalance(
                (float) $row->total_debit,
                (float) $row->total_credit,
                $typeName
            );
        }

        return $totals;
    }

    /**
     * @return array<string, float>
     */
    public function totalsByTypeNameForPeriod(string $startDate, string $endDate): array
    {
        $query = TransactionLine::query()
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'transaction_lines.account_id')
            ->join('chart_of_account_types', 'chart_of_account_types.id', '=', 'chart_of_accounts.type')
            ->where('chart_of_accounts.created_by', 0)
            ->whereDate('transaction_lines.date', '>=', $startDate)
            ->whereDate('transaction_lines.date', '<=', $endDate)
            ->select(
                'chart_of_account_types.name as type_name',
                DB::raw('COALESCE(SUM(transaction_lines.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(transaction_lines.credit), 0) as total_credit')
            )
            ->groupBy('chart_of_account_types.id', 'chart_of_account_types.name');

        $totals = [
            'Assets' => 0.0,
            'Liabilities' => 0.0,
            'Equity' => 0.0,
            'Income' => 0.0,
            'Costs of Goods Sold' => 0.0,
            'Expenses' => 0.0,
        ];

        foreach ($query->get() as $row) {
            $typeName = (string) $row->type_name;
            $totals[$typeName] = $this->signedBalance(
                (float) $row->total_debit,
                (float) $row->total_credit,
                $typeName
            );
        }

        return $totals;
    }

    public function isSystemBalanced(?string $endDate = null): bool
    {
        $asOf = $endDate ?? date('Y-m-d');
        $typeTotals = $this->totalsByTypeName($asOf);

        $assets = round($typeTotals['Assets'] ?? 0, 2);
        $liabilities = round($typeTotals['Liabilities'] ?? 0, 2);
        $equityAccounts = round($typeTotals['Equity'] ?? 0, 2);
        $retainedEarnings = round(
            ($typeTotals['Income'] ?? 0) - ($typeTotals['Costs of Goods Sold'] ?? 0) - ($typeTotals['Expenses'] ?? 0),
            2
        );
        $liabilitiesAndEquity = round($liabilities + $equityAccounts + $retainedEarnings, 2);

        return abs($assets - $liabilitiesAndEquity) < 0.01;
    }

    private function resolveTypeName(int $accountId): string
    {
        $account = ChartOfAccount::query()
            ->with('accountType')
            ->find($accountId);

        return $account?->accountType?->name ?? 'Assets';
    }
}
