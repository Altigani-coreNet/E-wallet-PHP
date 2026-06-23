<?php

namespace Database\Factories;

use App\Models\Merchant;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 5, 500);

        return [
            'transaction_id' => Transaction::generateTransactionId(),
            'merchant_id' => Merchant::factory(),
            'amount' => $amount,
            'original_amount' => $amount,
            'refunded_amount' => null,
            'voided_amount' => null,
            'cancelled_amount' => null,
            'status' => 'pending',
            'transaction_type' => 'SALE',
            'payment_type' => 'card',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved']);
    }

    public function captured(): static
    {
        return $this->state(fn () => ['status' => 'captured']);
    }

    public function voided(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $attributes['amount'] ?? 100;

            return [
                'status' => 'voided',
                'voided_amount' => $amount,
                'amount' => 0,
            ];
        });
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
            'amount' => $attributes['amount'] ?? 50,
            'type' => 'refund',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(function (array $attributes) {
            $amount = $attributes['amount'] ?? 100;

            return [
                'status' => 'cancelled',
                'cancelled_amount' => $amount,
                'amount' => 0,
            ];
        });
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => 'failed']);
    }

    public function forMerchant(Merchant|string $merchant): static
    {
        $merchantId = $merchant instanceof Merchant ? $merchant->id : $merchant;

        return $this->state(fn () => ['merchant_id' => $merchantId]);
    }
}
