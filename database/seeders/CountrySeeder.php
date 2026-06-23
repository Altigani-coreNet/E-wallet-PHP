<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Currency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Prepare currencies for assignment
        $defaultCurrency = Currency::where('currency_code->en', 'USD')->first();
        $sudanCurrency = Currency::where('currency_code->en', 'SDG')->first();
        $uaeCurrency = Currency::where('currency_code->en', 'AED')->first();

        // Read the JSON file
        $jsonPath = public_path('seeder/countries.json');
        
        if (!File::exists($jsonPath)) {
            $this->command->error('Countries JSON file not found at: ' . $jsonPath);
            return;
        }

        $jsonContent = File::get($jsonPath);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('Error parsing JSON: ' . json_last_error_msg());
            return;
        }

        // Find the countries data in the JSON structure
        $countriesData = null;
        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] === 'table' && $item['name'] === 'countries') {
                $countriesData = $item['data'];
                break;
            }
        }

        if (!$countriesData) {
            $this->command->error('Countries data not found in JSON file');
            return;
        }

        $this->command->info('Starting to seed countries...');
        $bar = $this->command->getOutput()->createProgressBar(count($countriesData));
        $bar->start();

        foreach ($countriesData as $countryData) {
            // Parse the name - it might be JSON string or already array
            if (is_string($countryData['name'])) {
                $nameData = json_decode($countryData['name'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // If it's not valid JSON, use it as is
                    $nameData = $countryData['name'];
                }
            } else {
                $nameData = $countryData['name'];
            }

            // Prepare the name data - ensure it's in the correct format
            if (is_string($nameData)) {
                // If it's a simple string, convert to JSON format with English key
                $nameForDb = ['en' => $nameData];
            } elseif (is_array($nameData)) {
                // If it's already an array, use it as is
                $nameForDb = $nameData;
            } else {
                $nameForDb = ['en' => 'Unknown'];
            }

            // Determine currency for the country
            $shortNameUpper = strtoupper($countryData['short_name'] ?? '');
            $currencyId = $defaultCurrency?->id;

            if (in_array($shortNameUpper, ['SD', 'SDN', 'SUDAN'])) {
                $currencyId = $sudanCurrency?->id ?? $currencyId;
            } elseif (in_array($shortNameUpper, ['AE', 'ARE', 'UAE', 'UNITED ARAB EMIRATES'])) {
                $currencyId = $uaeCurrency?->id ?? $currencyId;
            }

            Country::updateOrCreate(
                ['short_name' => $countryData['short_name']],
                [
                    'name' => $nameForDb, // Laravel will JSON encode this automatically
                    'code' => $countryData['code'] ?? null,
                    'status' => $countryData['status'] ?? 1,
                    'old_id' => $countryData['id'] ?? null, // Store old ID for reference
                    'currency_id' => $currencyId,
                    'created_at' => $countryData['created_at'] ?? now(),
                    'updated_at' => $countryData['updated_at'] ?? now(),
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('Countries seeded successfully!');
        $this->command->info('Total countries: ' . count($countriesData));
    }
}
