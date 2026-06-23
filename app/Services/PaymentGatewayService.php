<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Payment Gateway Service
 * 
 * This service handles communication with external payment processors/SDKs
 * Currently mocking responses, but can be replaced with real API calls
 */
class PaymentGatewayService
{
    /**
     * Process payment through external gateway/SDK
     * 
     * @param array $transactionData
     * @param string|null $terminalId UUID from AuthService
     * @return array
     */
    public function processPayment(array $transactionData, ?string $terminalId = null): array
    {
        // Log the payment request
        Log::info('Processing payment through gateway', [
            'transaction_id' => $transactionData['transaction_id'] ?? null,
            'amount' => $transactionData['amount'] ?? null,
            'terminal_id' => $terminalId ?? $transactionData['terminal_id'] ?? null
        ]);

        try {
            // Here you would make the actual API call to your payment gateway/SDK
            // For now, we're simulating the response
            
            // Simulate API call delay
            // usleep(500000); // 0.5 seconds - uncomment if you want to simulate delay
            
            $response = $this->mockGatewayResponse($transactionData, $terminalId);
            
            // Log the response
            Log::info('Payment gateway response received', [
                'transaction_id' => $transactionData['transaction_id'] ?? null,
                'status' => $response['status'],
                'response_code' => $response['response_code']
            ]);
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error('Payment gateway error', [
                'transaction_id' => $transactionData['transaction_id'] ?? null,
                'error' => $e->getMessage()
            ]);
            
            return $this->mockDeclinedResponse($e->getMessage());
        }
    }

    /**
     * Mock successful payment gateway response
     * This simulates what a real payment gateway would return
     * 
     * @param array $transactionData
     * @param string|null $terminalId UUID from AuthService
     * @return array
     */
    private function mockGatewayResponse(array $transactionData, ?string $terminalId): array
    {
        // Simulate random approval/decline based on amount
        // In real scenario, this comes from the payment gateway
        $isApproved = $this->shouldApproveTransaction($transactionData);
        
        if ($isApproved) {
            return [
                'status' => 'captured',
                'auth_code' => $this->generateAuthCode(),
                'rrn' => $this->generateRRN(),
                'response_code' => '00',
                'response_message' => 'Transaction approved',
                'trace_no' => $this->generateTraceNumber(),
                'mid' => $this->generateMID(),
                'tid' => $this->generateTID(),
                'atc' => $this->generateATC(),
                'tvr' => $this->generateTVR(),
                'app_name' => $this->determineCardType($transactionData),
                'tsi' => $this->generateTSI(),
                'sdk' => 'SDK1.0',
                'batch_no' => $this->generateBatchNumber(),
                'payment_type' => 'mobile',
                'gateway_transaction_id' => $this->generateGatewayTransactionId(),
                'processed_at' => now()->toIso8601String(),
            ];
        } else {
            return $this->mockDeclinedResponse('Insufficient funds');
        }
    }

