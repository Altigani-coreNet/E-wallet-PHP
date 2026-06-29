<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\TransactionLine;
use App\Models\Wallet;
use App\Modules\Accounting\Services\AccountBalanceService;
use App\Services\LedgerService;
use App\Services\WalletService;
use Database\Seeders\ChartOfAccountSeeder;
use Laravel\Passport\Passport;
use Tests\CustomerAuthTestCase;
use Tests\Support\AdminWalletTestHelpers;

class AdminWalletMoneyApiTest extends CustomerAuthTestCase
{
    use AdminWalletTestHelpers;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountSeeder::class);
        $this->admin = Admin::factory()->active()->create();
        // Pre-fund the master float so customer cash-ins can be issued from it.
        $this->createMasterWallet(100000);
    }

    public function test_admin_can_cash_in_user_wallet(): void
    {
        $customer = Customer::factory()->active()->create();
        $wallet = app(WalletService::class)->createForCustomer($customer);

        $response = $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/wallets/{$wallet->id}/cash-in",
            ['amount' => 200, 'description' => 'Station cash-in'],
            ['Idempotency-Key' => 'cash-in-'.uniqid()]
        );

        $response->assertOk()
            ->assertJsonPath('data.amount', 200)
            ->assertJsonPath('data.wallet.balance', 200)
            ->assertJsonPath('data.wallet.type', 'user')
            ->assertJsonPath('data.transaction.type', 'topup')
            ->assertJsonPath('data.posting_reference', LedgerService::REF_WALLET_CASH_IN);

        $wallet->refresh();
        $this->assertSame(200.0, (float) $wallet->balance);

        $lines = TransactionLine::query()->where('reference', LedgerService::REF_WALLET_CASH_IN)->get();
        $this->assertSame(
            round((float) $lines->sum('debit'), 2),
            round((float) $lines->sum('credit'), 2)
        );
        $this->assertTrue(app(AccountBalanceService::class)->isSystemBalanced());
    }

    public function test_admin_can_cash_out_user_wallet(): void
    {
        $wallet = $this->createFundedWallet(Customer::factory()->active()->create(), 300);

        $response = $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/wallets/{$wallet->id}/cash-out",
            ['amount' => 120, 'description' => 'Station cash-out'],
            ['Idempotency-Key' => 'cash-out-'.uniqid()]
        );

        $response->assertOk()
            ->assertJsonPath('data.amount', 120)
            ->assertJsonPath('data.wallet.balance', 180)
            ->assertJsonPath('data.transaction.direction', 'debit')
            ->assertJsonPath('data.posting_reference', LedgerService::REF_WALLET_CASH_OUT);

        $wallet->refresh();
        $this->assertSame(180.0, (float) $wallet->balance);
        $this->assertTrue(app(AccountBalanceService::class)->isSystemBalanced());
    }

    public function test_admin_cash_out_rejects_insufficient_balance(): void
    {
        $wallet = $this->createFundedWallet(Customer::factory()->active()->create(), 50);

        $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/wallets/{$wallet->id}/cash-out",
            ['amount' => 100]
        )->assertStatus(422);

        $wallet->refresh();
        $this->assertSame(50.0, (float) $wallet->balance);
    }

    public function test_admin_can_record_opening_capital(): void
    {
        $response = $this->actingAsAdminApi()->postJson(
            '/api/v2/admin/wallets/opening-capital',
            ['amount' => 1000, 'description' => 'Owner seed'],
            ['Idempotency-Key' => 'opening-'.uniqid()]
        );

        $response->assertOk()
            ->assertJsonPath('data.amount', 1000);

        $lines = TransactionLine::query()->where('reference', LedgerService::REF_OPENING_CAPITAL)->get();
        $this->assertCount(2, $lines);
        $this->assertTrue(app(AccountBalanceService::class)->isSystemBalanced());
    }

    public function test_admin_can_fund_and_defund_master_float(): void
    {
        $master = Wallet::query()->where('wallet_id', WalletService::MASTER_WALLET_ID)->firstOrFail();
        $startBalance = (float) $master->balance;

        $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/wallets/{$master->id}/cash-in",
            ['amount' => 500, 'description' => 'Fund float'],
            ['Idempotency-Key' => 'master-fund-'.uniqid()]
        )->assertOk();

        $master->refresh();
        $this->assertSame($startBalance + 500.0, (float) $master->balance);

        $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/wallets/{$master->id}/cash-out",
            ['amount' => 200, 'description' => 'Withdraw float'],
            ['Idempotency-Key' => 'master-defund-'.uniqid()]
        )->assertOk();

        $master->refresh();
        $this->assertSame($startBalance + 300.0, (float) $master->balance);
        $this->assertTrue(app(AccountBalanceService::class)->isSystemBalanced());
    }

    public function test_cash_in_idempotency_prevents_double_post(): void
    {
        $customer = Customer::factory()->active()->create();
        $wallet = app(WalletService::class)->createForCustomer($customer);
        $key = 'cash-in-idem-'.uniqid();

        $first = $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/wallets/{$wallet->id}/cash-in",
            ['amount' => 75],
            ['Idempotency-Key' => $key]
        );

        $linesAfterFirst = TransactionLine::query()->where('reference', LedgerService::REF_WALLET_CASH_IN)->count();

        $second = $this->actingAsAdminApi()->postJson(
            "/api/v2/admin/wallets/{$wallet->id}/cash-in",
            ['amount' => 75],
            ['Idempotency-Key' => $key]
        );

        $first->assertOk();
        $second->assertOk();
        $second->assertExactJson($first->json());

        $wallet->refresh();
        $this->assertSame(75.0, (float) $wallet->balance);
        // The duplicate request must not create additional ledger lines.
        $this->assertSame($linesAfterFirst, TransactionLine::query()->where('reference', LedgerService::REF_WALLET_CASH_IN)->count());
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
