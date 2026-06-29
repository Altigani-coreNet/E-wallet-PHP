<?php

namespace App\Modules\Accounting\Services;

use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountSubType;
use App\Models\ChartOfAccountType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

class ChartOfAccountService
{
    public const SYSTEM_OWNER = 0;

    public function __construct(
        private readonly AccountBalanceService $balanceService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function index(array $filters): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($filters);

        $query = ChartOfAccount::query()
            ->with(['accountType', 'accountSubType'])
            ->where('created_by', self::SYSTEM_OWNER);

        if ($search = ($filters['search'] ?? null)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== 'all') {
            $enabled = in_array($filters['status'], ['active', '1', 1, true], true) ? 1 : 0;
            $query->where('is_enabled', $enabled);
        }

        if ($typeKey = ($filters['type'] ?? null)) {
            $typeName = $this->typeNameFromKey((string) $typeKey);
            if ($typeName) {
                $query->whereHas('accountType', fn ($q) => $q->where('name', $typeName));
            }
        }

        $accounts = $query->orderBy('code')->get();

        $typeTotals = $this->balanceService->totalsByTypeName($endDate);
        $groups = $this->buildGroups($accounts, $startDate, $endDate);

        return [
            'filter' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'search' => $filters['search'] ?? null,
                'status' => $filters['status'] ?? 'all',
                'type' => $filters['type'] ?? 'all',
            ],
            'summary' => [
                'total_assets' => round($typeTotals['Assets'] ?? 0, 2),
                'total_liabilities' => round($typeTotals['Liabilities'] ?? 0, 2),
                'total_equity' => round(
                    ($typeTotals['Equity'] ?? 0)
                    + ($typeTotals['Income'] ?? 0)
                    - ($typeTotals['Costs of Goods Sold'] ?? 0)
                    - ($typeTotals['Expenses'] ?? 0),
                    2
                ),
                'total_accounts' => $accounts->count(),
                'is_balanced' => $this->balanceService->isSystemBalanced($endDate),
            ],
            'groups' => $groups,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function show(int $id, ?string $startDate = null, ?string $endDate = null): array
    {
        [$startDate, $endDate] = $this->resolveDateRange([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $account = $this->findSystemAccount($id);
        $account->load(['accountType', 'accountSubType']);

        return $this->formatAccount($account, $startDate, $endDate, true);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): ChartOfAccount
    {
        return DB::transaction(function () use ($data) {
            $subType = ChartOfAccountSubType::query()->findOrFail($data['sub_type']);

            return ChartOfAccount::create([
                'name' => $data['name'],
                'code' => (int) $data['code'],
                'type' => $subType->type,
                'sub_type' => $subType->id,
                'description' => $data['description'] ?? null,
                'is_enabled' => (int) ($data['is_enabled'] ?? 1),
                'created_by' => self::SYSTEM_OWNER,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): ChartOfAccount
    {
        $account = $this->findSystemAccount($id);

        return DB::transaction(function () use ($account, $data) {
            if (isset($data['sub_type'])) {
                $subType = ChartOfAccountSubType::query()->findOrFail($data['sub_type']);
                $account->type = $subType->type;
                $account->sub_type = $subType->id;
            }

            if (isset($data['name'])) {
                $account->name = $data['name'];
            }
            if (isset($data['code'])) {
                $account->code = (int) $data['code'];
            }
            if (array_key_exists('description', $data)) {
                $account->description = $data['description'];
            }
            if (array_key_exists('is_enabled', $data)) {
                $account->is_enabled = (int) $data['is_enabled'];
            }

            $account->save();

            return $account->fresh(['accountType', 'accountSubType']);
        });
    }

    public function destroy(int $id): void
    {
        $account = $this->findSystemAccount($id);

        if ($account->transactionLines()->exists()) {
            throw new RuntimeException('This chart of account is used in transactions and cannot be deleted.');
        }

        $account->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function types(): array
    {
        $types = ChartOfAccountType::query()
            ->with(['subTypes' => fn ($q) => $q->orderBy('name')])
            ->orderBy('name')
            ->get();

        return [
            'types' => $types->map(fn (ChartOfAccountType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'type_key' => $this->typeKeyFromName($type->name),
                'sub_types' => $type->subTypes->map(fn (ChartOfAccountSubType $sub) => [
                    'id' => $sub->id,
                    'name' => $sub->name,
                    'type_id' => $sub->type,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    public function suggestNextCode(?int $typeId = null): int
    {
        $query = ChartOfAccount::query()->where('created_by', self::SYSTEM_OWNER);

        if ($typeId) {
            $query->where('type', $typeId);
        }

        $maxCode = (int) $query->max('code');

        if ($maxCode <= 0) {
            return $typeId ? $this->defaultCodeForType($typeId) : 1000;
        }

        return $maxCode + 10;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(?string $startDate = null, ?string $endDate = null)
    {
        [$startDate, $endDate] = $this->resolveDateRange([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $payload = $this->index([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Chart of Accounts');

        $headers = ['Code', 'Name', 'Type Name', 'Sub Type Name', 'Balance', 'Status', 'Description'];
        $sheet->fromArray([$headers], null, 'A1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:G1')->getFont()->getColor()->setRGB('FFFFFF');

        $row = 2;
        foreach ($payload['groups'] as $group) {
            foreach ($group['sub_types'] as $subType) {
                foreach ($subType['accounts'] as $account) {
                    $sheet->fromArray([
                        $account['code'],
                        $account['name'],
                        $group['type_name'],
                        $subType['sub_type_name'],
                        $account['balance'],
                        $account['status'],
                        $account['description'] ?? '',
                    ], null, "A{$row}");
                    $row++;
                }
            }
        }

        $filename = 'chart_of_accounts_'.date('Y-m-d').'.xlsx';
        $tempPath = storage_path('app/temp/'.Str::uuid().'.xlsx');
        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }

        (new Xlsx($spreadsheet))->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function sample()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sample');

        $headers = ['Name', 'Code', 'Type Name', 'Sub Type Name', 'Is Enabled', 'Description'];
        $sheet->fromArray([$headers], null, 'A1');
        $sheet->fromArray([
            ['Petty Cash', 1030, 'Assets', 'Current Asset', 1, 'Office petty cash'],
            ['Accrued Expenses', 2020, 'Liabilities', 'Current Liabilities', 1, 'Accrued operating expenses'],
        ], null, 'A2');

        $filename = 'chart_of_account_sample_'.date('Y-m-d').'.xlsx';
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
    public function import(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray();

        if ($rows === []) {
            throw new RuntimeException('Import file is empty.');
        }

        $headers = array_map(
            fn ($h) => strtolower(str_replace(' ', '_', trim((string) $h))),
            array_shift($rows)
        );

        $successCount = 0;
        $failureCount = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $rowData = array_combine($headers, array_pad($row, count($headers), null));
                if (! is_array($rowData)) {
                    continue;
                }

                if (empty($rowData['name']) && empty($rowData['code'])) {
                    continue;
                }

                if (empty($rowData['name'])) {
                    $errors[] = "Row {$rowNumber}: Missing account name";
                    $failureCount++;
                    continue;
                }

                if (empty($rowData['code'])) {
                    $errors[] = "Row {$rowNumber} '{$rowData['name']}': Missing account code";
                    $failureCount++;
                    continue;
                }

                $type = null;
                $subType = null;

                if (! empty($rowData['type_name'])) {
                    $typeRecord = ChartOfAccountType::query()
                        ->where('name', trim((string) $rowData['type_name']))
                        ->first();
                    if (! $typeRecord) {
                        $errors[] = "Row {$rowNumber} '{$rowData['name']}': Type '{$rowData['type_name']}' not found";
                        $failureCount++;
                        continue;
                    }
                    $type = $typeRecord->id;
                }

                if (! empty($rowData['sub_type_name'])) {
                    $subTypeQuery = ChartOfAccountSubType::query()
                        ->where('name', trim((string) $rowData['sub_type_name']));
                    if ($type) {
                        $subTypeQuery->where('type', $type);
                    }
                    $subTypeRecord = $subTypeQuery->first();
                    if (! $subTypeRecord) {
                        $errors[] = "Row {$rowNumber} '{$rowData['name']}': Sub Type '{$rowData['sub_type_name']}' not found";
                        $failureCount++;
                        continue;
                    }
                    $subType = $subTypeRecord->id;
                    $type = $subTypeRecord->type;
                }

                if (! $type || ! $subType) {
                    $errors[] = "Row {$rowNumber} '{$rowData['name']}': Type and Sub Type are required";
                    $failureCount++;
                    continue;
                }

                $existing = ChartOfAccount::query()
                    ->where('code', (int) $rowData['code'])
                    ->where('created_by', self::SYSTEM_OWNER)
                    ->first();

                $payload = [
                    'name' => trim((string) $rowData['name']),
                    'code' => (int) $rowData['code'],
                    'type' => $type,
                    'sub_type' => $subType,
                    'is_enabled' => (int) ($rowData['is_enabled'] ?? 1),
                    'description' => trim((string) ($rowData['description'] ?? '')),
                    'created_by' => self::SYSTEM_OWNER,
                ];

                if ($existing) {
                    $existing->update($payload);
                } else {
                    ChartOfAccount::create($payload);
                }

                $successCount++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$rowNumber}: ".$e->getMessage();
                $failureCount++;
            }
        }

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'errors' => array_slice($errors, 0, 50),
        ];
    }

    public function findSystemAccount(int $id): ChartOfAccount
    {
        return ChartOfAccount::query()
            ->where('created_by', self::SYSTEM_OWNER)
            ->findOrFail($id);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ChartOfAccount>  $accounts
     * @return list<array<string, mixed>>
     */
    private function buildGroups($accounts, string $startDate, string $endDate): array
    {
        $types = ChartOfAccountType::query()->orderBy('name')->get();
        $groups = [];

        foreach ($types as $type) {
            $typeAccounts = $accounts->where('type', $type->id);
            if ($typeAccounts->isEmpty()) {
                continue;
            }

            $subTypeGroups = [];
            $subTypes = ChartOfAccountSubType::query()
                ->where('type', $type->id)
                ->orderBy('name')
                ->get();

            foreach ($subTypes as $subType) {
                $subAccounts = $typeAccounts->where('sub_type', $subType->id);
                if ($subAccounts->isEmpty()) {
                    continue;
                }

                $subTypeGroups[] = [
                    'sub_type_id' => $subType->id,
                    'sub_type_name' => $subType->name,
                    'accounts' => $subAccounts
                        ->map(fn (ChartOfAccount $account) => $this->formatAccount($account, $startDate, $endDate))
                        ->values()
                        ->all(),
                ];
            }

            if ($subTypeGroups === []) {
                continue;
            }

            $groups[] = [
                'type_id' => $type->id,
                'type_name' => $type->name,
                'type_key' => $this->typeKeyFromName($type->name),
                'sub_types' => $subTypeGroups,
            ];
        }

        return $groups;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAccount(
        ChartOfAccount $account,
        string $startDate,
        string $endDate,
        bool $detailed = false
    ): array {
        $typeName = $account->accountType?->name ?? 'Assets';

        $data = [
            'id' => $account->id,
            'code' => $account->code,
            'name' => $account->name,
            'type_id' => $account->type,
            'type_name' => $typeName,
            'type_key' => $this->typeKeyFromName($typeName),
            'sub_type_id' => $account->sub_type,
            'sub_type_name' => $account->accountSubType?->name,
            'description' => $account->description,
            'is_enabled' => (int) $account->is_enabled,
            'status' => (int) $account->is_enabled === 1 ? 'active' : 'inactive',
            'balance' => $this->balanceService->balance(
                $account->id,
                $startDate,
                $endDate,
                AccountBalanceService::MODE_PERIOD,
                $typeName
            ),
            'cumulative_balance' => $this->balanceService->closingBalance($account->id, $endDate, $typeName),
            'has_transactions' => $account->relationLoaded('transactionLines')
                ? $account->transactionLines->isNotEmpty()
                : $account->transactionLines()->exists(),
        ];

        if ($detailed) {
            $data['created_at'] = $account->created_at?->toIso8601String();
            $data['updated_at'] = $account->updated_at?->toIso8601String();
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: string, 1: string}
     */
    private function resolveDateRange(array $filters): array
    {
        $start = $filters['start_date'] ?? date('Y-01-01');
        $end = $filters['end_date'] ?? date('Y-m-d');

        return [$start, $end];
    }

    public function typeKeyFromName(string $typeName): string
    {
        return match (strtolower(trim($typeName))) {
            'assets' => 'asset',
            'liabilities' => 'liability',
            'equity' => 'equity',
            'income' => 'income',
            'costs of goods sold' => 'cogs',
            'expenses' => 'expense',
            default => Str::slug($typeName, '_'),
        };
    }

    public function typeNameFromKey(string $typeKey): ?string
    {
        return match ($typeKey) {
            'asset', 'assets' => 'Assets',
            'liability', 'liabilities' => 'Liabilities',
            'equity' => 'Equity',
            'income' => 'Income',
            'cogs', 'costs_of_goods_sold' => 'Costs of Goods Sold',
            'expense', 'expenses' => 'Expenses',
            default => null,
        };
    }

    private function defaultCodeForType(int $typeId): int
    {
        $type = ChartOfAccountType::query()->find($typeId);
        if (! $type) {
            return 1000;
        }

        return match (strtolower($type->name)) {
            'assets' => 1000,
            'liabilities' => 2000,
            'equity' => 3000,
            'income' => 4000,
            'costs of goods sold' => 5000,
            'expenses' => 5000,
            default => 9000,
        };
    }
}
