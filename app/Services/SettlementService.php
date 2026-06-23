<?php

namespace App\Services;

use App\Models\Settlement;
use App\Models\Batch;
use App\Repositories\SettlementRepository;
use Illuminate\Support\Str;

class SettlementService
{
    protected $settlementRepository;

    public function __construct(SettlementRepository $settlementRepository)
    {
        $this->settlementRepository = $settlementRepository;
    }

    /**
     * Create a new settlement for a batch
     */
    public function createSettlementForBatch(Batch $batch, array $data = []): Settlement
    {
        $settlementData = array_merge([
            'settlement_number' => $this->generateUniqueSettlementNumber($batch->merchant_id),
            'batch_id' => $batch->id,
            'merchant_id' => $batch->merchant_id,
            'status' => 'pending',
            'total_amount' => $batch->total_amount,
            'transaction_count' => $batch->transaction_count,
            'currency' => $batch->currency,
        ], $data);

        return $this->settlementRepository->create($settlementData);
    }

    /**
     * Generate unique settlement number for a merchant
     */
    public function generateUniqueSettlementNumber(int $merchantId): string
    {
        $prefix = 'SETTLE';
        $date = now()->format('Ymd');
        $merchantCode = str_pad($merchantId, 4, '0', STR_PAD_LEFT);
        
        // Get the count of settlements for this merchant on this date
        $settlementCount = Settlement::where('merchant_id', $merchantId)
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->count();
        
        // Generate sequence number (1-based, padded to 3 digits)
        $sequence = str_pad($settlementCount + 1, 3, '0', STR_PAD_LEFT);
        
        // Format: SETTLE + YYYYMMDD + MERCHANT(4) + SEQUENCE(3)
        // Example: SETTLE2024011512340001 (SETTLE + 20240115 + 1234 + 001)
        $settlementNumber = "{$prefix}{$date}{$merchantCode}{$sequence}";
        
        // Ensure uniqueness by checking if this settlement number already exists
        while (Settlement::where('settlement_number', $settlementNumber)->exists()) {
            $settlementCount++;
            $sequence = str_pad($settlementCount + 1, 3, '0', STR_PAD_LEFT);
            $settlementNumber = "{$prefix}{$date}{$merchantCode}{$sequence}";
        }
        
        return $settlementNumber;
    }

    /**
     * Mark settlement as settled
     */
    public function markAsSettled(Settlement $settlement): bool
    {
        return $settlement->update([
            'status' => 'settled',
            'settled_at' => now(),
        ]);
    }

    /**
     * Mark settlement as failed
     */
    public function markAsFailed(Settlement $settlement): bool
    {
        return $settlement->update([
            'status' => 'failed',
        ]);
    }

    /**
     * Update settlement totals from batch
     */
    public function updateSettlementTotals(Settlement $settlement): bool
    {
        $batch = $settlement->batch;
        
        if ($batch) {
            return $settlement->update([
                'total_amount' => $batch->total_amount,
                'transaction_count' => $batch->transaction_count,
            ]);
        }
        
        return false;
    }

    /**
     * Get or create daily settlement for a batch
     */
    public function getOrCreateDailySettlement(Batch $batch): Settlement
    {
        $today = now()->format('Y-m-d');
        
        // Try to find existing pending settlement for this batch today
        $settlement = Settlement::where('batch_id', $batch->id)
            ->whereDate('created_at', $today)
            ->where('status', 'pending')
            ->first();
        
        if (!$settlement) {
            // Create new settlement for today
            $settlement = $this->createSettlementForBatch($batch);
        }
        
        return $settlement;
    }

    /**
     * Process settlement for a batch
     */
    public function processSettlement(Batch $batch): Settlement
    {
        // Check if batch can be settled
        if (!$batch->canBeSettled()) {
            throw new \Exception('Batch cannot be settled - has pending transactions');
        }

        // Get or create settlement
        $settlement = $this->getOrCreateDailySettlement($batch);
        
        // Update totals
        $this->updateSettlementTotals($settlement);
        
        // Mark as settled
        $this->markAsSettled($settlement);
        
        // Mark batch as settled
        $batch->markAsSettled();
        
        return $settlement;
    }

    /**
     * Get settlement summary for a merchant
     */
    public function getSettlementSummary(int $merchantId, string $startDate = null, string $endDate = null): array
    {
        return $this->settlementRepository->getSettlementSummary($merchantId, $startDate, $endDate);
    }

    /**
     * Get settlements by batch
     */
    public function getSettlementsByBatch(int $batchId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->settlementRepository->getSettlementsByBatch($batchId);
    }
}
