<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            [
                'country' => 'United States',
                'name' => 'US Dollar',
                'symbol' => '$',
                'currency_code' => 'USD'
            ],
            [
                'country' => 'European Union',
                'name' => 'Euro',
                'symbol' => '€',
                'currency_code' => 'EUR'
            ],
            [
                'country' => 'United Kingdom',
                'name' => 'British Pound',
                'symbol' => '£',
                'currency_code' => 'GBP'
            ],
            [
                'country' => 'Japan',
                'name' => 'Japanese Yen',
                'symbol' => '¥',
                'currency_code' => 'JPY'
            ],
            [
                'country' => 'Australia',
                'name' => 'Australian Dollar',
                'symbol' => 'A$',
                'currency_code' => 'AUD'
            ],
            [
                'country' => 'United Arab Emirates',
                'name' => 'UAE Dirham',
                'symbol' => 'AED',
                'currency_code' => 'AED'
            ],
            [
                'country' => 'Sudan',
                'name' => 'Sudanese Pound',
                'symbol' => 'ج.س.',
                'currency_code' => 'SDG',
            ],
        ];

        foreach ($currencies as $currency) {
            $codeEn = $currency['currency_code'];
            Currency::updateOrCreate(
                ['currency_code->en' => $codeEn],
                [
                    'country' => $currency['country'],
                    'name' => $currency['name'],
                    'symbol' => [
                        'en' => $currency['symbol'],
                        'ar' => $currency['symbol'],
                    ],
                    'currency_code' => [
                        'en' => $codeEn,
                        'ar' => $codeEn,
                    ],
                ]
            );
        }
    }
}
