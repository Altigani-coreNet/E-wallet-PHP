<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PosTransaction>
 */
class PosTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $transactionTypes = ['SALE', 'REFUND', 'VOID', 'AUTHORIZATION'];
        $statuses = ['PENDING', 'APPROVED', 'DECLINED', 'FAILED'];
        $currencies = ['AED', 'USD', 'EUR', 'GBP'];
        
        return [
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'currency' => $this->faker->randomElement($currencies),
            'transaction_id' => 'term-tx-' . $this->faker->dateTimeBetween('-1 year')->format('YmdHis') . '-' . $this->faker->regexify('[a-z0-9]{8}'),
            'timestamp' => $this->faker->dateTimeBetween('-1 year'),
            'transaction_type' => $this->faker->randomElement($transactionTypes),
            'payment_method_id' => PaymentMethod::factory(),
            'merchant_id' => 'MID-' . $this->faker->numberBetween(10000, 99999) . '-DXB',
            'terminal_id' => 'TID-' . $this->faker->numberBetween(10000, 99999),
            'emv_data' => $this->faker->regexify('[0-9A-F]{50}'),
            'pin_block' => $this->faker->optional()->regexify('[0-9A-F]{16}'),
            'ksn' => $this->faker->regexify('FFFF[0-9A-F]{12}E0001A'),
            'status' => $this->faker->randomElement($statuses),
            'response_data' => $this->faker->optional()->passthrough(json_encode(['code' => '00'])),
            'processed_at' => $this->faker->optional()->dateTimeBetween('-1 year'),
            'notes' => $this->faker->optional()->sentence(),
            'metadata' => $this->faker->optional()->passthrough(json_encode(['source' => 'factory'])),
        ];
    }

    /**
     * Indicate that the transaction is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'PENDING',
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate that the transaction is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'APPROVED',
            'processed_at' => $this->faker->dateTimeBetween('-1 month'),
        ]);
    }

    /**
     * Indicate that the transaction is declined.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'DECLINED',
            'processed_at' => $this->faker->dateTimeBetween('-1 month'),
        ]);
    }
} 