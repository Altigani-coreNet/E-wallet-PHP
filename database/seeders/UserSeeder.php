<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Merchant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
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

        // Create 5 users for each merchant (excluding the owner who was already created)
        foreach ($merchants as $merchant) {
            for ($i = 1; $i <= 5; $i++) {
                User::create([
                    'name' => "User {$i} - {$merchant->name}",
                    'email' => "user{$i}@" . strtolower(str_replace(' ', '', $merchant->name)) . ".com",
                    'phone' => '+1' . rand(1000000000, 9999999999),
                    'password' => Hash::make('password123'),
                    'status' => true,
                    'merchant_id' => $merchant->id,
                ]);
            }
        }

        // Create a test user
        User::create([
            'name' => 'Test User',
            'email' => 'm.altigani@corenet-tech.com',
            'password' => Hash::make('12345678'),
            'status' => true,
        ]);

        $this->command->info('Users seeded successfully!');
    }
}
