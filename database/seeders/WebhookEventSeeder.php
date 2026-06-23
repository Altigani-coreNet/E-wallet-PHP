<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WebhookEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [
            // Transaction Events
            [
                'name' => 'transaction.created',
                'category' => 'Transaction',
                'version' => 'v1',
                'description' => 'Occurs when a new transaction is created',
                'is_active' => true,
            ],
            [
                'name' => 'transaction.updated',
                'category' => 'Transaction',
                'version' => 'v1',
                'description' => 'Occurs when a transaction is updated',
                'is_active' => true,
            ],
            [
                'name' => 'transaction.approved',
                'category' => 'Transaction',
                'version' => 'v1',
                'description' => 'Occurs when a transaction is approved',
                'is_active' => true,
            ],
            [
                'name' => 'transaction.declined',
                'category' => 'Transaction',
                'version' => 'v1',
                'description' => 'Occurs when a transaction is declined',
                'is_active' => true,
            ],
            [
                'name' => 'transaction.failed',
                'category' => 'Transaction',
                'version' => 'v1',
                'description' => 'Occurs when a transaction fails',
                'is_active' => true,
            ],
            [
                'name' => 'transaction.voided',
                'category' => 'Transaction',
                'version' => 'v1',
                'description' => 'Occurs when a transaction is voided',
                'is_active' => true,
            ],
            [
                'name' => 'transaction.cancelled',
                'category' => 'Transaction',
                'version' => 'v1',
                'description' => 'Occurs when a transaction is cancelled',
                'is_active' => true,
            ],

            // Refund Events
            [
                'name' => 'refund.created',
                'category' => 'Refund',
                'version' => 'v1',
                'description' => 'Occurs when a refund is created',
                'is_active' => true,
            ],
            [
                'name' => 'refund.completed',
                'category' => 'Refund',
                'version' => 'v1',
                'description' => 'Occurs when a refund is completed',
                'is_active' => true,
            ],
            [
                'name' => 'refund.failed',
                'category' => 'Refund',
                'version' => 'v1',
                'description' => 'Occurs when a refund fails',
                'is_active' => true,
            ],
            [
                'name' => 'refund.partial',
                'category' => 'Refund',
                'version' => 'v1',
                'description' => 'Occurs when a partial refund is processed',
                'is_active' => true,
            ],

            // Settlement Events
            [
                'name' => 'settlement.created',
                'category' => 'Settlement',
                'version' => 'v1',
                'description' => 'Occurs when a settlement is created',
                'is_active' => true,
            ],
            [
                'name' => 'settlement.updated',
                'category' => 'Settlement',
                'version' => 'v1',
                'description' => 'Occurs when a settlement is updated',
                'is_active' => true,
            ],
            [
                'name' => 'settlement.completed',
                'category' => 'Settlement',
                'version' => 'v1',
                'description' => 'Occurs when a settlement is completed',
                'is_active' => true,
            ],
            [
                'name' => 'settlement.failed',
                'category' => 'Settlement',
                'version' => 'v1',
                'description' => 'Occurs when a settlement fails',
                'is_active' => true,
            ],

            // Batch Events
            [
                'name' => 'batch.created',
                'category' => 'Batch',
                'version' => 'v1',
                'description' => 'Occurs when a batch is created',
                'is_active' => true,
            ],
            [
                'name' => 'batch.closed',
                'category' => 'Batch',
                'version' => 'v1',
                'description' => 'Occurs when a batch is closed',
                'is_active' => true,
            ],
            [
                'name' => 'batch.settled',
                'category' => 'Batch',
                'version' => 'v1',
                'description' => 'Occurs when a batch is settled',
                'is_active' => true,
            ],

            // Payment Events
            [
                'name' => 'payment.succeeded',
                'category' => 'Payment',
                'version' => 'v1',
                'description' => 'Occurs when a payment succeeds',
                'is_active' => true,
            ],
            [
                'name' => 'payment.failed',
                'category' => 'Payment',
                'version' => 'v1',
                'description' => 'Occurs when a payment fails',
                'is_active' => true,
            ],
            [
                'name' => 'payment.processing',
                'category' => 'Payment',
                'version' => 'v1',
                'description' => 'Occurs when a payment is being processed',
                'is_active' => true,
            ],

            // Chargeback Events
            [
                'name' => 'chargeback.created',
                'category' => 'Chargeback',
                'version' => 'v1',
                'description' => 'Occurs when a chargeback is created',
                'is_active' => true,
            ],
            [
                'name' => 'chargeback.updated',
                'category' => 'Chargeback',
                'version' => 'v1',
                'description' => 'Occurs when a chargeback is updated',
                'is_active' => true,
            ],
            [
                'name' => 'chargeback.resolved',
                'category' => 'Chargeback',
                'version' => 'v1',
                'description' => 'Occurs when a chargeback is resolved',
                'is_active' => true,
            ],

            // Dispute Events
            [
                'name' => 'dispute.created',
                'category' => 'Dispute',
                'version' => 'v1',
                'description' => 'Occurs when a dispute is created',
                'is_active' => true,
            ],
            [
                'name' => 'dispute.updated',
                'category' => 'Dispute',
                'version' => 'v1',
                'description' => 'Occurs when a dispute is updated',
                'is_active' => true,
            ],
            [
                'name' => 'dispute.closed',
                'category' => 'Dispute',
                'version' => 'v1',
                'description' => 'Occurs when a dispute is closed',
                'is_active' => true,
            ],

            // Account Events (like Stripe)
            [
                'name' => 'account.updated',
                'category' => 'Account',
                'version' => 'v2',
                'description' => 'Occurs when an account is updated',
                'is_active' => true,
            ],
            [
                'name' => 'account.balance.updated',
                'category' => 'Account',
                'version' => 'v2',
                'description' => 'Occurs when account balance is updated',
                'is_active' => true,
            ],
        ];

        foreach ($events as $event) {
            $event['created_at'] = now();
            $event['updated_at'] = now();
        }

        DB::table('webhook_events')->insert($events);
    }
}

