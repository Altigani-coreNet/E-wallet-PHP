<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Read the JSON file
        $jsonPath = public_path('seeder/cities.json');
        
        if (!File::exists($jsonPath)) {
            $this->command->error('Cities JSON file not found at: ' . $jsonPath);
            return;
        }

        $jsonContent = File::get($jsonPath);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('Error parsing JSON: ' . json_last_error_msg());
            return;
        }

        // Find the cities data in the JSON structure
        $citiesData = null;
        foreach ($data as $item) {
            if (isset($item['type']) && $item['type'] === 'table' && $item['name'] === 'cities') {
                $citiesData = $item['data'];
                break;
            }
        }

        if (!$citiesData) {
            $this->command->error('Cities data not found in JSON file');
            return;
        }

        $this->command->info('Starting to seed cities...');
        $bar = $this->command->getOutput()->createProgressBar(count($citiesData));
        $bar->start();

        foreach ($citiesData as $cityData) {
            // Parse the name - it might be JSON string or already array
            if (is_string($cityData['name'])) {
                $nameData = json_decode($cityData['name'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // If it's not valid JSON, use it as is
                    $nameData = $cityData['name'];
                }
            } else {
                $nameData = $cityData['name'];
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

            // Find country by old_id mapping
            $country = \App\Models\Country::where('old_id', $cityData['country_id'])->first();
            
            City::updateOrCreate(
                [
                    'old_id' => $cityData['id'], // Use old_id for matching
                ],
                [
                    'name' => $nameForDb, // Laravel will JSON encode this automatically
                    'status' => $cityData['status'] ?? 1,
                    'country_id' => $country?->id, // Use the UUID from the country
                    'created_at' => $cityData['created_at'] ?? now(),
                    'updated_at' => $cityData['updated_at'] ?? now(),
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('Cities seeded successfully!');
        $this->command->info('Total cities: ' . count($citiesData));
    }
}
