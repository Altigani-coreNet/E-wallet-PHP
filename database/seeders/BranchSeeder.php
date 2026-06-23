<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Merchant;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all merchants
        $merchants = Merchant::all();

        if ($merchants->isEmpty()) {
            $this->command->info('No merchants found. Please run MerchantSeeder first.');
            return;
        }

        // Create exactly 2 branches for each merchant
        foreach ($merchants as $merchant) {
            for ($i = 1; $i <= 2; $i++) {
                Branch::create([
                    'name' => "Branch {$i} - {$merchant->name}",
                    'address' => "Branch {$i} Address, " . $merchant->address,
                    'merchant_id' => $merchant->id,
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Branches seeded successfully!');
        $this->command->info('Created 2 branches per merchant (16 total)');
    }
}

