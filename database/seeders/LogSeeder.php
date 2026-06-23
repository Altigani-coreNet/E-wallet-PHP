<?php

namespace Database\Seeders;

use App\Models\Log;
use App\Models\User;
use App\Models\Merchant;
use Illuminate\Database\Seeder;

class LogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Example: User with ID 4 updates Merchant with ID 3
        Log::create([
            'loggable_type' => Merchant::class,
            'loggable_id' => 3,
            'action' => 'update',
            'old_values' => [
                'name' => 'Old Merchant Name',
                'status' => 'inactive'
            ],
            'new_values' => [
                'name' => 'New Merchant Name',
                'status' => 'active'
            ],
            'user_id' => 4,
            'user_type' => User::class,
            'metadata' => [
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0...',
                'reason' => 'Status update requested by merchant'
            ]
        ]);
    }
}