    /**
     * Mock declined payment response
     * 
     * @param string $reason
     * @return array
     */
    private function mockDeclinedResponse(string $reason = 'Transaction declined'): array
    {
        return [
            'status' => 'declined',
            'auth_code' => null,
            'rrn' => $this->generateRRN(),
            'response_code' => '05',
            'response_message' => $reason,
            'trace_no' => $this->generateTraceNumber(),
            'mid' => null,
            'tid' => null,
            'atc' => null,
            'tvr' => null,
            'app_name' => null,
            'tsi' => null,
            'sdk' => null,
            'batch_no' => null,
            'payment_type' => 'mobile',
            'gateway_transaction_id' => $this->generateGatewayTransactionId(),
            'processed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Determine if transaction should be approved (for mock purposes)
     * In real scenario, this decision comes from the payment gateway
     * 
     * @param array $transactionData
     * @return bool
     */
    private function shouldApproveTransaction(array $transactionData): bool
    {
        // Mock logic: approve 90% of transactions
        // Decline very high amounts or specific test cards
        $amount = $transactionData['amount'] ?? 0;
        
        // Decline if amount is over 10000
        if ($amount > 10000) {
            return false;
        }
        
        // Check for test decline card numbers
        $cardNumber = $transactionData['card_number'] ?? '';
        if (strpos($cardNumber, '0000') !== false) {
            return false;
        }
        
        // Random 90% approval rate
        return rand(1, 10) <= 9;
    }

    /**
     * Generate authorization code
     * Format: AUTH + 6 alphanumeric characters
     */
    private function generateAuthCode(): string
    {
        return 'AUTH' . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    /**
     * Generate Retrieval Reference Number (RRN)
     * Format: RRN + 10 alphanumeric characters
     */
    private function generateRRN(): string
    {
        return 'RRN' . strtoupper(substr(md5(uniqid()), 0, 10));
    }

    /**
     * Generate trace number
     * Format: TRACE + 6 digit number
     */
    private function generateTraceNumber(): string
    {
        return 'TRACE' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Merchant ID
     * Format: MID + 6 digit number
     */
    private function generateMID(): string
    {
        return 'MID' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Terminal ID
     * Format: TID + 6 digit number
     */
    private function generateTID(): string
    {
        return 'TID' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Application Transaction Counter (ATC)
     * Format: ATC + 4 digit number
     */
    private function generateATC(): string
    {
        return 'ATC' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Terminal Verification Results (TVR)
     * Format: TVR + 10 digit number
     */
    private function generateTVR(): string
    {
        return 'TVR' . str_pad(rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Transaction Status Information (TSI)
     * Format: TSI + 4 digit number
     */
    private function generateTSI(): string
    {
        return 'TSI' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate batch number
     * Format: BATCH + YYYYMMDD + 4 digit random
     */
    private function generateBatchNumber(): string
    {
        return 'BATCH' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate gateway transaction ID
     */
    private function generateGatewayTransactionId(): string
    {
        return 'GTW-' . date('YmdHis') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
    }

    /**
     * Determine card type from transaction data
     * 
     * @param array $transactionData
     * @return string
     */
    private function determineCardType(array $transactionData): string
    {
        $cardNumber = $transactionData['card_number'] ?? '';
        
        // Simple card type detection based on first digit
        if (strpos($cardNumber, '4') === 0) {
            return 'Visa Debit';
        } elseif (strpos($cardNumber, '5') === 0) {
            return 'Mastercard';
        } elseif (strpos($cardNumber, '3') === 0) {
            return 'American Express';
        } else {
            return 'Visa Debit'; // Default
        }
    }

    /**
     * Refund a transaction through the payment gateway
     * 
     * @param string $originalTransactionId
     * @param float $amount
     * @return array
     */
    public function refundTransaction(string $originalTransactionId, float $amount): array
    {
        Log::info('Processing refund through gateway', [
            'original_transaction_id' => $originalTransactionId,
            'refund_amount' => $amount
        ]);

        try {
            // Mock refund response
            return [
                'status' => 'refunded',
                'refund_id' => 'REF-' . strtoupper(substr(md5(uniqid()), 0, 10)),
                'original_transaction_id' => $originalTransactionId,
                'refund_amount' => $amount,
                'response_code' => '00',
                'response_message' => 'Refund processed successfully',
                'processed_at' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            Log::error('Refund gateway error', [
                'original_transaction_id' => $originalTransactionId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Verify transaction status with payment gateway
     * 
     * @param string $transactionId
     * @return array
     */
    public function verifyTransaction(string $transactionId): array
    {
        Log::info('Verifying transaction status with gateway', [
            'transaction_id' => $transactionId
        ]);

        // Mock verification response
        return [
            'transaction_id' => $transactionId,
            'status' => 'captured',
            'verified_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Cancel/void a transaction through the payment gateway
     * 
     * @param string $transactionId
     * @return array
     */
    public function voidTransaction(string $transactionId): array
    {
        Log::info('Voiding transaction through gateway', [
            'transaction_id' => $transactionId
        ]);

        // Mock void response
        return [
            'status' => 'voided',
            'transaction_id' => $transactionId,
            'void_id' => 'VOID-' . strtoupper(substr(md5(uniqid()), 0, 10)),
            'response_code' => '00',
            'response_message' => 'Transaction voided successfully',
            'processed_at' => now()->toIso8601String(),
        ];
    }
}

