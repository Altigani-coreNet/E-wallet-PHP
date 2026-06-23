<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $entryModes = ['NFC', 'CHIP', 'SWIPE', 'CONTACTLESS'];
        $cardTypes = ['VISA', 'MASTERCARD', 'AMEX', 'DISCOVER'];
        $cardBrands = ['DEBIT', 'CREDIT', 'PREPAID'];
        
        return [
            'entry_mode' => $this->faker->randomElement($entryModes),
            'pan_token' => 'tkn_live_' . $this->faker->regexify('[A-Za-z0-9]{16}'),
            'cardholder_name' => $this->faker->name(),
            'expiry_month' => $this->faker->numberBetween(1, 12),
            'expiry_year' => $this->faker->numberBetween(2025, 2030),
            'card_type' => $this->faker->randomElement($cardTypes),
            'card_brand' => $this->faker->randomElement($cardBrands),
            'issuer_bank' => $this->faker->company(),
            'cvv_present' => $this->faker->randomElement(['YES', 'NO']),
            'pin_present' => $this->faker->randomElement(['YES', 'NO']),
        ];
    }
} 