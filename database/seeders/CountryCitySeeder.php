<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CountryCitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Country and City data seeding...');

        // Load countries from SQL file
        $this->loadCountriesFromSql();

        // Load cities from SQL file
        $this->loadCitiesFromSql();

        $this->command->info('Country and City data seeding completed!');
    }

    /**
     * Load countries from SQL file
     */
    private function loadCountriesFromSql(): void
    {
        $sqlFile = database_path('sql/countries.sql');
        
        if (!File::exists($sqlFile)) {
            $this->command->warn("Countries SQL file not found at: {$sqlFile}");
            $this->command->info('Creating sample countries data...');
            $this->createSampleCountries();
            return;
        }

        try {
            $this->command->info('Loading countries from SQL file...');
            
            // Read and execute SQL file
            $sql = File::get($sqlFile);
            
            // Split SQL into individual statements
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($statement) {
                    return !empty($statement) && !preg_match('/^(--|\/\*)/', $statement);
                }
            );

            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    DB::statement($statement);
                }
            }

            $this->command->info('Countries loaded successfully!');
        } catch (\Exception $e) {
            $this->command->error("Error loading countries: " . $e->getMessage());
            Log::error("CountryCitySeeder - Countries loading error: " . $e->getMessage());
        }
    }

    /**
     * Load cities from SQL file
     */
    private function loadCitiesFromSql(): void
    {
        $sqlFile = database_path('sql/cities.sql');
        
        if (!File::exists($sqlFile)) {
            $this->command->warn("Cities SQL file not found at: {$sqlFile}");
            $this->command->info('Creating sample cities data...');
            $this->createSampleCities();
            return;
        }

        try {
            $this->command->info('Loading cities from SQL file...');
            
            // Read and execute SQL file
            $sql = File::get($sqlFile);
            
            // Split SQL into individual statements
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($statement) {
                    return !empty($statement) && !preg_match('/^(--|\/\*)/', $statement);
                }
            );

            foreach ($statements as $statement) {
                if (!empty(trim($statement))) {
                    DB::statement($statement);
                }
            }

            $this->command->info('Cities loaded successfully!');
        } catch (\Exception $e) {
            $this->command->error("Error loading cities: " . $e->getMessage());
            Log::error("CountryCitySeeder - Cities loading error: " . $e->getMessage());
        }
    }

    /**
     * Create sample countries data if SQL file doesn't exist
     */
    private function createSampleCountries(): void
    {
        $countries = [
            [
                'name' => json_encode(['en' => 'United States', 'ar' => 'الولايات المتحدة']),
                'short_name' => 'US',
                'code' => 'USA',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode(['en' => 'United Kingdom', 'ar' => 'المملكة المتحدة']),
                'short_name' => 'UK',
                'code' => 'GBR',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode(['en' => 'Canada', 'ar' => 'كندا']),
                'short_name' => 'CA',
                'code' => 'CAN',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode(['en' => 'Germany', 'ar' => 'ألمانيا']),
                'short_name' => 'DE',
                'code' => 'DEU',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode(['en' => 'France', 'ar' => 'فرنسا']),
                'short_name' => 'FR',
                'code' => 'FRA',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode(['en' => 'Italy', 'ar' => 'إيطاليا']),
                'short_name' => 'IT',
                'code' => 'ITA',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode(['en' => 'Spain', 'ar' => 'إسبانيا']),
                'short_name' => 'ES',
                'code' => 'ESP',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode(['en' => 'Australia', 'ar' => 'أستراليا']),
                'short_name' => 'AU',
                'code' => 'AUS',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode(['en' => 'Japan', 'ar' => 'اليابان']),
                'short_name' => 'JP',
                'code' => 'JPN',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode(['en' => 'China', 'ar' => 'الصين']),
                'short_name' => 'CN',
                'code' => 'CHN',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('countries')->insert($countries);
        $this->command->info('Sample countries created!');
    }

    /**
     * Create sample cities data if SQL file doesn't exist
     */
    private function createSampleCities(): void
    {
        // Get country IDs
        $usId = DB::table('countries')->where('code', 'USA')->value('id');
        $ukId = DB::table('countries')->where('code', 'GBR')->value('id');
        $caId = DB::table('countries')->where('code', 'CAN')->value('id');
        $deId = DB::table('countries')->where('code', 'DEU')->value('id');
        $frId = DB::table('countries')->where('code', 'FRA')->value('id');

        $cities = [
            // US Cities
            [
                'name' => json_encode(['en' => 'New York', 'ar' => 'نيويورك']),
                'country_id' => $usId,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode(['en' => 'Los Angeles', 'ar' => 'لوس أنجلوس']),
                'country_id' => $usId,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode(['en' => 'Chicago', 'ar' => 'شيكاغو']),
                'country_id' => $usId,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // UK Cities
            [
                'name' => json_encode(['en' => 'London', 'ar' => 'لندن']),
                'country_id' => $ukId,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode(['en' => 'Manchester', 'ar' => 'مانشستر']),
                'country_id' => $ukId,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Canada Cities
            [
                'name' => json_encode(['en' => 'Toronto', 'ar' => 'تورونتو']),
                'country_id' => $caId,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode(['en' => 'Vancouver', 'ar' => 'فانكوفر']),
                'country_id' => $caId,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Germany Cities
            [
                'name' => json_encode(['en' => 'Berlin', 'ar' => 'برلين']),
                'country_id' => $deId,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode(['en' => 'Munich', 'ar' => 'ميونخ']),
                'country_id' => $deId,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // France Cities
            [
                'name' => json_encode(['en' => 'Paris', 'ar' => 'باريس']),
                'country_id' => $frId,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => json_encode(['en' => 'Marseille', 'ar' => 'مرسيليا']),
                'country_id' => $frId,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('cities')->insert($cities);
        $this->command->info('Sample cities created!');
    }
}
