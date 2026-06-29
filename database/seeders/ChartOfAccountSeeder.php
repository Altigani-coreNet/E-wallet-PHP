<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountSubType;
use App\Models\ChartOfAccountType;
use Illuminate\Database\Seeder;

/**
 * Seeds the chart-of-accounts lookup tables and a minimal ewallet account tree.
 *
 * Double-entry posting convention (transaction_lines):
 *   Wallet top-up of 100 — same reference + reference_id on both rows:
 *     Debit  Bank Account (1000)               100.00
 *     Credit Customer Wallet Liability (2000)        100.00
 */
class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        $typeIds = $this->seedTypes();
        $subTypeIds = $this->seedSubTypes($typeIds);
        $this->seedAccounts($typeIds, $subTypeIds);
    }

    private function seedTypes(): array
    {
        $typeIds = [];

        foreach (ChartOfAccountType::$chartOfAccountType as $key => $name) {
            $type = ChartOfAccountType::updateOrCreate(
                ['name' => $name],
                ['created_by' => 0]
            );
            $typeIds[$key] = $type->id;
        }

        return $typeIds;
    }

    private function seedSubTypes(array $typeIds): array
    {
        $subTypeIds = [];

        foreach (ChartOfAccountSubType::$chartOfAccountSubType as $typeKey => $subTypes) {
            $typeId = $typeIds[$typeKey];

            foreach ($subTypes as $subTypeKey => $name) {
                $subType = ChartOfAccountSubType::updateOrCreate(
                    ['name' => $name, 'type' => $typeId],
                    []
                );
                $subTypeIds[$typeKey][$subTypeKey] = $subType->id;
            }
        }

        return $subTypeIds;
    }

    private function seedAccounts(array $typeIds, array $subTypeIds): void
    {
        $accounts = [
            ['code' => 1000, 'name' => 'Bank Cash Account', 'type_key' => 'assets', 'sub_type_key' => '1'],
            ['code' => 1010, 'name' => 'eWallet Float', 'type_key' => 'assets', 'sub_type_key' => '1'],
            ['code' => 1020, 'name' => 'Settlement Receivable', 'type_key' => 'assets', 'sub_type_key' => '1'],
            ['code' => 2000, 'name' => 'Customer Wallet Liability', 'type_key' => 'liabilities', 'sub_type_key' => '1'],
            ['code' => 2050, 'name' => 'Master Wallet Liability', 'type_key' => 'liabilities', 'sub_type_key' => '1'],
            ['code' => 2900, 'name' => 'Fees / Tax Payable', 'type_key' => 'liabilities', 'sub_type_key' => '1'],
            ['code' => 3000, 'name' => 'Owner Equity', 'type_key' => 'equity', 'sub_type_key' => '1'],
            ['code' => 3900, 'name' => 'Retained Earnings', 'type_key' => 'equity', 'sub_type_key' => '1'],
            ['code' => 4000, 'name' => 'Transaction Fee Income', 'type_key' => 'income', 'sub_type_key' => '2'],
            ['code' => 4010, 'name' => 'Commission Income', 'type_key' => 'income', 'sub_type_key' => '2'],
            ['code' => 5000, 'name' => 'Bank Charges', 'type_key' => 'expenses', 'sub_type_key' => '2'],
            ['code' => 5010, 'name' => 'General Operating Expense', 'type_key' => 'expenses', 'sub_type_key' => '2'],
        ];

        foreach ($accounts as $account) {
            ChartOfAccount::updateOrCreate(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'type' => $typeIds[$account['type_key']],
                    'sub_type' => $subTypeIds[$account['type_key']][$account['sub_type_key']],
                    'is_enabled' => 1,
                    'created_by' => 0,
                ]
            );
        }
    }
}
