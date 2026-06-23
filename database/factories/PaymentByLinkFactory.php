<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Merchant;
use App\Models\PaymentByLink;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentByLink>
 */
class PaymentByLinkFactory extends Factory
{
    protected $model = PaymentByLink::class;

    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'status' => 'pending',
            'link' => fake()->url(),
            'payment_sdk' => 'stripe',
            'amount' => fake()->randomFloat(2, 10, 500),
            'currency_id' => Currency::factory(),
            'currency_code' => 'AED',
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->phoneNumber(),
            'customer_email' => fake()->safeEmail(),
            'uuid' => (string) Str::uuid(),
            'payment_status' => 'unpaid',
            'metadata' => [],
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => 'expired',
            'expired_date' => now()->subDay(),
        ]);
    }
}
