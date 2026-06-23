<?php

namespace App\Repositories;

use App\Models\Batch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\BatchService;
use Illuminate\Http\Request;
use App\Models\Transaction;
use Yajra\DataTables\Facades\DataTables;

class BatchRepository implements BatchService
{
    protected $model;

    public function __construct(Batch $model)
    {
        $this->model = $model;
    }

    public function getPendingBatches(string $merchantId = null): Collection
    {
        $query = $this->model->pending();
        
        if ($merchantId) {
            $query->byMerchant($merchantId);
        }
        
        return $query->with(['merchant', 'transactions'])->get();
    }

    public function getSettledBatches(string $merchantId = null): Collection
    {
        $query = $this->model->settled();
        
        if ($merchantId) {
            $query->byMerchant($merchantId);
        }
        
        return $query->with(['merchant', 'transactions'])->get();
    }

    public function getFailedBatches(string $merchantId = null): Collection
    {
        $query = $this->model->failed();
        
        if ($merchantId) {
            $query->byMerchant($merchantId);
        }
        
        return $query->with(['merchant', 'transactions'])->get();
    }

    public function getBatchSummary(string $merchantId, string $startDate = null, string $endDate = null): array
    {
        $query = $this->model->byMerchant($merchantId);
        
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }
        
        $totalBatches = $query->count();
        $pendingBatches = $query->pending()->count();
        $settledBatches = $query->settled()->count();
        $failedBatches = $query->failed()->count();
        $totalAmount = $query->sum('total_amount');
        $totalTransactions = $query->sum('transaction_count');
        
        return [
            'total_batches' => $totalBatches,
            'pending_batches' => $pendingBatches,
            'settled_batches' => $settledBatches,
            'failed_batches' => $failedBatches,
            'total_amount' => $totalAmount,
            'total_transactions' => $totalTransactions,
        ];
    }

    public function createBatch(string $merchantId, array $data = []): Batch
    {
        throw new \Exception('Not implemented');
    }
    public function getOrCreateDailyBatch(string $merchantId): Batch
    {
        throw new \Exception('Not implemented');
    }
    public function addTransactionToBatch(Batch $batch, Transaction $transaction): bool
    {
        throw new \Exception('Not implemented');
    }
    public function removeTransactionFromBatch(Batch $batch, Transaction $transaction): bool
    {
        throw new \Exception('Not implemented');
    }
    public function settleBatch(Batch $batch): bool
    {
        throw new \Exception('Not implemented');
    }
    public function failBatch(Batch $batch, string $reason = null): bool
    {
        throw new \Exception('Not implemented');
    }
    public function updateBatchTotals(Batch $batch): void
    {
        throw new \Exception('Not implemented');
    }
    public function getBatchesByMerchant(string $merchantId, string $userId, int $perPage = 15)
    {
        $query =  $this->model->query();

        if(request()->has('search')){
            $query->where('batch_number', 'like', '%' . request()->search . '%');
            $query->orWhereHas('transactions', function ($transactionQuery) {
                $transactionQuery->where('transaction_id', 'like', '%' . request()->search . '%');
            });
        }
        if(request()->has('status')){
            $query->where('status', request()->status);
        }
        if(request()->has('start_date') && request()->has('end_date')){
            $query->whereBetween('created_at', [request()->start_date, request()->end_date]);
        }
        

        return $query->byMerchant($merchantId)
            ->where('user_id', $userId)
            ->with(['settlement'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
    public function closeBatch(Batch $batch): bool
    {
        throw new \Exception('Not implemented');
    }
    public function reopenBatch(Batch $batch): bool
    {
        throw new \Exception('Not implemented');
    }
    public function getBatchTransactions(Batch $batch, int $perPage = 15)
    {
        throw new \Exception('Not implemented');
    }
    public function exportBatchData(Batch $batch): array
    {
        throw new \Exception('Not implemented');
    }
    public function getAllBatches(int $perPage = 15)
    {
        throw new \Exception('Not implemented');
    }
    public function getAllBatchesForDataTable(Request $request)
    {
        $query = Batch::withCountry()->with(['merchant']);

        if ($request->has('search') && !is_array($request->search)) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%$search%")
                    ->orWhere('id', 'like', "$search%")
                    ->orWhereHas('merchant', function ($merchantQuery) use ($search) {
                        $merchantQuery->where('name', 'like', "%$search%");
                    });
            });
        }

        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        if ($request->has('merchant_id') && !empty($request->merchant_id)) {
            $query->where('merchant_id', $request->merchant_id);
        }

        if ($request->has('country_id') && !empty($request->country_id)) {
            $query->where('country_id', $request->country_id);
        }

        if ($request->has('from_date') && !empty($request->from_date)) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && !empty($request->to_date)) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        return DataTables::of($query)
            ->addColumn('record_select', function ($batch) {
                return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                            <input class="form-check-input record__select" type="checkbox" value="' . $batch->id . '"/>
                        </div>';
            })
            ->editColumn('merchant_id', function ($batch) {
                return $batch->merchant ? $batch->merchant->name : 'N/A';
            })
            ->editColumn('status', function ($batch) {
                $statusClass = match($batch->status) {
                    'pending' => 'badge-light-warning',
                    'settled' => 'badge-light-success',
                    'failed' => 'badge-light-danger',
                    default => 'badge-light-secondary'
                };
                return '<span class="badge ' . $statusClass . '">' . ucfirst($batch->status) . '</span>';
            })
            ->editColumn('total_amount', function ($batch) {
                return number_format($batch->total_amount, 2) . ' ' . ($batch->currency ? $batch->currency->symbol : '$');
            })
            ->editColumn('created_at', function ($batch) {
                return $batch->created_at->format('Y-m-d H:i:s');
            })
            ->editColumn('country', function ($batch) {
                return $batch->country ? $batch->country->name : 'N/A';
            })
            ->addColumn('actions', function ($batch) {
                return '<a href="' . route('admin.batches.show', $batch) . '" class="btn btn-sm btn-light-primary">View</a>';
            })
            ->rawColumns(['record_select', 'status', 'actions'])
            ->toJson();
    }
    public function getStatistics(): array
    {
        $total = Batch::withCountry()->count();
        $pending = Batch::withCountry()->where('status', Batch::STATUS_PENDING)->count();
        $settled = Batch::withCountry()->where('status', Batch::STATUS_SETTLED)->count();
        $failed = Batch::withCountry()->where('status', Batch::STATUS_FAILED)->count();

        return [
            'total' => $total,
            'pending' => $pending,
            'settled' => $settled,
            'failed' => $failed,
        ];
    }
}
