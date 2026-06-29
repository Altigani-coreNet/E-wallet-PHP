<?php

namespace App\Modules\Accounting\Services;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\TransactionLine;
use App\Services\LedgerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class FinancialReportService
{
    public function __construct(
        private readonly AccountBalanceService $balanceService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function ledger(?int $accountId, ?string $customerId, string $startDate, string $endDate): array
    {
        $query = TransactionLine::query()
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'transaction_lines.account_id')
            ->join('chart_of_account_types', 'chart_of_account_types.id', '=', 'chart_of_accounts.type')
            ->where('chart_of_accounts.created_by', 0)
            ->whereDate('transaction_lines.date', '>=', $startDate)
            ->whereDate('transaction_lines.date', '<=', $endDate);

        if ($accountId) {
            $query->where('transaction_lines.account_id', $accountId);
        }

        $this->applyCustomerFilter($query, $customerId);

        $lines = $query
            ->select([
                'transaction_lines.id',
                'transaction_lines.account_id',
                'transaction_lines.reference',
                'transaction_lines.reference_id',
                'transaction_lines.reference_sub_id',
                'transaction_lines.date',
                'transaction_lines.debit',
                'transaction_lines.credit',
                'chart_of_accounts.code as account_code',
                'chart_of_accounts.name as account_name',
                'chart_of_account_types.name as type_name',
            ])
            ->orderBy('chart_of_accounts.code')
            ->orderBy('transaction_lines.date')
            ->orderBy('transaction_lines.id')
            ->get();

        $customerIds = $lines->pluck('reference_id')
            ->filter(fn ($id) => filled($id) && (string) $id !== '0')
            ->unique()
            ->values();

        $customers = Customer::query()
            ->whereIn('id', $customerIds)
            ->pluck('name', 'id');

        $accountIds = $lines->pluck('account_id')->unique();
        $openingSums = TransactionLine::query()
            ->whereIn('account_id', $accountIds)
            ->whereDate('date', '<', $startDate)
            ->when($customerId, fn (Builder $q) => $q->where('reference_id', $customerId))
            ->selectRaw('account_id, COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $typeByAccount = $lines->groupBy('account_id')
            ->map(fn ($group) => (string) ($group->first()->type_name ?? 'Assets'));

        $openings = [];
        foreach ($accountIds as $aid) {
            $typeName = $typeByAccount[$aid] ?? 'Assets';
            $sums = $openingSums->get($aid);
            $openings[$aid] = $sums
                ? $this->balanceService->signedBalance((float) $sums->total_debit, (float) $sums->total_credit, $typeName)
                : 0.0;
        }

        $rows = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $runningByAccount = [];

        foreach ($lines as $line) {
            $aid = (int) $line->account_id;
            if (! isset($runningByAccount[$aid])) {
                $runningByAccount[$aid] = $openings[$aid] ?? 0.0;
            }

            $debit = round((float) $line->debit, 2);
            $credit = round((float) $line->credit, 2);
            $totalDebit += $debit;
            $totalCredit += $credit;

            $typeName = (string) ($line->type_name ?? 'Assets');
            $runningByAccount[$aid] = $this->applyRunningBalance($runningByAccount[$aid], $line, $typeName);

            $rows[] = [
                'id' => $line->id,
                'account_id' => $aid,
                'account_code' => (int) $line->account_code,
                'account_name' => $line->account_name,
                'name' => $this->resolveCounterpartyName($line, $customers),
                'reference' => $line->reference,
                'reference_id' => (string) $line->reference_id,
                'transaction_type' => $this->transactionTypeLabel($line),
                'date' => $line->date?->format('Y-m-d') ?? (string) $line->date,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => round($runningByAccount[$aid], 2),
            ];
        }

        return [
            'filter' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'account_id' => $accountId,
                'customer_id' => $customerId,
            ],
            'rows' => $rows,
            'totals' => [
                'debit' => round($totalDebit, 2),
                'credit' => round($totalCredit, 2),
            ],
        ];
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public function ledgerCustomers(): array
    {
        return Customer::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportLedger(?int $accountId, ?string $customerId, string $startDate, string $endDate)
    {
        $payload = $this->ledger($accountId, $customerId, $startDate, $endDate);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ledger Summary');

        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'Ledger Summary Report - '.date('Y-m-d H:i'));
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', 'Date Range: '.$startDate.' to '.$endDate);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['Account Name', 'Name', 'Transaction Type', 'Transaction Date', 'Debit', 'Credit', 'Balance'];
        $sheet->fromArray([$headers], null, 'A3');
        $sheet->getStyle('A3:G3')->getFont()->setBold(true);
        $sheet->getStyle('A3:G3')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A3:G3')->getFont()->getColor()->setRGB('FFFFFF');

        $row = 4;
        foreach ($payload['rows'] as $entry) {
            $sheet->fromArray([
                $entry['account_name'],
                $entry['name'] ?? '-',
                $entry['transaction_type'],
                $entry['date'],
                $entry['debit'],
                $entry['credit'],
                $entry['balance'],
            ], null, "A{$row}");
            $row++;
        }

        $filename = 'ledger_summary_'.date('Y-m-d').'.xlsx';
        $tempPath = storage_path('app/temp/'.Str::uuid().'.xlsx');
        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }

        (new Xlsx($spreadsheet))->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Hook: extend join/whereIn when customer linkage changes.
     */
    private function applyCustomerFilter(Builder $query, ?string $customerId): void
    {
        if ($customerId) {
            $query->where('transaction_lines.reference_id', $customerId);
        }
    }

    /**
     * Hook: map reference to display label.
     */
    private function transactionTypeLabel(object $line): string
    {
        return match ($line->reference ?? '') {
            LedgerService::REF_WALLET_TOPUP => 'Wallet Top-up',
            LedgerService::REF_WALLET_TRANSFER => 'Wallet Transfer',
            default => str_replace('_', ' ', (string) ($line->reference ?? '')),
        };
    }

    /**
     * Hook: map reference/reference_id to counterparty name.
     *
     * @param  \Illuminate\Support\Collection<int, string>  $customers
     */
    private function resolveCounterpartyName(object $line, $customers): ?string
    {
        $referenceId = (string) ($line->reference_id ?? '0');

        if ($referenceId === '' || $referenceId === '0') {
            return 'Master Wallet';
        }

        return $customers[$referenceId] ?? null;
    }

    /**
     * Hook: customize per-reference balance sign rules here.
     * Default uses IFRS debit/credit-normal rules from AccountBalanceService.
     */
    private function applyRunningBalance(float $running, object $line, string $typeName): float
    {
        $debit = round((float) ($line->debit ?? 0), 2);
        $credit = round((float) ($line->credit ?? 0), 2);

        if ($this->balanceService->isDebitNormal($typeName)) {
            return round($running + $debit - $credit, 2);
        }

        return round($running + $credit - $debit, 2);
    }

    /**
     * @return array<string, mixed>
     */
    public function balanceSheet(string $startDate, string $endDate): array
    {
        $assetsSection = $this->buildBalanceSheetSection($startDate, $endDate, 'Assets');
        $liabilitiesSection = $this->buildBalanceSheetSection($startDate, $endDate, 'Liabilities');
        $equitySection = $this->buildBalanceSheetSection($startDate, $endDate, 'Equity');

        $periodTotals = $this->balanceService->totalsByTypeNameForPeriod($startDate, $endDate);
        $income = round($periodTotals['Income'] ?? 0, 2);
        $cogs = round($periodTotals['Costs of Goods Sold'] ?? 0, 2);
        $expenses = round($periodTotals['Expenses'] ?? 0, 2);
        $netIncome = round($income - $cogs - $expenses, 2);

        $equitySection['net_income'] = $netIncome;
        if (abs($netIncome) >= 0.01) {
            $equitySection['sub_types'][] = [
                'sub_type_name' => 'Net Income',
                'accounts' => [[
                    'id' => null,
                    'code' => null,
                    'name' => 'Net Income',
                    'balance' => $netIncome,
                    'is_net_income' => true,
                ]],
                'subtotal' => $netIncome,
            ];
        }

        $assetsTotal = round($assetsSection['total'], 2);
        $liabilitiesTotal = round($liabilitiesSection['total'], 2);
        $equityTotal = round($equitySection['total'] + $netIncome, 2);
        $equitySection['total'] = $equityTotal;

        $liabilitiesAndEquity = round($liabilitiesTotal + $equityTotal, 2);
        $difference = round($assetsTotal - $liabilitiesAndEquity, 2);

        return [
            'filter' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'sections' => [
                'assets' => $assetsSection,
                'liabilities' => $liabilitiesSection,
                'equity' => $equitySection,
            ],
            'totals' => [
                'total_assets' => $assetsTotal,
                'total_liabilities' => $liabilitiesTotal,
                'total_equity' => $equityTotal,
                'total_liabilities_and_equity' => $liabilitiesAndEquity,
            ],
            'is_balanced' => abs($difference) < 0.01,
            'difference' => $difference,
        ];
    }

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportBalanceSheet(string $startDate, string $endDate)
    {
        $payload = $this->balanceSheet($startDate, $endDate);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Balance Sheet');

        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A1', 'Balance Sheet Report - '.date('Y-m-d H:i'));
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:C2');
        $sheet->setCellValue('A2', 'Date Range: '.$startDate.' to '.$endDate);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['Account', 'Code', 'Amount'];
        $sheet->fromArray([$headers], null, 'A3');
        $sheet->getStyle('A3:C3')->getFont()->setBold(true);
        $sheet->getStyle('A3:C3')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A3:C3')->getFont()->getColor()->setRGB('FFFFFF');

        $row = 4;
        foreach (['assets', 'liabilities', 'equity'] as $sectionKey) {
            $section = $payload['sections'][$sectionKey];
            $sheet->setCellValue("A{$row}", $section['name']);
            $sheet->setCellValue("C{$row}", $section['total']);
            $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
            $row++;

            foreach ($section['sub_types'] as $subType) {
                $sheet->setCellValue("A{$row}", '  '.$subType['sub_type_name']);
                $sheet->setCellValue("C{$row}", $subType['subtotal']);
                $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
                $row++;

                foreach ($subType['accounts'] as $account) {
                    $sheet->fromArray([
                        '    '.$account['name'],
                        $account['code'] ?? '',
                        $account['balance'],
                    ], null, "A{$row}");
                    $row++;
                }
            }
        }

        $sheet->setCellValue("A{$row}", 'Total Liabilities & Equity');
        $sheet->setCellValue("C{$row}", $payload['totals']['total_liabilities_and_equity']);
        $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);

        $filename = 'balance_sheet_'.date('Y-m-d').'.xlsx';
        $tempPath = storage_path('app/temp/'.Str::uuid().'.xlsx');
        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }

        (new Xlsx($spreadsheet))->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * @return array{name: string, total: float, sub_types: list<array<string, mixed>>}
     */
    private function buildBalanceSheetSection(string $startDate, string $endDate, string $typeName): array
    {
        return $this->buildPeriodSection($startDate, $endDate, $typeName);
    }

    /**
     * @return array{name: string, total: float, sub_types: list<array<string, mixed>>}
     */
    private function buildProfitLossSection(string $startDate, string $endDate, string $typeName): array
    {
        return $this->buildPeriodSection($startDate, $endDate, $typeName);
    }

    /**
     * @return array{name: string, total: float, sub_types: list<array<string, mixed>>}
     */
    private function buildPeriodSection(string $startDate, string $endDate, string $typeName): array
    {
        $accounts = ChartOfAccount::query()
            ->with(['accountType', 'accountSubType'])
            ->where('created_by', 0)
            ->whereHas('accountType', fn ($q) => $q->where('name', $typeName))
            ->orderBy('code')
            ->get();

        $subTypeGroups = [];
        $sectionTotal = 0.0;

        foreach ($accounts as $account) {
            $balance = $this->balanceService->balance(
                $account->id,
                $startDate,
                $endDate,
                AccountBalanceService::MODE_PERIOD,
                $typeName
            );

            if (abs($balance) < 0.01) {
                continue;
            }

            $subTypeName = $account->accountSubType?->name ?? 'Other';
            if (! isset($subTypeGroups[$subTypeName])) {
                $subTypeGroups[$subTypeName] = [
                    'sub_type_name' => $subTypeName,
                    'accounts' => [],
                    'subtotal' => 0.0,
                ];
            }

            $subTypeGroups[$subTypeName]['accounts'][] = [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'balance' => $balance,
            ];
            $subTypeGroups[$subTypeName]['subtotal'] = round($subTypeGroups[$subTypeName]['subtotal'] + $balance, 2);
            $sectionTotal += $balance;
        }

        return [
            'name' => $typeName,
            'total' => round($sectionTotal, 2),
            'sub_types' => array_values($subTypeGroups),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profitAndLoss(string $startDate, string $endDate): array
    {
        $incomeSection = $this->buildProfitLossSection($startDate, $endDate, 'Income');
        $cogsSection = $this->buildProfitLossSection($startDate, $endDate, 'Costs of Goods Sold');
        $expensesSection = $this->buildProfitLossSection($startDate, $endDate, 'Expenses');

        $income = round($incomeSection['total'], 2);
        $cogs = round($cogsSection['total'], 2);
        $expenses = round($expensesSection['total'], 2);
        $grossProfit = round($income - $cogs, 2);
        $netProfit = round($grossProfit - $expenses, 2);

        return [
            'filter' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'sections' => [
                'income' => $incomeSection,
                'costs_of_goods_sold' => $cogsSection,
                'expenses' => $expensesSection,
            ],
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
        ];
    }

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportProfitAndLoss(string $startDate, string $endDate)
    {
        $payload = $this->profitAndLoss($startDate, $endDate);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Profit and Loss');

        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A1', 'Profit & Loss Report - '.date('Y-m-d H:i'));
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:C2');
        $sheet->setCellValue('A2', 'Date Range: '.$startDate.' to '.$endDate);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['Account', 'Code', 'Amount'];
        $sheet->fromArray([$headers], null, 'A3');
        $sheet->getStyle('A3:C3')->getFont()->setBold(true);
        $sheet->getStyle('A3:C3')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A3:C3')->getFont()->getColor()->setRGB('FFFFFF');

        $row = 4;
        $sectionKeys = ['income', 'costs_of_goods_sold', 'expenses'];

        foreach ($sectionKeys as $sectionKey) {
            $section = $payload['sections'][$sectionKey];
            $sheet->setCellValue("A{$row}", $section['name']);
            $sheet->setCellValue("C{$row}", $section['total']);
            $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
            $row++;

            foreach ($section['sub_types'] as $subType) {
                $sheet->setCellValue("A{$row}", '  '.$subType['sub_type_name']);
                $sheet->setCellValue("C{$row}", $subType['subtotal']);
                $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
                $row++;

                foreach ($subType['accounts'] as $account) {
                    $sheet->fromArray([
                        '    '.$account['name'],
                        $account['code'] ?? '',
                        $account['balance'],
                    ], null, "A{$row}");
                    $row++;
                }
            }

            if ($sectionKey === 'costs_of_goods_sold') {
                $sheet->setCellValue("A{$row}", 'Gross Profit');
                $sheet->setCellValue("C{$row}", $payload['gross_profit']);
                $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
                $row++;
            }
        }

        $sheet->setCellValue("A{$row}", 'Net Profit');
        $sheet->setCellValue("C{$row}", $payload['net_profit']);
        $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);

        $filename = 'profit_and_loss_'.date('Y-m-d').'.xlsx';
        $tempPath = storage_path('app/temp/'.Str::uuid().'.xlsx');
        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }

        (new Xlsx($spreadsheet))->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * @return array<string, mixed>
     */
    public function trialBalance(string $startDate, string $endDate): array
    {
        $accounts = ChartOfAccount::query()
            ->with(['accountType', 'accountSubType'])
            ->where('created_by', 0)
            ->orderBy('code')
            ->get();

        $rows = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($accounts as $account) {
            $sums = $this->balanceService->rawSums($account->id, $startDate, $endDate);
            if ($sums['debit'] == 0.0 && $sums['credit'] == 0.0) {
                continue;
            }

            $typeName = $account->accountType?->name ?? 'Assets';
            $totalDebit += $sums['debit'];
            $totalCredit += $sums['credit'];

            $rows[] = [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $typeName,
                'sub_type' => $account->accountSubType?->name,
                'debit' => $sums['debit'],
                'credit' => $sums['credit'],
                'balance' => $this->balanceService->signedBalance($sums['debit'], $sums['credit'], $typeName),
            ];
        }

        return [
            'filter' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'rows' => $rows,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
        ];
    }

    /**
     * @param  list<string>  $typeNames
     * @return array<string, list<array<string, mixed>>>
     */
    private function accountsGroupedByType(?string $endDate, array $typeNames): array
    {
        $result = array_fill_keys($typeNames, []);

        $accounts = ChartOfAccount::query()
            ->with(['accountType', 'accountSubType'])
            ->where('created_by', 0)
            ->whereHas('accountType', fn ($q) => $q->whereIn('name', $typeNames))
            ->orderBy('code')
            ->get();

        foreach ($accounts as $account) {
            $typeName = $account->accountType?->name;
            if (! $typeName || ! in_array($typeName, $typeNames, true)) {
                continue;
            }

            $balance = $this->balanceService->closingBalance($account->id, $endDate ?? date('Y-m-d'), $typeName);
            if (abs($balance) < 0.01) {
                continue;
            }

            $result[$typeName][] = [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'sub_type' => $account->accountSubType?->name,
                'balance' => $balance,
            ];
        }

        return $result;
    }
}
