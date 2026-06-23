<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

class BatchFactory extends Factory
{
    protected $model = Batch::class;

    public function definition(): array
    {
        return [
            'batch_number' => 'BATCH' . now()->format('Ymd') . str_pad($this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'merchant_id' => Merchant::factory(),
            'status' => $this->faker->randomElement(['pending', 'settled', 'failed']),
            'total_amount' => $this->faker->randomFloat(2, 100, 10000),
            'transaction_count' => $this->faker->numberBetween(1, 100),
            'settled_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'settled_at' => null,
        ]);
    }

    public function settled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'settled',
            'settled_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'settled_at' => null,
        ]);
    }
}
