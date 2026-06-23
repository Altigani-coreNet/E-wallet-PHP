<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample payment methods
        PaymentMethod::factory(20)->create();
        
        // Create specific payment methods for testing
        PaymentMethod::create([
            'entry_mode' => 'NFC',
            'pan_token' => 'tkn_live_xyz9876543210fedcba',
            'cardholder_name' => 'Jane Smith',
            'expiry_month' => 9,
            'expiry_year' => 2027,
            'card_type' => 'VISA',
            'card_brand' => 'DEBIT',
            'issuer_bank' => 'Emirates NBD',
            'cvv_present' => 'NO',
            'pin_present' => 'NO',
        ]);
        
        PaymentMethod::create([
            'entry_mode' => 'CHIP',
            'pan_token' => 'tkn_live_abc1234567890fedcba',
            'cardholder_name' => 'John Doe',
            'expiry_month' => 12,
            'expiry_year' => 2026,
            'card_type' => 'MASTERCARD',
            'card_brand' => 'CREDIT',
            'issuer_bank' => 'Dubai Islamic Bank',
            'cvv_present' => 'YES',
            'pin_present' => 'YES',
        ]);
    }
} 