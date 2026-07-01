<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\TransactionLine;
use App\Modules\Accounting\Services\AccountBalanceService;
use App\Services\AccountingService;
use App\Services\LedgerService;
use App\Services\PartnerPayableAccountService;
use App\Services\WalletService;
use App\Support\AccountCode;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\CustomerAuthTestCase;
use Tests\Support\AdminWalletTestHelpers;

class AdminProviderSettlementTest extends CustomerAuthTestCase
{
    use AdminWalletTestHelpers;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountSeeder::class);
        app(AccountingService::class)->recordOpeningCapital(1000, 'Opening equity');
        app(WalletService::class)->createMasterWallet();
        $this->admin = Admin::factory()->active()->create();
    }

    public function test_admin_can_settle_provider_payable(): void
    {
        $partner = $this->createPartnerWithPayable();
        $this->fundPartnerPayable($partner, 100);

        $response = $this->actingAsAdminApi()->postJson(
            '/api/v2/admin/provider-settlements',
            ['partner_id' => $partner->id, 'amount' => 100, 'description' => 'Wire payout'],
            ['Idempotency-Key' => 'settle-'.uniqid()]
        );

        $response->assertOk()
            ->assertJsonPath('data.amount', 100)
            ->assertJsonPath('data.payable_balance_after', 0)
            ->assertJsonPath('data.posting_reference', LedgerService::REF_PROVIDER_SETTLEMENT);

        $this->assertTrue(app(AccountBalanceService::class)->isSystemBalanced());
    }

    public function test_settlement_exceeding_payable_is_rejected(): void
    {
        $partner = $this->createPartnerWithPayable();
        $this->fundPartnerPayable($partner, 40);

        $this->actingAsAdminApi()->postJson(
            '/api/v2/admin/provider-settlements',
            ['partner_id' => $partner->id, 'amount' => 100],
            ['Idempotency-Key' => 'settle-fail-'.uniqid()]
        )->assertStatus(422);

        $this->assertSame(0, TransactionLine::query()->where('reference', LedgerService::REF_PROVIDER_SETTLEMENT)->count());
    }

    private function createPartnerWithPayable(): \App\Models\Partner
    {
        $partner = \App\Models\Partner::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Electricity Co',
            'email' => 'elec-'.Str::random(6).'@example.com',
            'merchant_code' => 'MRC_'.Str::random(8),
            'is_active' => true,
            'status' => 'approved',
        ]);

        app(PartnerPayableAccountService::class)->allocateForPartner($partner);

        return $partner->fresh(['chartOfAccount']);
    }

    private function fundPartnerPayable(\App\Models\Partner $partner, float $amount): void
    {
        $wallet = $this->createFundedWallet(Customer::factory()->active()->create(), $amount);

        app(WalletService::class)->billPay(
            $wallet,
            $partner->chartOfAccount,
            $amount,
            0,
            'Seed payable',
            'seed-'.$partner->id
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
}
