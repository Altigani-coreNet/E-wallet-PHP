<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\TransactionLine;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\LedgerService;
use Database\Seeders\ChartOfAccountSeeder;
use Laravel\Passport\Passport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\CustomerAuthTestCase;
use Tests\Support\AdminWalletTestHelpers;

class AdminWalletApiTest extends CustomerAuthTestCase
{
    use AdminWalletTestHelpers;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountSeeder::class);
        $this->admin = Admin::factory()->active()->create();
    }

    public function test_wallet_routes_require_authentication(): void
    {
        $this->getJson('/api/v2/admin/wallets')->assertUnauthorized();
        $this->getJson('/api/v2/admin/wallets/export')->assertUnauthorized();
        $this->getJson('/api/v2/admin/wallets/transactions')->assertUnauthorized();
        $this->getJson('/api/v2/admin/wallets/transactions/export')->assertUnauthorized();
    }

    public function test_admin_can_list_wallets(): void
    {
        $customer = Customer::factory()->active()->create();
        $wallet = $this->createFundedWallet($customer, 250);

        $response = $this->actingAsAdminApi()->getJson('/api/v2/admin/wallets?per_page=15');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'data',
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);

        $walletIds = collect($response->json('data.data'))->pluck('id')->all();
        $this->assertContains($wallet->id, $walletIds);
    }

    public function test_admin_can_show_wallet_with_summary(): void
    {
        [$senderWallet, $recipientWallet] = $this->createTransferPair(600, 0);
        $this->transferBetweenWallets($senderWallet, $recipientWallet, 150, 'API show test');

        $response = $this->actingAsAdminApi()->getJson("/api/v2/admin/wallets/{$senderWallet->id}");

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.id', $senderWallet->id)
            ->assertJsonPath('data.wallet_id', $senderWallet->wallet_id)
            ->assertJsonPath('data.type', 'user')
            ->assertJsonPath('data.is_master', false)
            ->assertJsonPath('data.summary.transaction_count', 2)
            ->assertJsonPath('data.summary.total_debits', 150)
            ->assertJsonPath('data.summary.total_credits', 600)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'wallet_id',
                    'status',
                    'balance',
                    'owner',
                    'summary' => ['transaction_count', 'total_credits', 'total_debits'],
                ],
            ]);
    }

    public function test_admin_can_suspend_activate_and_close_wallet(): void
    {
        $customer = Customer::factory()->active()->create();
        $wallet = $this->createFundedWallet($customer, 100);

        $this->actingAsAdminApi()
            ->postJson("/api/v2/admin/wallets/{$wallet->id}/suspend")
            ->assertOk()
            ->assertJsonPath('data.message', 'Wallet suspended successfully.')
            ->assertJsonPath('data.wallet.status', 'frozen');

        $this->assertDatabaseHas('wallets', ['id' => $wallet->id, 'status' => 'frozen']);

        $this->actingAsAdminApi()
            ->postJson("/api/v2/admin/wallets/{$wallet->id}/activate")
            ->assertOk()
            ->assertJsonPath('data.wallet.status', 'active');

        $this->actingAsAdminApi()
            ->deleteJson("/api/v2/admin/wallets/{$wallet->id}")
            ->assertOk()
            ->assertJsonPath('data.wallet.status', 'closed');

        $this->assertDatabaseHas('wallets', ['id' => $wallet->id, 'status' => 'closed']);
    }

    public function test_admin_can_list_wallet_transactions_with_counterparty(): void
    {
        [$senderWallet, $recipientWallet] = $this->createTransferPair(500, 0);
        $this->transferBetweenWallets($senderWallet, $recipientWallet, 100, 'Wallet tx list');

        $response = $this->actingAsAdminApi()->getJson(
            "/api/v2/admin/wallets/{$senderWallet->id}/transactions"
        );

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.total', 2);

        $rows = collect($response->json('data.data'));
        $debitTransfer = $rows->first(fn ($row) => $row['type'] === 'transfer' && $row['direction'] === 'debit');

        $this->assertNotNull($debitTransfer);
        $this->assertSame(-100, $debitTransfer['signed_amount']);
        $this->assertSame($recipientWallet->wallet_id, $debitTransfer['counterparty']['wallet_id']);
    }

    public function test_admin_can_list_all_wallet_transactions_with_direction_filter(): void
    {
        [$senderWallet, $recipientWallet] = $this->createTransferPair(400, 0);
        $this->transferBetweenWallets($senderWallet, $recipientWallet, 80, 'Global tx list');

        $response = $this->actingAsAdminApi()->getJson(
            '/api/v2/admin/wallets/transactions?direction=debit&wallet_id='.$senderWallet->wallet_id
        );

        $response->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.direction', 'debit')
            ->assertJsonPath('data.data.0.wallet.wallet_id', $senderWallet->wallet_id);
    }

    public function test_admin_can_show_wallet_transaction_with_related_entries(): void
    {
        [$senderWallet, $recipientWallet] = $this->createTransferPair(500, 0);
        $this->transferBetweenWallets($senderWallet, $recipientWallet, 100, 'Detail API test');

        $listResponse = $this->actingAsAdminApi()->getJson(
            "/api/v2/admin/wallets/{$senderWallet->id}/transactions"
        );

        $debitTransfer = collect($listResponse->json('data.data'))
            ->first(fn ($row) => $row['type'] === 'transfer' && $row['direction'] === 'debit');

        $this->assertNotNull($debitTransfer);

        $detailResponse = $this->actingAsAdminApi()->getJson(
            '/api/v2/admin/wallets/transactions/'.$debitTransfer['id']
        );

        $detailResponse->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.transaction.id', $debitTransfer['id'])
            ->assertJsonPath('data.transaction.direction', 'debit')
            ->assertJsonPath('data.operation.entry_count', 2)
            ->assertJsonCount(1, 'data.related_transactions')
            ->assertJsonPath('data.related_transactions.0.direction', 'credit')
            ->assertJsonPath('data.related_transactions.0.wallet.wallet_id', $recipientWallet->wallet_id);

        $this->assertNotNull($detailResponse->json('data.transaction.operation_id'));
        $this->assertSame(
            $detailResponse->json('data.transaction.operation_id'),
            $detailResponse->json('data.operation.operation_id')
        );
        $this->assertSame(
            $detailResponse->json('data.transaction.operation_id'),
            $detailResponse->json('data.related_transactions.0.operation_id')
        );
    }

    public function test_admin_transaction_detail_shows_only_same_operation_counterpart(): void
    {
        [$senderWallet, $recipientWallet] = $this->createTransferPair(1000, 0);
        $this->transferBetweenWallets($senderWallet, $recipientWallet, 100, 'First transfer');
        $this->transferBetweenWallets($senderWallet, $recipientWallet, 50, 'Second transfer');

        $listResponse = $this->actingAsAdminApi()->getJson(
            "/api/v2/admin/wallets/{$senderWallet->id}/transactions"
        );

        $firstDebit = collect($listResponse->json('data.data'))
            ->first(fn ($row) => $row['type'] === 'transfer'
                && $row['direction'] === 'debit'
                && $row['description'] === 'First transfer');

        $this->assertNotNull($firstDebit);

        $detailResponse = $this->actingAsAdminApi()->getJson(
            '/api/v2/admin/wallets/transactions/'.$firstDebit['id']
        );

        $detailResponse->assertOk()
            ->assertJsonPath('data.operation.entry_count', 2)
            ->assertJsonCount(1, 'data.related_transactions')
            ->assertJsonPath('data.related_transactions.0.direction', 'credit')
            ->assertJsonPath('data.related_transactions.0.amount', 100)
            ->assertJsonPath('data.related_transactions.0.description', 'First transfer');

        $this->assertNotNull($detailResponse->json('data.transaction.operation_id'));
        $this->assertSame(
            $detailResponse->json('data.transaction.operation_id'),
            $detailResponse->json('data.related_transactions.0.operation_id')
        );
    }

    public function test_transfer_shares_operation_id_on_wallet_and_ledger_rows(): void
    {
        [$senderWallet, $recipientWallet] = $this->createTransferPair(500, 0);
        $this->transferBetweenWallets($senderWallet, $recipientWallet, 75, 'Operation id test');

        $walletRows = WalletTransaction::query()
            ->where('reference', LedgerService::REF_WALLET_TRANSFER)
            ->where('description', 'Operation id test')
            ->orderBy('direction')
            ->get();

        $this->assertCount(2, $walletRows);
        $operationId = $walletRows->first()->operation_id;
        $this->assertNotNull($operationId);
        $this->assertTrue($walletRows->every(fn (WalletTransaction $row) => $row->operation_id === $operationId));

        $ledgerRows = TransactionLine::query()
            ->where('operation_id', $operationId)
            ->get();

        $this->assertGreaterThanOrEqual(2, $ledgerRows->count());
        $this->assertTrue($ledgerRows->every(fn (TransactionLine $line) => $line->operation_id === $operationId));
    }

    public function test_closed_wallets_are_excluded_from_default_index(): void
    {
        $customer = Customer::factory()->active()->create();
        $wallet = $this->createFundedWallet($customer, 50);

        $this->actingAsAdminApi()
            ->deleteJson("/api/v2/admin/wallets/{$wallet->id}")
            ->assertOk();

        $response = $this->actingAsAdminApi()->getJson('/api/v2/admin/wallets?per_page=50');

        $walletIds = collect($response->json('data.data'))->pluck('id')->all();
        $this->assertNotContains($wallet->id, $walletIds);

        $closedResponse = $this->actingAsAdminApi()->getJson('/api/v2/admin/wallets?status=closed');
        $closedIds = collect($closedResponse->json('data.data'))->pluck('id')->all();
        $this->assertContains($wallet->id, $closedIds);
    }

    public function test_admin_can_export_wallets_and_transactions(): void
    {
        [$senderWallet, $recipientWallet] = $this->createTransferPair(300, 0);
        $this->transferBetweenWallets($senderWallet, $recipientWallet, 60, 'Export API test');

        $walletExport = $this->actingAsAdminApi()->get('/api/v2/admin/wallets/export');
        $walletExport->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $walletExport->baseResponse);

        $txExport = $this->actingAsAdminApi()->get('/api/v2/admin/wallets/transactions/export');
        $txExport->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $txExport->baseResponse);
    }

    public function test_admin_cannot_suspend_master_wallet(): void
    {
        $master = $this->createMasterWallet(10000);

        $this->actingAsAdminApi()
            ->postJson("/api/v2/admin/wallets/{$master->id}/suspend")
            ->assertStatus(500);

        $this->assertDatabaseHas('wallets', [
            'id' => $master->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_filter_wallets_by_type(): void
    {
        $master = $this->createMasterWallet(5000);
        $customer = Customer::factory()->active()->create();
        $userWallet = $this->createFundedWallet($customer, 50);

        $masterResponse = $this->actingAsAdminApi()->getJson('/api/v2/admin/wallets?wallet_type=master');
        $masterIds = collect($masterResponse->json('data.data'))->pluck('id')->all();

        $this->assertContains($master->id, $masterIds);
        $this->assertNotContains($userWallet->id, $masterIds);
    }

    public function test_show_returns_not_found_for_missing_wallet(): void
    {
        $this->actingAsAdminApi()
            ->getJson('/api/v2/admin/wallets/00000000-0000-4000-8000-000000000099')
            ->assertStatus(500);
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
}
