<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\TransactionLog;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransactionLog>
 */
class TransactionLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TransactionLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $action = $this->faker->randomElement(TransactionLog::ACTIONS);
        $actionType = $this->faker->randomElement(TransactionLog::ACTION_TYPES);
        
        // Randomly decide if this is a user or admin action
        $performerType = $this->faker->randomElement([User::class, Admin::class]);
        $performer = $performerType::factory()->create();
        
        $amountBefore = $this->faker->randomFloat(2, 10, 1000);
        $amountAfter = $this->faker->randomFloat(2, 0, $amountBefore);
        $amountChange = $amountAfter - $amountBefore;
        
        $statusBefore = $this->faker->randomElement(['pending', 'approved', 'declined', 'failed']);
        $statusAfter = $this->faker->randomElement(['pending', 'approved', 'declined', 'failed', 'processed']);
        
        return [
            'transaction_id' => Transaction::factory(),
            'action' => $action,
            'action_type' => $actionType,
            'performed_by' => $performer->id,
            'performed_by_type' => $performerType,
            'amount_before' => $amountBefore,
            'amount_after' => $amountAfter,
            'amount_change' => $amountChange,
            'status_before' => $statusBefore,
            'status_after' => $statusAfter,
            'description' => $this->faker->sentence(),
            'metadata' => [
                'reason' => $this->faker->word(),
                'ip_address' => $this->faker->ipv4(),
                'user_agent' => $this->faker->userAgent(),
            ],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    /**
     * Create a log entry for transaction creation
     */
    public function created(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => TransactionLog::ACTIONS['created'],
            'action_type' => 'system',
            'performed_by' => null,
            'performed_by_type' => null,
            'amount_before' => null,
            'status_before' => null,
            'status_after' => 'pending',
        ]);
    }

    /**
     * Create a log entry for status change
     */
    public function statusChanged(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => TransactionLog::ACTIONS['status_changed'],
            'action_type' => 'system',
            'performed_by' => null,
            'performed_by_type' => null,
            'amount_before' => null,
            'amount_after' => null,
            'amount_change' => null,
        ]);
    }

    /**
     * Create a log entry for refund
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => TransactionLog::ACTIONS['refunded'],
            'action_type' => 'user',
            'amount_change' => $this->faker->randomFloat(2, 1, 100),
        ]);
    }

    /**
     * Create a log entry for partial refund
     */
    public function partialRefunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => TransactionLog::ACTIONS['partial_refunded'],
            'action_type' => 'user',
            'amount_change' => $this->faker->randomFloat(2, 1, 100),
        ]);
    }

    /**
     * Create a log entry for void
     */
    public function voided(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => TransactionLog::ACTIONS['voided'],
            'action_type' => 'user',
            'amount_after' => 0,
            'amount_change' => -$this->faker->randomFloat(2, 10, 1000),
        ]);
    }

    /**
     * Create a log entry for cancellation
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => TransactionLog::ACTIONS['cancelled'],
            'action_type' => 'user',
            'amount_after' => 0,
            'amount_change' => -$this->faker->randomFloat(2, 10, 1000),
        ]);
    }

    /**
     * Create a log entry for batch assignment
     */
    public function batchAssigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => TransactionLog::ACTIONS['batch_assigned'],
            'action_type' => 'system',
            'performed_by' => null,
            'performed_by_type' => null,
            'amount_before' => null,
            'amount_after' => null,
            'amount_change' => null,
            'status_before' => null,
            'status_after' => null,
        ]);
    }
}
