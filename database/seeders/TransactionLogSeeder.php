<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\TransactionLog;
use App\Models\User;
use App\Models\Admin;

class TransactionLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some existing transactions to work with
        $transactions = Transaction::limit(50)->get();
        
        if ($transactions->isEmpty()) {
            $this->command->info('No transactions found. Please run TransactionSeeder first.');
            return;
        }

        $users = User::limit(10)->get();
        $admins = Admin::limit(5)->get();
        
        $performerTypes = array_merge(
            $users->map(fn($user) => ['type' => User::class, 'id' => $user->id])->toArray(),
            $admins->map(fn($admin) => ['type' => Admin::class, 'id' => $admin->id])->toArray()
        );

        foreach ($transactions as $transaction) {
            // Create creation log
            TransactionLog::create([
                'transaction_id' => $transaction->id,
                'action' => TransactionLog::ACTIONS['created'],
                'action_type' => 'system',
                'performed_by' => null,
                'performed_by_type' => null,
                'amount_before' => null,
                'amount_after' => $transaction->amount,
                'amount_change' => null,
                'status_before' => null,
                'status_after' => $transaction->status,
                'description' => 'Transaction created',
                'metadata' => [
                    'terminal_id' => $transaction->terminal_id,
                    'merchant_id' => $transaction->merchant_id,
                ],
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
            ]);

            // Randomly add some status change logs
            if (fake()->boolean(70)) {
                $oldStatus = 'pending';
                $newStatus = fake()->randomElement(['approved', 'declined', 'failed', 'processed']);
                
                TransactionLog::create([
                    'transaction_id' => $transaction->id,
                    'action' => TransactionLog::ACTIONS['status_changed'],
                    'action_type' => 'system',
                    'performed_by' => null,
                    'performed_by_type' => null,
                    'amount_before' => null,
                    'amount_after' => null,
                    'amount_change' => null,
                    'status_before' => $oldStatus,
                    'status_after' => $newStatus,
                    'description' => "Status changed from {$oldStatus} to {$newStatus}",
                    'metadata' => [
                        'response_code' => fake()->randomNumber(3),
                        'response_message' => fake()->sentence(),
                    ],
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => fake()->userAgent(),
                ]);
            }

            // Randomly add some refund logs
            if (fake()->boolean(20) && $transaction->status === 'approved') {
                $refundAmount = fake()->randomFloat(2, 1, $transaction->amount);
                $performer = fake()->randomElement($performerTypes);
                
                TransactionLog::create([
                    'transaction_id' => $transaction->id,
                    'action' => $refundAmount == $transaction->amount ? 
                               TransactionLog::ACTIONS['refunded'] : 
                               TransactionLog::ACTIONS['partial_refunded'],
                    'action_type' => 'user',
                    'performed_by' => $performer['id'],
                    'performed_by_type' => $performer['type'],
                    'amount_before' => $transaction->amount,
                    'amount_after' => $transaction->amount - $refundAmount,
                    'amount_change' => $refundAmount,
                    'status_before' => null,
                    'status_after' => null,
                    'description' => "Refunded {$refundAmount}",
                    'metadata' => [
                        'refund_amount' => $refundAmount,
                        'reason' => fake()->randomElement(['customer request', 'duplicate charge', 'service not provided']),
                        'refund_method' => fake()->randomElement(['card', 'cash', 'credit']),
                    ],
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => fake()->userAgent(),
                ]);
            }

            // Randomly add some void logs
            if (fake()->boolean(15) && $transaction->status === 'approved') {
                $performer = fake()->randomElement($performerTypes);
                
                TransactionLog::create([
                    'transaction_id' => $transaction->id,
                    'action' => TransactionLog::ACTIONS['voided'],
                    'action_type' => 'user',
                    'performed_by' => $performer['id'],
                    'performed_by_type' => $performer['type'],
                    'amount_before' => $transaction->amount,
                    'amount_after' => 0,
                    'amount_change' => -$transaction->amount,
                    'status_before' => null,
                    'status_after' => null,
                    'description' => 'Transaction voided',
                    'metadata' => [
                        'void_amount' => $transaction->amount,
                        'reason' => fake()->randomElement(['customer cancelled', 'system error', 'duplicate transaction']),
                        'void_method' => 'system',
                    ],
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => fake()->userAgent(),
                ]);
            }

            // Randomly add some batch assignment logs
            if (fake()->boolean(40)) {
                TransactionLog::create([
                    'transaction_id' => $transaction->id,
                    'action' => TransactionLog::ACTIONS['batch_assigned'],
                    'action_type' => 'system',
                    'performed_by' => null,
                    'performed_by_type' => null,
                    'amount_before' => null,
                    'amount_after' => null,
                    'amount_change' => null,
                    'status_before' => null,
                    'status_after' => null,
                    'description' => 'Transaction assigned to batch',
                    'metadata' => [
                        'batch_id' => fake()->randomNumber(3),
                        'batch_number' => 'B' . fake()->randomNumber(6),
                    ],
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => fake()->userAgent(),
                ]);
            }
        }

        $this->command->info('TransactionLogSeeder completed successfully!');
    }
}
