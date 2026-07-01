<?php

namespace Database\Seeders;

use App\Models\Partner;
use App\Services\PartnerPayableAccountService;
use Illuminate\Database\Seeder;

/**
 * Ensures every partner row has a provider payable COA (2110–2199) linked via account_id.
 * Safe to re-run: allocation is idempotent when account_id is already set.
 */
class PartnerPayableAccountSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(PartnerPayableAccountService::class);

        Partner::withoutGlobalScopes()
            ->whereNull('account_id')
            ->orderBy('id')
            ->each(function (Partner $partner) use ($service) {
                $service->allocateForPartner($partner);
            });
    }
}
