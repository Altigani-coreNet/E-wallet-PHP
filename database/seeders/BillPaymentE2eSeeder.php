<?php

namespace Database\Seeders;

use App\Models\ProductServiceForm;
use Illuminate\Database\Seeder;

/**
 * Prepares local/E2E databases for wallet bill-payment Cypress specs.
 * Idempotent: safe to re-run before cy:run:wallet-bill.
 */
class BillPaymentE2eSeeder extends Seeder
{
    public const MOCK_PARTNER_PAY_URL = 'https://bill-mock.test/pay';

    public function run(): void
    {
        $this->call(TelecomTopupFlowSeeder::class);
        $this->call(PartnerPayableAccountSeeder::class);

        ProductServiceForm::query()
            ->where(function ($query) {
                $query->whereNull('form_url')->orWhere('form_url', '');
            })
            ->update(['form_url' => self::MOCK_PARTNER_PAY_URL]);
    }
}
