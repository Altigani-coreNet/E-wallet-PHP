<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Transaction;
use Illuminate\Http\Request;

interface BatchService
{
    public function createBatch(string $merchantId, array $data = []): Batch;
    public function getOrCreateDailyBatch(string $merchantId): Batch;
    public function addTransactionToBatch(Batch $batch, Transaction $transaction): bool;
    public function removeTransactionFromBatch(Batch $batch, Transaction $transaction): bool;
    public function settleBatch(Batch $batch): bool;
    public function failBatch(Batch $batch, string $reason = null): bool;
    public function updateBatchTotals(Batch $batch): void;
    public function getBatchSummary(string $merchantId, string $startDate = null, string $endDate = null): array;
    public function getBatchesByMerchant(string $merchantId, string $userId, int $perPage = 15);
    public function getPendingBatches(string $merchantId = null);
    public function getSettledBatches(string $merchantId = null);
    public function getFailedBatches(string $merchantId = null);
    public function closeBatch(Batch $batch): bool;
    public function reopenBatch(Batch $batch): bool;
    public function getBatchTransactions(Batch $batch, int $perPage = 15);
    public function exportBatchData(Batch $batch): array;
    public function getAllBatches(int $perPage = 15);
    public function getAllBatchesForDataTable(Request $request);
    public function getStatistics(): array;
}
