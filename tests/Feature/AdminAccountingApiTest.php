<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountSubType;
use App\Models\ChartOfAccountType;
use App\Models\Customer;
use App\Services\LedgerService;
use App\Services\WalletService;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Http\UploadedFile;
use Laravel\Passport\Passport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\CustomerAuthTestCase;
use Tests\Support\AccountingTestHelpers;

class AdminAccountingApiTest extends CustomerAuthTestCase
{
    use AccountingTestHelpers;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedChartOfAccounts();
        $this->admin = Admin::factory()->active()->create();
    }

    public function test_accounting_routes_require_authentication(): void
    {
        $this->getJson('/api/v2/admin/accounting/chart-of-accounts')->assertUnauthorized();
        $this->getJson('/api/v2/admin/accounting/account-types')->assertUnauthorized();
        $this->getJson('/api/v2/admin/accounting/ledger?account_id=1')->assertUnauthorized();
        $this->getJson('/api/v2/admin/accounting/reports/balance-sheet')->assertUnauthorized();
        $this->getJson('/api/v2/admin/accounting/reports/profit-loss')->assertUnauthorized();
        $this->getJson('/api/v2/admin/accounting/reports/trial-balance')->assertUnauthorized();
    }

    public function test_admin_can_list_chart_of_accounts_with_summary(): void
    {
        $response = $this->actingAsAdminApi()
            ->getJson('/api/v2/admin/accounting/chart-of-accounts');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.summary.total_accounts', fn ($count) => $count >= 10)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'filter',
                    'summary' => [
                        'total_assets',
                        'total_liabilities',
                        'total_equity',
                        'total_accounts',
                        'is_balanced',
                    ],
                    'groups',
                ],
            ]);
    }

    public function test_admin_can_filter_chart_of_accounts_index(): void
    {
        $subType = ChartOfAccountSubType::query()->whereHas('accountType', fn ($q) => $q->where('name', 'Assets'))->firstOrFail();

        $this->actingAsAdminApi()->postJson('/api/v2/admin/accounting/chart-of-accounts', [
            'name' => 'API Filter Account',
            'code' => 9150,
            'sub_type' => $subType->id,
            'is_enabled' => false,
        ])->assertCreated();

        $this->actingAsAdminApi()
            ->getJson('/api/v2/admin/accounting/chart-of-accounts?search=API+Filter&status=inactive&type=asset')
            ->assertOk()
            ->assertJsonPath('data.filter.search', 'API Filter')
            ->assertJsonPath('data.filter.status', 'inactive')
            ->assertJsonPath('data.filter.type', 'asset');
    }

    public function test_admin_can_show_chart_of_account(): void
    {
        $account = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);
        $this->postLine($account->id, 75, 0);

        $response = $this->actingAsAdminApi()->getJson(
            "/api/v2/admin/accounting/chart-of-accounts/{$account->id}?start_date=".date('Y-01-01').'&end_date='.date('Y-m-d')
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $account->id)
            ->assertJsonPath('data.balance', 75)
            ->assertJsonPath('data.has_transactions', true);
    }

    public function test_admin_show_returns_not_found_for_missing_account(): void
    {
        $this->actingAsAdminApi()
            ->getJson('/api/v2/admin/accounting/chart-of-accounts/999999')
            ->assertStatus(404)
            ->assertJsonPath('status', false);
    }

    public function test_admin_can_create_update_and_delete_unused_chart_of_account(): void
    {
        $subType = ChartOfAccountSubType::query()->first();

        $create = $this->actingAsAdminApi()->postJson('/api/v2/admin/accounting/chart-of-accounts', [
            'name' => 'Test Suspense Account',
            'code' => 9100,
            'sub_type' => $subType->id,
            'description' => 'Temporary test account',
            'is_enabled' => true,
        ]);

        $create->assertStatus(201)
            ->assertJsonPath('data.name', 'Test Suspense Account')
            ->assertJsonPath('data.code', 9100);

        $accountId = $create->json('data.id');

        $this->actingAsAdminApi()->putJson("/api/v2/admin/accounting/chart-of-accounts/{$accountId}", [
            'name' => 'Updated Suspense Account',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Updated Suspense Account');

        $this->actingAsAdminApi()->deleteJson("/api/v2/admin/accounting/chart-of-accounts/{$accountId}")
            ->assertOk();

        $this->assertDatabaseMissing('chart_of_accounts', ['id' => $accountId]);
    }

    public function test_admin_store_validation_fails_without_required_fields(): void
    {
        $this->actingAsAdminApi()
            ->postJson('/api/v2/admin/accounting/chart-of-accounts', [])
            ->assertStatus(422)
            ->assertJsonPath('status', false);
    }

    public function test_admin_cannot_delete_account_with_transactions(): void
    {
        $bankAccount = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);
        $this->postLine($bankAccount->id, 100, 0);

        $this->actingAsAdminApi()->deleteJson("/api/v2/admin/accounting/chart-of-accounts/{$bankAccount->id}")
            ->assertStatus(422)
            ->assertJsonPath('status', false);
    }

    public function test_admin_can_get_account_types(): void
    {
        $response = $this->actingAsAdminApi()->getJson('/api/v2/admin/accounting/account-types');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'types' => [
                        ['id', 'name', 'type_key', 'sub_types'],
                    ],
                ],
            ]);
    }

    public function test_admin_can_get_next_account_code(): void
    {
        $assetsType = ChartOfAccountType::query()->where('name', 'Assets')->firstOrFail();

        $response = $this->actingAsAdminApi()->getJson(
            '/api/v2/admin/accounting/chart-of-accounts/next-code?type_id='.$assetsType->id
        );

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure(['status', 'data' => ['code']]);
    }

    public function test_ledger_summary_returns_rows_and_totals_for_all_accounts(): void
    {
        $bankAccount = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);
        $liabilityAccount = $this->accountByCode(WalletService::WALLET_LIABILITY_ACCOUNT_CODE);

        $this->postLine($bankAccount->id, 150, 0);
        $this->postLine($liabilityAccount->id, 0, 150);

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $response = $this->actingAsAdminApi()->getJson(
            "/api/v2/admin/accounting/ledger?start_date={$start}&end_date={$end}"
        );

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'filter',
                    'rows',
                    'totals' => ['debit', 'credit'],
                ],
            ])
            ->assertJsonCount(2, 'data.rows')
            ->assertJsonPath('data.totals.debit', 150)
            ->assertJsonPath('data.totals.credit', 150);
    }

    public function test_ledger_summary_filters_by_account_id(): void
    {
        $bankAccount = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);
        $liabilityAccount = $this->accountByCode(WalletService::WALLET_LIABILITY_ACCOUNT_CODE);

        $this->postLine($bankAccount->id, 150, 0);
        $this->postLine($liabilityAccount->id, 0, 150);

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $response = $this->actingAsAdminApi()->getJson(
            "/api/v2/admin/accounting/ledger?account_id={$bankAccount->id}&start_date={$start}&end_date={$end}"
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data.rows')
            ->assertJsonPath('data.rows.0.account_id', $bankAccount->id)
            ->assertJsonPath('data.totals.debit', 150);
    }

    public function test_ledger_summary_filters_by_customer_id(): void
    {
        $customer = Customer::factory()->active()->create(['name' => 'Ledger Customer']);
        $bankAccount = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);

        $this->postLine($bankAccount->id, 150, 0, null, LedgerService::REF_WALLET_TOPUP, $customer->id);
        $this->postLine($bankAccount->id, 50, 0, null, LedgerService::REF_WALLET_TOPUP, 999);

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $response = $this->actingAsAdminApi()->getJson(
            "/api/v2/admin/accounting/ledger?customer_id={$customer->id}&start_date={$start}&end_date={$end}"
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data.rows')
            ->assertJsonPath('data.rows.0.name', 'Ledger Customer')
            ->assertJsonPath('data.totals.debit', 150);
    }

    public function test_ledger_summary_filters_by_start_and_end_time(): void
    {
        $bankAccount = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);
        $today = now()->toDateString();

        $morningLine = $this->postLine($bankAccount->id, 100, 0, $today);
        $morningLine->forceFill(['created_at' => "{$today} 08:00:00"])->save();

        $afternoonLine = $this->postLine($bankAccount->id, 50, 0, $today);
        $afternoonLine->forceFill(['created_at' => "{$today} 14:00:00"])->save();

        $response = $this->actingAsAdminApi()->getJson(
            "/api/v2/admin/accounting/ledger?start_date={$today}&end_date={$today}&start_time=12:00&end_time=23:59"
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data.rows')
            ->assertJsonPath('data.totals.debit', 50)
            ->assertJsonPath('data.filter.start_time', '12:00')
            ->assertJsonPath('data.filter.end_time', '23:59');
    }

    public function test_ledger_summary_export_returns_xlsx(): void
    {
        $bankAccount = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);
        $this->postLine($bankAccount->id, 150, 0);

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $response = $this->actingAsAdminApi()->get(
            "/api/v2/admin/accounting/ledger/export?start_date={$start}&end_date={$end}"
        );

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            (string) $response->headers->get('content-type')
        );
    }

    public function test_ledger_customers_endpoint_returns_customer_list(): void
    {
        $customer = Customer::factory()->active()->create(['name' => 'Ledger Dropdown Customer']);

        $response = $this->actingAsAdminApi()->getJson('/api/v2/admin/accounting/ledger/customers');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'customers' => [
                        ['id', 'name'],
                    ],
                ],
            ]);

        $names = collect($response->json('data.customers'))->pluck('name');
        $this->assertTrue($names->contains('Ledger Dropdown Customer'));
    }

    public function test_balance_sheet_is_balanced_after_wallet_top_up(): void
    {
        $this->postBalancedWalletTopUp(500);

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $response = $this->actingAsAdminApi()->getJson(
            "/api/v2/admin/accounting/reports/balance-sheet?start_date={$start}&end_date={$end}"
        );

        $response->assertOk()
            ->assertJsonPath('data.is_balanced', true)
            ->assertJsonPath('data.totals.total_assets', 500)
            ->assertJsonPath('data.totals.total_liabilities', 500)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'filter',
                    'sections' => [
                        'assets' => ['name', 'total', 'sub_types'],
                        'liabilities' => ['name', 'total', 'sub_types'],
                        'equity' => ['name', 'total', 'sub_types', 'net_income'],
                    ],
                    'totals',
                    'is_balanced',
                ],
            ]);
    }

    public function test_balance_sheet_export_returns_xlsx(): void
    {
        $this->postBalancedWalletTopUp(500);

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $response = $this->actingAsAdminApi()->get(
            "/api/v2/admin/accounting/reports/balance-sheet/export?start_date={$start}&end_date={$end}"
        );

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            (string) $response->headers->get('content-type')
        );
    }

    public function test_profit_and_loss_returns_net_profit(): void
    {
        $incomeAccount = $this->accountByCode(4000);
        $expenseAccount = $this->accountByCode(5000);

        $this->postLine($incomeAccount->id, 0, 1000);
        $this->postLine($expenseAccount->id, 200, 0);

        $response = $this->actingAsAdminApi()->getJson(
            '/api/v2/admin/accounting/reports/profit-loss?start_date='.date('Y-01-01').'&end_date='.date('Y-m-d')
        );

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'filter',
                    'sections' => [
                        'income' => ['name', 'total', 'sub_types'],
                        'costs_of_goods_sold' => ['name', 'total', 'sub_types'],
                        'expenses' => ['name', 'total', 'sub_types'],
                    ],
                    'gross_profit',
                    'net_profit',
                ],
            ])
            ->assertJsonPath('data.sections.income.total', 1000)
            ->assertJsonPath('data.sections.expenses.total', 200)
            ->assertJsonPath('data.net_profit', 800);
    }

    public function test_profit_and_loss_export_returns_spreadsheet(): void
    {
        $incomeAccount = $this->accountByCode(4000);
        $this->postLine($incomeAccount->id, 0, 500);

        $response = $this->actingAsAdminApi()->get(
            '/api/v2/admin/accounting/reports/profit-loss/export?start_date='.date('Y-01-01').'&end_date='.date('Y-m-d')
        );

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            (string) $response->headers->get('content-type')
        );
    }

    public function test_trial_balance_api_returns_balanced_totals(): void
    {
        $bank = $this->accountByCode(WalletService::BANK_ACCOUNT_CODE);
        $liability = $this->accountByCode(WalletService::WALLET_LIABILITY_ACCOUNT_CODE);

        $this->postLine($bank->id, 400, 0);
        $this->postLine($liability->id, 0, 400);

        $response = $this->actingAsAdminApi()->getJson(
            '/api/v2/admin/accounting/reports/trial-balance?start_date='.date('Y-01-01').'&end_date='.date('Y-m-d')
        );

        $response->assertOk()
            ->assertJsonPath('data.is_balanced', true)
            ->assertJsonPath('data.total_debit', 400)
            ->assertJsonPath('data.total_credit', 400)
            ->assertJsonCount(2, 'data.rows');
    }

    public function test_import_and_export_chart_of_accounts(): void
    {
        $file = $this->makeImportSpreadsheet([
            ['Name', 'Code', 'Type Name', 'Sub Type Name', 'Is Enabled', 'Description'],
            ['Imported Account', 9200, 'Assets', 'Current Asset', 1, 'Imported via test'],
        ]);

        $import = $this->actingAsAdminApi()->post('/api/v2/admin/accounting/chart-of-accounts/import', [
            'file' => $file,
        ], ['Accept' => 'application/json']);

        $import->assertOk()
            ->assertJsonPath('data.success_count', 1);

        $this->assertDatabaseHas('chart_of_accounts', [
            'code' => 9200,
            'name' => 'Imported Account',
        ]);

        $export = $this->actingAsAdminApi()->get('/api/v2/admin/accounting/chart-of-accounts/export');
        $export->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            (string) $export->headers->get('content-type')
        );
    }

    public function test_import_validation_requires_file(): void
    {
        $this->actingAsAdminApi()
            ->postJson('/api/v2/admin/accounting/chart-of-accounts/import', [])
            ->assertStatus(422)
            ->assertJsonPath('status', false);
    }

    public function test_admin_can_download_sample_template(): void
    {
        $response = $this->actingAsAdminApi()->get('/api/v2/admin/accounting/chart-of-accounts/sample');

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            (string) $response->headers->get('content-type')
        );
    }

    private function actingAsAdminApi(): self
    {
        Passport::actingAs($this->admin, [], 'admin-api');

        return $this->withHeaders([
            'Accept' => 'application/json',
            'Accept-Language' => 'en',
            'X-App-Locale' => 'en',
        ]);
    }

    /**
     * @param  list<list<string|int>>  $rows
     */
    private function makeImportSpreadsheet(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');

        $path = storage_path('app/temp/test_coa_import.xlsx');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'chart_of_accounts.xlsx', null, null, true);
    }
}
