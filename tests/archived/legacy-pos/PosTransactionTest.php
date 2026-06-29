<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\PaymentMethod;
use App\Models\PosTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PosTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_pos_transaction()
    {
        $transactionData = [
            'transactionDetails' => [
                'amount' => 85.00,
                'currency' => 'AED',
                'transactionId' => 'term-tx-20250812124141-f5g6h7i8',
                'timestamp' => '2025-08-12T12:41:41+04:00',
                'transactionType' => 'SALE',
            ],
            'paymentMethod' => [
                'entryMode' => 'NFC',
                'panToken' => 'tkn_live_xyz9876543210fedcba',
                'cardholderName' => 'Jane Smith',
                'expiryMonth' => 9,
                'expiryYear' => 2027,
            ],
            'merchantDetails' => [
                'merchantId' => 'MID-12345-DXB',
                'terminalId' => 'TID-11223',
            ],
            'securityData' => [
                'emvData' => '9F2701809F3704A5B6C7D89A032508129F02060000000008500',
                'pinBlock' => null,
                'ksn' => 'FFFF9876543210E0001A',
            ],
        ];

        $response = $this->postJson('/api/pos', $transactionData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'status',
                    'data' => [
                        'message',
                        'transaction' => [
                            'id',
                            'amount',
                            'currency',
                            'transaction_id',
                            'status',
                            'payment_method_id',
                        ],
                        'api_response',
                    ],
                ]);

        // Verify data was stored in database
        $this->assertDatabaseHas('payment_methods', [
            'entry_mode' => 'NFC',
            'cardholder_name' => 'Jane Smith',
            'expiry_month' => 9,
            'expiry_year' => 2027,
        ]);

        $this->assertDatabaseHas('pos_transactions', [
            'amount' => 85.00,
            'currency' => 'AED',
            'transaction_id' => 'term-tx-20250812124141-f5g6h7i8',
            'merchant_id' => 'MID-12345-DXB',
            'terminal_id' => 'TID-11223',
        ]);
    }

    public function test_can_get_pos_transactions()
    {
        // Create some test data
        $paymentMethod = PaymentMethod::factory()->create();
        PosTransaction::factory(5)->create(['payment_method_id' => $paymentMethod->id]);

        $response = $this->getJson('/api/pos/transactions');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'data' => [
                        'transactions' => [
                            'current_page',
                            'data',
                            'per_page',
                            'total',
                        ],
                    ],
                ]);
    }

    public function test_can_get_specific_pos_transaction()
    {
        $paymentMethod = PaymentMethod::factory()->create();
        $transaction = PosTransaction::factory()->create(['payment_method_id' => $paymentMethod->id]);

        $response = $this->getJson("/api/pos/transactions/{$transaction->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'status',
                    'data' => [
                        'transaction' => [
                            'id',
                            'amount',
                            'currency',
                            'transaction_id',
                            'status',
                        ],
                    ],
                ]);
    }

    public function test_validation_requires_all_required_fields()
    {
        $response = $this->postJson('/api/pos', []);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'status',
                    'message',
                    'Error_Code',
                ]);
    }
} 