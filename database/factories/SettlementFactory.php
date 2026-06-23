<?php

namespace Database\Factories;

use App\Models\Settlement;
use App\Models\Batch;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Settlement>
 */
class SettlementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Settlement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'settled', 'failed']);
        $merchant = Merchant::factory()->create();
        $batch = Batch::factory()->create(['merchant_id' => $merchant->id]);
        
        return [
            'settlement_number' => $this->generateSettlementNumber($merchant->id),
            'batch_id' => $batch->id,
            'merchant_id' => $merchant->id,
            'status' => $status,
            'total_amount' => $this->faker->randomFloat(2, 100, 10000),
            'transaction_count' => $this->faker->numberBetween(1, 50),
            'settled_at' => $status === 'settled' ? $this->faker->dateTimeBetween('-30 days', 'now') : null,
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the settlement is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'settled_at' => null,
        ]);
    }

    /**
     * Indicate that the settlement is settled.
     */
    public function settled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'settled',
            'settled_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Indicate that the settlement failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'settled_at' => null,
        ]);
    }

    /**
     * Generate a settlement number
     */
    private function generateSettlementNumber(int $merchantId): string
    {
        $prefix = 'SETTLE';
        $date = now()->format('Ymd');
        $merchantCode = str_pad($merchantId, 4, '0', STR_PAD_LEFT);
        $sequence = str_pad($this->faker->numberBetween(1, 999), 3, '0', STR_PAD_LEFT);
        
        return "{$prefix}{$date}{$merchantCode}{$sequence}";
    }
}
