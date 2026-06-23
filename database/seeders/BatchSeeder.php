<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Merchant;
use Illuminate\Database\Seeder;

class BatchSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing merchants or create some if none exist
        $merchants = Merchant::all();
        
        if ($merchants->isEmpty()) {
            $merchants = Merchant::factory(5)->create();
        }

        // Create sample batches for each merchant
        foreach ($merchants as $merchant) {
            // Create pending batches
            Batch::factory(3)
                ->pending()
                ->create(['merchant_id' => $merchant->id]);

            // Create settled batches
            Batch::factory(2)
                ->settled()
                ->create(['merchant_id' => $merchant->id]);

            // Create failed batches
            Batch::factory(1)
                ->failed()
                ->create(['merchant_id' => $merchant->id]);
        }
    }
}
