<?php

namespace Database\Seeders;

use App\Models\PosTransaction;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PosTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing payment methods
        $paymentMethods = PaymentMethod::all();
        
        if ($paymentMethods->isEmpty()) {
            // If no payment methods exist, create some first
            $this->call(PaymentMethodSeeder::class);
            $paymentMethods = PaymentMethod::all();
        }
        
        // Create sample POS transactions
        PosTransaction::factory(50)->create([
            'payment_method_id' => $paymentMethods->random()->id,
        ]);
        
        // Create specific POS transaction for testing
        PosTransaction::create([
            'amount' => 85.00,
            'currency' => 'AED',
            'transaction_id' => 'term-tx-20250812124141-f5g6h7i8',
            'timestamp' => '2025-08-12T12:41:41+04:00',
            'transaction_type' => 'SALE',
            'payment_method_id' => $paymentMethods->first()->id,
            'merchant_id' => 'MID-12345-DXB',
            'terminal_id' => 'TID-11223',
            'emv_data' => '9F2701809F3704A5B6C7D89A032508129F02060000000008500',
            'pin_block' => null,
            'ksn' => 'FFFF9876543210E0001A',
            'status' => 'APPROVED',
            'response_data' => json_encode([
                'status' => 'APPROVED',
                'auth_code' => 'AUTH123',
                'rrn' => 'RRN987654321',
                'response_code' => '00',
                'response_message' => 'Transaction approved',
            ]),
            'processed_at' => now(),
        ]);
    }
} 