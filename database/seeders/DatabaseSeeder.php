<?php

namespace Database\Seeders;


// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $faker = Factory::create();

        // Run basic seeders first (countries/cities before flows that need Sudan UUIDs)
        $this->call(PermissionsSeeder::class);
        $this->call(SettingSeeder::class);
        $this->call(CurrencySeeder::class);
        $this->call(ChartOfAccountSeeder::class);
        $this->call(CountrySeeder::class);
        $this->call(CitySeeder::class);
        $this->call(TelecomTopupFlowSeeder::class);
        $this->call(AdminSeeder::class);
        
        // Create 3 merchants with their owners
        $this->call(MerchantSeeder::class);

        // Demo wallet flow (master funding + customer transfers)
        $this->call(WalletSeeder::class);
        
        // // Create 5 users for each merchant (15 total users + 1 test user)
        $this->call(UserSeeder::class);
        $this->call(PlanSeeder::class);
        $this->call(SettingSeeder::class);
        
        // // Create 2 branches for each merchant (6 total branches)
        $this->call(BranchSeeder::class);
        
        // // Create 30 terminals for each merchant (90 total terminals)
        // $this->call(TerminalSeeder::class);
        
        // Create 4 terminal groups for each merchant (12 total terminal groups)
        // $this->call(TerminalGroupSeeder::class);
        
        // // Seed customers
        // $this->call(CustomerSeeder::class);
        
        // Create 4 user groups for each merchant (12 total user groups)
        // $this->call(UserGroupSeeder::class);
        
        // Create batches for each merchant
        // $this->call(BatchSeeder::class);
        
        // Create payment methods and POS transactions
        // $this->call(PaymentMethodSeeder::class);

        
        

        // $this->call(PosTransactionSeeder::class);

        $this->command->info('All seeders completed successfully!');
        $this->command->info('Created: 3 Merchants, 16 Users, 6 Branches, 90 Terminals, 12 Terminal Groups, 12 User Groups, Payment Methods, POS Transactions');
    }
}
