<?php

namespace Tests\Unit\Accounting;

use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountSubType;
use App\Models\ChartOfAccountType;
use App\Models\TransactionLine;
use App\Modules\Accounting\Services\ChartOfAccountService;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\CustomerAuthTestCase;
use Tests\Support\AccountingTestHelpers;

class ChartOfAccountServiceTest extends CustomerAuthTestCase
{
    use AccountingTestHelpers;

    private ChartOfAccountService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedChartOfAccounts();
        $this->service = app(ChartOfAccountService::class);
    }

    public function test_index_returns_groups_summary_and_default_filters(): void
    {
        $this->postBalancedWalletTopUp(200);

        $payload = $this->service->index([]);

        $this->assertArrayHasKey('filter', $payload);
        $this->assertArrayHasKey('summary', $payload);
        $this->assertArrayHasKey('groups', $payload);
        $this->assertGreaterThanOrEqual(10, $payload['summary']['total_accounts']);
        $this->assertTrue($payload['summary']['is_balanced']);
        $this->assertNotEmpty($payload['groups']);
    }

    public function test_index_filters_by_search_status_and_type(): void
    {
        $subType = ChartOfAccountSubType::query()->whereHas('accountType', fn ($q) => $q->where('name', 'Assets'))->firstOrFail();

        $account = $this->service->store([
            'name' => 'Filterable Petty Cash',
            'code' => 9300,
            'sub_type' => $subType->id,
            'is_enabled' => 0,
        ]);

        $bySearch = $this->service->index(['search' => 'Filterable Petty']);
        $this->assertContains($account->id, $this->accountIdsFromIndex($bySearch));

        $byStatus = $this->service->index(['status' => 'inactive']);
        $this->assertContains($account->id, $this->accountIdsFromIndex($byStatus));

        $byType = $this->service->index(['type' => 'asset', 'search' => 'Filterable Petty']);
        $this->assertSame([$account->id], $this->accountIdsFromIndex($byType));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<int>
     */
    private function accountIdsFromIndex(array $payload): array
    {
        $ids = [];
        foreach ($payload['groups'] as $group) {
            foreach ($group['sub_types'] as $subType) {
                foreach ($subType['accounts'] as $row) {
                    $ids[] = $row['id'];
                }
            }
        }

        return $ids;
    }

    public function test_show_returns_detailed_account_payload(): void
    {
        $account = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);
        $this->postLine($account->id, 120, 0, now()->toDateString());

        $payload = $this->service->show($account->id, date('Y-01-01'), date('Y-m-d'));

        $this->assertSame($account->id, $payload['id']);
        $this->assertSame(120.0, $payload['balance']);
        $this->assertArrayHasKey('created_at', $payload);
        $this->assertTrue($payload['has_transactions']);
    }

    public function test_store_creates_system_account(): void
    {
        $subType = ChartOfAccountSubType::query()->firstOrFail();

        $account = $this->service->store([
            'name' => 'Unit Test Account',
            'code' => 9400,
            'sub_type' => $subType->id,
            'description' => 'Created in unit test',
            'is_enabled' => 1,
        ]);

        $this->assertDatabaseHas('chart_of_accounts', [
            'id' => $account->id,
            'code' => 9400,
            'created_by' => ChartOfAccountService::SYSTEM_OWNER,
        ]);
    }

    public function test_update_changes_account_fields(): void
    {
        $subType = ChartOfAccountSubType::query()->firstOrFail();
        $account = $this->service->store([
            'name' => 'Before Update',
            'code' => 9410,
            'sub_type' => $subType->id,
        ]);

        $updated = $this->service->update($account->id, [
            'name' => 'After Update',
            'description' => 'Updated description',
            'is_enabled' => 0,
        ]);

        $this->assertSame('After Update', $updated->name);
        $this->assertSame(0, (int) $updated->is_enabled);
    }

    public function test_destroy_deletes_unused_account(): void
    {
        $subType = ChartOfAccountSubType::query()->firstOrFail();
        $account = $this->service->store([
            'name' => 'Delete Me',
            'code' => 9420,
            'sub_type' => $subType->id,
        ]);

        $this->service->destroy($account->id);

        $this->assertDatabaseMissing('chart_of_accounts', ['id' => $account->id]);
    }

    public function test_destroy_throws_when_account_has_transactions(): void
    {
        $account = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);
        $this->postLine($account->id, 10, 0);

        $this->expectException(RuntimeException::class);
        $this->service->destroy($account->id);
    }

    public function test_types_returns_nested_type_and_sub_type_structure(): void
    {
        $payload = $this->service->types();

        $this->assertNotEmpty($payload['types']);
        $this->assertArrayHasKey('type_key', $payload['types'][0]);
        $this->assertNotEmpty($payload['types'][0]['sub_types']);
    }

    public function test_suggest_next_code_increments_existing_max_code(): void
    {
        $assetsType = ChartOfAccountType::query()->where('name', 'Assets')->firstOrFail();
        $maxCode = (int) ChartOfAccount::query()->where('type', $assetsType->id)->max('code');

        $this->assertSame($maxCode + 10, $this->service->suggestNextCode($assetsType->id));
    }

    public function test_suggest_next_code_returns_default_when_type_has_no_accounts(): void
    {
        $cogsType = ChartOfAccountType::query()->where('name', 'Costs of Goods Sold')->firstOrFail();

        $this->assertSame(5000, $this->service->suggestNextCode($cogsType->id));

        $globalMax = (int) ChartOfAccount::query()
            ->where('created_by', ChartOfAccountService::SYSTEM_OWNER)
            ->max('code');

        $this->assertSame($globalMax + 10, $this->service->suggestNextCode(null));
    }

    public function test_type_key_and_name_converters(): void
    {
        $this->assertSame('asset', $this->service->typeKeyFromName('Assets'));
        $this->assertSame('cogs', $this->service->typeKeyFromName('Costs of Goods Sold'));
        $this->assertSame('Assets', $this->service->typeNameFromKey('asset'));
        $this->assertSame('Costs of Goods Sold', $this->service->typeNameFromKey('cogs'));
        $this->assertNull($this->service->typeNameFromKey('unknown'));
    }

    public function test_find_system_account_returns_only_system_owned_records(): void
    {
        $account = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);

        $found = $this->service->findSystemAccount($account->id);

        $this->assertSame($account->id, $found->id);
    }

    public function test_find_system_account_throws_for_missing_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->findSystemAccount(999999);
    }

    public function test_import_creates_and_updates_accounts_with_partial_errors(): void
    {
        $file = $this->makeSpreadsheet([
            ['Name', 'Code', 'Type Name', 'Sub Type Name', 'Is Enabled', 'Description'],
            ['Imported One', 9500, 'Assets', 'Current Asset', 1, 'First import'],
            ['Missing Type', 9501, 'Unknown Type', 'Current Asset', 1, 'Should fail'],
            ['Imported Two', 9500, 'Assets', 'Current Asset', 0, 'Updated import'],
        ]);

        $result = $this->service->import($file);

        $this->assertSame(2, $result['success_count']);
        $this->assertSame(1, $result['failure_count']);
        $this->assertDatabaseHas('chart_of_accounts', [
            'code' => 9500,
            'name' => 'Imported Two',
        ]);
    }

    public function test_import_returns_zero_counts_for_header_only_file(): void
    {
        $file = $this->makeSpreadsheet([
            ['Name', 'Code', 'Type Name', 'Sub Type Name', 'Is Enabled', 'Description'],
        ]);

        $result = $this->service->import($file);

        $this->assertSame(0, $result['success_count']);
        $this->assertSame(0, $result['failure_count']);
    }

    public function test_export_returns_downloadable_spreadsheet(): void
    {
        $response = $this->service->export(date('Y-01-01'), date('Y-m-d'));

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
    }

    public function test_sample_returns_downloadable_template(): void
    {
        $response = $this->service->sample();

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertStringContainsString('chart_of_account_sample', (string) $response->headers->get('content-disposition'));
    }

    /**
     * @param  list<list<string|int|null>>  $rows
     */
    private function makeSpreadsheet(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        if ($rows !== []) {
            $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');
        }

        $path = storage_path('app/temp/unit_coa_import_'.uniqid().'.xlsx');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'chart_of_accounts.xlsx', null, null, true);
    }
}
