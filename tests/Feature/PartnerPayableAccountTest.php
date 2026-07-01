<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ChartOfAccount;
use App\Models\Country;
use App\Models\Partner;
use App\Models\ServiceCategory;
use App\Support\AccountCode;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\CustomerAuthTestCase;

class PartnerPayableAccountTest extends CustomerAuthTestCase
{
    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountSeeder::class);
        $this->admin = Admin::factory()->active()->create();
    }

    public function test_create_partner_allocates_payable_coa_in_transaction(): void
    {
        $country = Country::query()->create([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Sudan', 'ar' => 'Sudan'],
            'short_name' => 'SD',
            'code' => '249',
            'status' => true,
        ]);

        $partnerCategory = ServiceCategory::query()->create([
            'id' => (string) Str::uuid(),
            'type' => ServiceCategory::TYPE_PARTNER,
            'name_en' => 'Utilities',
            'name_ar' => 'Utilities',
            'code' => 'UTIL_'.Str::lower(Str::random(6)),
            'is_active' => true,
        ]);

        $email = 'new-partner-'.Str::random(6).'@example.com';

        $response = $this->actingAsAdminApi()->postJson('/api/v1/admin/partners', [
            'name' => 'Gas Utility',
            'email' => $email,
            'country_id' => $country->id,
            'partner_category_id' => $partnerCategory->id,
        ]);

        $response->assertCreated();

        $partner = Partner::query()->where('email', $email)->firstOrFail();
        $this->assertNotNull($partner->account_id);

        $payable = ChartOfAccount::query()->findOrFail($partner->account_id);
        $this->assertGreaterThanOrEqual(AccountCode::PROVIDER_PAYABLE_MIN, (int) $payable->code);
        $this->assertLessThanOrEqual(AccountCode::PROVIDER_PAYABLE_MAX, (int) $payable->code);
        $this->assertStringContainsString('Gas Utility', $payable->name);
    }

    public function test_create_sub_partner_allocates_payable_coa_in_transaction(): void
    {
        $country = Country::query()->create([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Sudan', 'ar' => 'Sudan'],
            'short_name' => 'SD',
            'code' => '249',
            'status' => true,
        ]);

        $partnerCategory = ServiceCategory::query()->create([
            'id' => (string) Str::uuid(),
            'type' => ServiceCategory::TYPE_PARTNER,
            'name_en' => 'Telecom',
            'name_ar' => 'Telecom',
            'code' => 'TEL_'.Str::lower(Str::random(6)),
            'is_active' => true,
        ]);

        $parent = Partner::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Telecom Group',
            'email' => 'parent-'.Str::random(6).'@example.com',
            'merchant_code' => 'MRC_'.Str::random(8),
            'country_id' => $country->id,
            'partner_category_id' => $partnerCategory->id,
            'is_parent' => true,
            'is_active' => true,
            'status' => 'approved',
        ]);

        $email = 'sub-partner-'.Str::random(6).'@example.com';

        $response = $this->actingAsAdminApi()->postJson(
            "/api/v1/admin/partners/{$parent->id}/sub-partners",
            [
                'name' => 'Telecom Branch A',
                'email' => $email,
            ]
        );

        $response->assertCreated();

        $subPartner = Partner::query()->where('email', $email)->firstOrFail();
        $this->assertSame($parent->id, $subPartner->parent_id);
        $this->assertNotNull($subPartner->account_id);

        $payable = ChartOfAccount::query()->findOrFail($subPartner->account_id);
        $this->assertGreaterThanOrEqual(AccountCode::PROVIDER_PAYABLE_MIN, (int) $payable->code);
        $this->assertStringContainsString('Telecom Branch A', $payable->name);
    }

    public function test_approve_partner_creates_payable_coa_in_21xx_range(): void
    {
        $country = Country::query()->create([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Sudan', 'ar' => 'Sudan'],
            'short_name' => 'SD',
            'code' => '249',
            'status' => true,
        ]);

        $partner = Partner::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Electricity Co',
            'email' => 'electricity-'.Str::random(6).'@example.com',
            'merchant_code' => 'MRC_'.Str::random(8),
            'country_id' => $country->id,
            'is_active' => false,
            'status' => 'pending',
        ]);

        $response = $this->actingAsAdminApi()->postJson("/api/v1/admin/partners/{$partner->id}/approve");

        $response->assertOk();

        $partner->refresh();
        $this->assertNotNull($partner->account_id);

        $payable = ChartOfAccount::query()->findOrFail($partner->account_id);
        $this->assertGreaterThanOrEqual(AccountCode::PROVIDER_PAYABLE_MIN, (int) $payable->code);
        $this->assertLessThanOrEqual(AccountCode::PROVIDER_PAYABLE_MAX, (int) $payable->code);
        $this->assertStringContainsString('Electricity Co', $payable->name);
    }

    public function test_allocate_is_idempotent_when_partner_already_has_account(): void
    {
        $partner = Partner::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Water Utility',
            'email' => 'water-'.Str::random(6).'@example.com',
            'merchant_code' => 'MRC_'.Str::random(8),
            'is_active' => true,
            'status' => 'approved',
        ]);

        $service = app(\App\Services\PartnerPayableAccountService::class);
        $service->allocateForPartner($partner);
        $partner->refresh();
        $firstAccountId = $partner->account_id;

        $account = $service->allocateForPartner($partner->fresh());

        $partner->refresh();
        $this->assertSame($firstAccountId, $partner->account_id);
        $this->assertSame($firstAccountId, $account->id);
    }

    public function test_telecom_topup_seeder_allocates_payable_accounts(): void
    {
        Country::query()->create([
            'id' => (string) Str::uuid(),
            'name' => ['en' => 'Sudan', 'ar' => 'Sudan'],
            'short_name' => 'SD',
            'code' => '249',
            'status' => true,
        ]);

        $this->seed(\Database\Seeders\TelecomTopupFlowSeeder::class);

        $seededEmails = [
            'sudani.topup.partner@sudani.com',
            'zain.topup.partner@example.com',
            'fastpos.partner@fastpay.com',
        ];

        foreach ($seededEmails as $email) {
            $partner = Partner::query()->where('email', $email)->first();
            $this->assertNotNull($partner, "Missing seeded partner: {$email}");
            $this->assertNotNull($partner->account_id, "Missing account_id for: {$email}");
        }
    }

    public function test_partner_payable_account_seeder_backfills_missing_accounts(): void
    {
        $partner = Partner::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Legacy Partner',
            'email' => 'legacy-'.Str::random(6).'@example.com',
            'merchant_code' => 'MRC_'.Str::random(8),
            'is_active' => true,
            'status' => 'approved',
        ]);

        $this->assertNull($partner->account_id);

        $this->seed(\Database\Seeders\PartnerPayableAccountSeeder::class);

        $partner->refresh();
        $this->assertNotNull($partner->account_id);
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
