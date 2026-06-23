<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Settlement;
use App\Models\Batch;
use App\Models\Merchant;

class SettlementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing merchants and batches
        $merchants = Merchant::all();
        $batches = Batch::all();

        if ($merchants->isEmpty() || $batches->isEmpty()) {
            $this->command->info('No merchants or batches found. Skipping settlement seeding.');
            return;
        }

        // Create settlements for each batch
        foreach ($batches as $batch) {
            // Create 1-3 settlements per batch
            $settlementCount = rand(1, 3);
            
            for ($i = 0; $i < $settlementCount; $i++) {
                $status = $this->getRandomStatus();
                
                Settlement::create([
                    'settlement_number' => $this->generateSettlementNumber($batch->merchant_id, $i + 1),
                    'batch_id' => $batch->id,
                    'merchant_id' => $batch->merchant_id,
                    'status' => $status,
                    'total_amount' => $batch->total_amount * (rand(80, 100) / 100), // Random amount variation
                    'transaction_count' => $batch->transaction_count,
                    'settled_at' => $status === 'settled' ? now()->subDays(rand(1, 30)) : null,
                    'created_at' => now()->subDays(rand(1, 30)),
                    'updated_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }

        $this->command->info('Settlements seeded successfully!');
    }

    /**
     * Generate a settlement number
     */
    private function generateSettlementNumber(int $merchantId, int $sequence): string
    {
        $prefix = 'SETTLE';
        $date = now()->subDays(rand(1, 30))->format('Ymd');
        $merchantCode = str_pad($merchantId, 4, '0', STR_PAD_LEFT);
        $sequenceCode = str_pad($sequence, 3, '0', STR_PAD_LEFT);
        
        return "{$prefix}{$date}{$merchantCode}{$sequenceCode}";
    }

    /**
     * Get random status with weighted distribution
     */
    private function getRandomStatus(): string
    {
        $statuses = [
            'settled' => 70,    // 70% chance
            'pending' => 20,    // 20% chance
            'failed' => 10,     // 10% chance
        ];

        $random = rand(1, 100);
        $cumulative = 0;

        foreach ($statuses as $status => $probability) {
            $cumulative += $probability;
            if ($random <= $cumulative) {
                return $status;
            }
        }

        return 'pending'; // fallback
    }
}
