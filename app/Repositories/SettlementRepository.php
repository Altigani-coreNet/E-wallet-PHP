<?php

namespace App\Repositories;

use App\Models\Settlement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SettlementRepository
{
    protected $model;

    public function __construct(Settlement $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function find($id): ?Settlement
    {
        return $this->model->find($id);
    }

    public function findBySettlementNumber(string $settlementNumber): ?Settlement
    {
        return $this->model->where('settlement_number', $settlementNumber)->first();
    }

    public function create(array $data): Settlement
    {
        return $this->model->create($data);
    }

    public function update(Settlement $settlement, array $data): bool
    {
        return $settlement->update($data);
    }

    public function delete(Settlement $settlement): bool
    {
        return $settlement->delete();
    }

    public function getByBatch(int $batchId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('batch_id', $batchId)
            ->with(['batch', 'merchant'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getByMerchant(int $merchantId, int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('merchant_id', $merchantId)
            ->where('user_id', $userId)
            ->with(['batch', 'merchant'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getPendingSettlements(int $merchantId = null, int $userId = null): Collection
    {
        $query = $this->model->pending();
        
        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        return $query->with(['batch', 'merchant'])->get();
    }

    public function getSettledSettlements(int $merchantId = null, int $userId = null): Collection
    {
        $query = $this->model->settled();
        
        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        return $query->with(['batch', 'merchant'])->get();
    }

    public function getFailedSettlements(int $merchantId = null, int $userId = null): Collection
    {
        $query = $this->model->failed();
        
        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        return $query->with(['batch', 'merchant'])->get();
    }

    public function getSettlementsByDateRange(int $merchantId, int $userId, string $startDate, string $endDate): Collection
    {
        return $this->model
            ->where('merchant_id', $merchantId)
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['batch', 'merchant'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getSettlementSummary(int $merchantId, int $userId, string $startDate = null, string $endDate = null): array
    {
        $query = $this->model->where('merchant_id', $merchantId)->where('user_id', $userId);
        
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        
        $totalSettlements = $query->count();
        $pendingSettlements = $query->pending()->count();
        $settledSettlements = $query->settled()->count();
        $failedSettlements = $query->failed()->count();
        $totalAmount = $query->sum('total_amount');
        $totalTransactions = $query->sum('transaction_count');
        
        return [
            'total_settlements' => $totalSettlements,
            'pending_settlements' => $pendingSettlements,
            'settled_settlements' => $settledSettlements,
            'failed_settlements' => $failedSettlements,
            'total_amount' => $totalAmount,
            'total_transactions' => $totalTransactions,
        ];
    }

    public function getSettlementsByBatch(int $batchId): Collection
    {
        return $this->model
            ->where('batch_id', $batchId)
            ->with(['batch', 'merchant'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
