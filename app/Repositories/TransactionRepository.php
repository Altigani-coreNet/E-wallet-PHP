<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TransactionRepository
{
    protected $model;

    public function __construct(Transaction $model)
    {
        $this->model = $model;
    }

    /**
     * Create a new transaction
     */
    public function create(array $data): Transaction
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing transaction
     */
    public function update(Transaction $transaction, array $data): Transaction
    {
        $transaction->update($data);
        return $transaction;
    }

    /**
     * Delete a transaction
     */
    public function delete(Transaction $transaction): bool
    {
        return $transaction->delete();
    }

    /**
     * Find transaction by ID
     */
    public function findById(int $id): ?Transaction
    {
        return $this->model->find($id);
    }

    /**
     * Get all transactions with pagination
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['terminal', 'merchant'])
            ->latest('transaction_datetime')
            ->paginate($perPage);
    }

    /**
     * Get all transactions
     */
    public function getAll(): Collection
    {
        return $this->model->with(['terminal', 'merchant'])
            ->latest('transaction_datetime')
            ->get();
    }

    /**
     * Get transactions by status
     */
    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['terminal', 'merchant'])
            ->where('status', $status)
            ->latest('transaction_datetime')
            ->paginate($perPage);
    }

    /**
     * Get transactions by merchant
     */
    public function getByMerchant(int $merchantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['terminal', 'merchant'])
            ->where('merchant_id', $merchantId)
            ->latest('transaction_datetime')
            ->paginate($perPage);
    }

    /**
     * Get transactions by terminal
     */
    public function getByTerminal(int $terminalId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['terminal', 'merchant'])
            ->where('terminal_id', $terminalId)
            ->latest('transaction_datetime')
            ->paginate($perPage);
    }

    /**
     * Get transactions by user
     */
    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['terminal', 'merchant', 'user'])
            ->where('user_id', $userId)
            ->latest('transaction_datetime')
            ->paginate($perPage);
    }

    /**
     * Get transactions by date range
     */
    public function getByDateRange(string $startDate, string $endDate, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['terminal', 'merchant'])
            ->whereBetween('transaction_datetime', [$startDate, $endDate])
            ->latest('transaction_datetime')
            ->paginate($perPage);
    }

    /**
     * Get transactions by payment method
     */
    public function getByMethod(string $method, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['terminal', 'merchant'])
            ->where('method', $method)
            ->latest('transaction_datetime')
            ->paginate($perPage);
    }

    /**
     * Search transactions
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['terminal', 'merchant'])
            ->where(function ($q) use ($query) {
                $q->where('transaction_id', 'LIKE', "%{$query}%")
                  ->orWhere('rrn', 'LIKE', "%{$query}%")
                  ->orWhere('auth_code', 'LIKE', "%{$query}%")
                  ->orWhere('invoice_no', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%")
                  ->orWhere('card_number', 'LIKE', "%{$query}%");
            })
            ->latest('transaction_datetime')
            ->paginate($perPage);
    }

    /**
     * Get transaction statistics
     */
    public function getStatistics(): array
    {
        $baseQuery = $this->model->withCountry();
        
        // Sale transactions (approved, pending, captured)
        $saleTransactions = (clone $baseQuery)->whereIn('status', ['APPROVED', 'PENDING', 'CAPTURED'])->count();
        $saleTransactionsAmount = (clone $baseQuery)->whereIn('status', ['APPROVED', 'PENDING', 'CAPTURED'])->sum('amount');
        
        // Refund transactions
        $refundTransactions = (clone $baseQuery)->where('status', 'REFUNDED')->count();
        $refundTransactionsAmount = (clone $baseQuery)->where('status', 'REFUNDED')->sum('amount');
        
        // Void transactions
        $voidTransactions = (clone $baseQuery)->where('status', 'VOIDED')->count();
        $voidTransactionsAmount = (clone $baseQuery)->where('status', 'VOIDED')->sum('amount');
        
        // General statistics
        $totalTransactions = $baseQuery->count();
        $approvedTransactions = (clone $baseQuery)->where('status', 'APPROVED')->count();
        $declinedTransactions = (clone $baseQuery)->where('status', 'DECLINED')->count();
        $pendingTransactions = (clone $baseQuery)->where('status', 'PENDING')->count();

        return [
            // Card-specific data
            'saleTransactions' => $saleTransactions,
            'saleTransactionsAmount' => $saleTransactionsAmount,
            'refundTransactions' => $refundTransactions,
            'refundTransactionsAmount' => $refundTransactionsAmount,
            'voidTransactions' => $voidTransactions,
            'voidTransactionsAmount' => $voidTransactionsAmount,
            
            // General statistics
            'total' => $totalTransactions,
            'approved' => $approvedTransactions,
            'declined' => $declinedTransactions,
            'pending' => $pendingTransactions,
            'approval_rate' => $totalTransactions > 0 ? round(($approvedTransactions / $totalTransactions) * 100, 2) : 0,
        ];
    }

    /**
     * Get transaction statistics scoped to a merchant
     */
    public function getStatisticsForMerchant(string $merchantId): array
    {
        $baseQuery = $this->model->withCountry()->where('merchant_id', $merchantId);
        
        // Sale transactions (approved, pending, captured)
        $saleTransactions = (clone $baseQuery)->whereIn('status', ['APPROVED', 'PENDING', 'CAPTURED'])->count();
        $saleTransactionsAmount = (clone $baseQuery)->whereIn('status', ['APPROVED', 'PENDING', 'CAPTURED'])->sum('amount');
        
        // Refund transactions
        $refundTransactions = (clone $baseQuery)->where('status', 'REFUNDED')->count();
        $refundTransactionsAmount = (clone $baseQuery)->where('status', 'REFUNDED')->sum('amount');
        
        // Void transactions
        $voidTransactions = (clone $baseQuery)->where('status', 'VOIDED')->count();
        $voidTransactionsAmount = (clone $baseQuery)->where('status', 'VOIDED')->sum('amount');
        
        // General statistics
        $totalTransactions = $baseQuery->count();
        $approvedTransactions = (clone $baseQuery)->where('status', 'APPROVED')->count();
        $declinedTransactions = (clone $baseQuery)->where('status', 'DECLINED')->count();
        $pendingTransactions = (clone $baseQuery)->where('status', 'PENDING')->count();

        return [
            // Card-specific data
            'saleTransactions' => $saleTransactions,
            'saleTransactionsAmount' => $saleTransactionsAmount,
            'refundTransactions' => $refundTransactions,
            'refundTransactionsAmount' => $refundTransactionsAmount,
            'voidTransactions' => $voidTransactions,
            'voidTransactionsAmount' => $voidTransactionsAmount,
            
            // General statistics
            'total' => $totalTransactions,
            'approved' => $approvedTransactions,
            'declined' => $declinedTransactions,
            'pending' => $pendingTransactions,
            'approval_rate' => $totalTransactions > 0 ? round(($approvedTransactions / $totalTransactions) * 100, 2) : 0,
        ];
    }

    /**
     * Get transaction statistics by type
     */
    public function getStatisticsByType(string $type): array
    {
        $baseQuery = $this->model->withCountry();
        
        // Apply type-specific filtering
        switch ($type) {
            case 'refunded':
                $baseQuery->where('status', 'REFUNDED');
                break;
            case 'voided':
                $baseQuery->where('status', 'VOIDED');
                break;
            default:
                // For unknown types, return empty statistics
                return [
                    'saleTransactions' => 0,
                    'saleTransactionsAmount' => 0,
                    'refundTransactions' => 0,
                    'refundTransactionsAmount' => 0,
                    'voidTransactions' => 0,
                    'voidTransactionsAmount' => 0,
                    'total' => 0,
                    'approved' => 0,
                    'declined' => 0,
                    'pending' => 0,
                    'approval_rate' => 0,
                ];
        }
        
        // Calculate card-specific data based on filtered query
        $saleTransactions = (clone $baseQuery)->whereIn('status', ['APPROVED', 'PENDING', 'CAPTURED'])->count();
        $saleTransactionsAmount = (clone $baseQuery)->whereIn('status', ['APPROVED', 'PENDING', 'CAPTURED'])->sum('amount');
        
        $refundTransactions = (clone $baseQuery)->where('status', 'REFUNDED')->count();
        $refundTransactionsAmount = (clone $baseQuery)->where('status', 'REFUNDED')->sum('amount');
        
        $voidTransactions = (clone $baseQuery)->where('status', 'VOIDED')->count();
        $voidTransactionsAmount = (clone $baseQuery)->where('status', 'VOIDED')->sum('amount');
        
        // General statistics for the filtered type
        $totalTransactions = $baseQuery->count();
        $approvedTransactions = (clone $baseQuery)->where('status', 'APPROVED')->count();
        $declinedTransactions = (clone $baseQuery)->where('status', 'DECLINED')->count();
        $pendingTransactions = (clone $baseQuery)->where('status', 'PENDING')->count();

        return [
            // Card-specific data
            'saleTransactions' => $saleTransactions,
            'saleTransactionsAmount' => $saleTransactionsAmount,
            'refundTransactions' => $refundTransactions,
            'refundTransactionsAmount' => $refundTransactionsAmount,
            'voidTransactions' => $voidTransactions,
            'voidTransactionsAmount' => $voidTransactionsAmount,
            
            // General statistics
            'total' => $totalTransactions,
            'approved' => $approvedTransactions,
            'declined' => $declinedTransactions,
            'pending' => $pendingTransactions,
            'approval_rate' => $totalTransactions > 0 ? round(($approvedTransactions / $totalTransactions) * 100, 2) : 0,
        ];
    }

    /**
     * Get transaction statistics by type scoped to a merchant
     */
    public function getStatisticsByTypeForMerchant(string $merchantId, string $type): array
    {
        $baseQuery = $this->model->withCountry()->where('merchant_id', $merchantId);
        
        // Apply type-specific filtering
        switch ($type) {
            case 'refunded':
                $baseQuery->where('status', 'REFUNDED');
                break;
            case 'voided':
                $baseQuery->where('status', 'VOIDED');
                break;
            default:
                // For unknown types, return empty statistics
                return [
                    'saleTransactions' => 0,
                    'saleTransactionsAmount' => 0,
                    'refundTransactions' => 0,
                    'refundTransactionsAmount' => 0,
                    'voidTransactions' => 0,
                    'voidTransactionsAmount' => 0,
                    'total' => 0,
                    'approved' => 0,
                    'declined' => 0,
                    'pending' => 0,
                    'approval_rate' => 0,
                ];
        }
        
        // Calculate card-specific data based on filtered query
        $saleTransactions = (clone $baseQuery)->whereIn('status', ['APPROVED', 'PENDING', 'CAPTURED'])->count();
        $saleTransactionsAmount = (clone $baseQuery)->whereIn('status', ['APPROVED', 'PENDING', 'CAPTURED'])->sum('amount');
        
        $refundTransactions = (clone $baseQuery)->where('status', 'REFUNDED')->count();
        $refundTransactionsAmount = (clone $baseQuery)->where('status', 'REFUNDED')->sum('amount');
        
        $voidTransactions = (clone $baseQuery)->where('status', 'VOIDED')->count();
        $voidTransactionsAmount = (clone $baseQuery)->where('status', 'VOIDED')->sum('amount');
        
        // General statistics for the filtered type
        $totalTransactions = $baseQuery->count();
        $approvedTransactions = (clone $baseQuery)->where('status', 'APPROVED')->count();
        $declinedTransactions = (clone $baseQuery)->where('status', 'DECLINED')->count();
        $pendingTransactions = (clone $baseQuery)->where('status', 'PENDING')->count();

        return [
            // Card-specific data
            'saleTransactions' => $saleTransactions,
            'saleTransactionsAmount' => $saleTransactionsAmount,
            'refundTransactions' => $refundTransactions,
            'refundTransactionsAmount' => $refundTransactionsAmount,
            'voidTransactions' => $voidTransactions,
            'voidTransactionsAmount' => $voidTransactionsAmount,
            
            // General statistics
            'total' => $totalTransactions,
            'approved' => $approvedTransactions,
            'declined' => $declinedTransactions,
            'pending' => $pendingTransactions,
            'approval_rate' => $totalTransactions > 0 ? round(($approvedTransactions / $totalTransactions) * 100, 2) : 0,
        ];
    }

    /**
     * Get transactions by batch number
     */
    public function getByBatchNumber(string $batchNo): Collection
    {
        return $this->model->with(['terminal', 'merchant'])
            ->where('batch_no', $batchNo)
            ->latest('transaction_datetime')
            ->get();
    }

    /**
     * Get transactions by trace number
     */
    public function getByTraceNumber(string $traceNo): ?Transaction
    {
        return $this->model->with(['terminal', 'merchant'])
            ->where('trace_no', $traceNo)
            ->first();
    }

    /**
     * Get transactions by RRN
     */
    public function getByRRN(string $rrn): ?Transaction
    {
        return $this->model->with(['terminal', 'merchant'])
            ->where('rrn', $rrn)
            ->first();
    }

    /**
     * Get recent transactions
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model->with(['terminal', 'merchant'])
            ->latest('transaction_datetime')
            ->limit($limit)
            ->get();
    }

    /**
     * Get transactions count by status
     */
    public function getCountByStatus(): array
    {
        return [
            'approved' => $this->model->where('status', 'APPROVED')->count(),
            'declined' => $this->model->where('status', 'DECLINED')->count(),
            'pending' => $this->model->where('status', 'PENDING')->count(),
        ];
    }

    /**
     * Get transactions count by method
     */
    public function getCountByMethod(): array
    {
        return $this->model->selectRaw('method, COUNT(*) as count')
            ->groupBy('method')
            ->pluck('count', 'method')
            ->toArray();
    }
} 