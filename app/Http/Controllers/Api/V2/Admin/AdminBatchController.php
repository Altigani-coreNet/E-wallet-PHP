<?php

namespace App\Http\Controllers\Api\V2\Admin;

use App\Http\Controllers\Controller;
use App\Services\BatchService;
use App\Models\Batch;
use App\Traits\ApiResponse;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminBatchController extends Controller
{
    use ApiResponse;

    protected $batchService;

    public function __construct(BatchService $batchService)
    {
        $this->batchService = $batchService;
    }

    /**
     * Get all batches with pagination, search, and filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Batch::query()->with(['merchant', 'transactions']);

            // Search
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('batch_number', 'like', "%{$search}%")
                      ->orWhereHas('merchant', function ($merchantQuery) use ($search) {
                          $merchantQuery->where('name', 'like', "%{$search}%")
                                      ->orWhere('business_name', 'like', "%{$search}%");
                      });
                });
            }

            // Status filter
            if ($status = $request->input('status')) {
                $query->where('status', $status);
            }

            // Merchant filter
            if ($merchantId = $request->input('merchant_id')) {
                $query->where('merchant_id', $merchantId);
            }

            // Country filter (via merchant's country)
            if ($countryId = $request->input('country_id')) {
                $query->whereHas('merchant', function ($q) use ($countryId) {
                    $q->where('country_id', $countryId);
                });
            }

            // Date range filter
            if ($dateFrom = $request->input('date_from') || $request->input('from_date')) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo = $request->input('date_to') || $request->input('to_date')) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            // Pagination
            $perPage = $request->input('per_page', 15);
            $batches = $query->latest('created_at')->paginate($perPage);

            return $this->SuccessMessage($batches);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch batches: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get batch details
     */
    public function show(int $id): JsonResponse
    {
        try {
            $batch = Batch::with([
                // 'merchant', 
                // 'transactions.user',
                'transactions.paymentMethod',
                // 'transactions.currency'
            ])->findOrFail($id);

            return $this->SuccessMessage($batch);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch batch: ' . $e->getMessage(), null, 404);
        }
    }

    /**
     * Get batch statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $statistics = $this->batchService->getStatistics();

            return $this->SuccessMessage($statistics);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch statistics: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export batches
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $query = Batch::query()->with(['merchant', 'transactions']);

            // Apply same filters as index
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('batch_number', 'like', "%{$search}%")
                      ->orWhereHas('merchant', function ($merchantQuery) use ($search) {
                          $merchantQuery->where('name', 'like', "%{$search}%")
                                      ->orWhere('business_name', 'like', "%{$search}%");
                      });
                });
            }

            if ($status = $request->input('status')) {
                $query->where('status', $status);
            }

            if ($merchantId = $request->input('merchant_id')) {
                $query->where('merchant_id', $merchantId);
            }

            if ($dateFrom = $request->input('date_from')) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo = $request->input('date_to')) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            $batches = $query->latest('created_at')->get();

            $exportData = $batches->map(function ($batch) {
                return [
                    'Batch Number' => $batch->batch_number ?? 'N/A',
                    'Merchant' => $batch->merchant->business_name ?? $batch->merchant->name ?? 'N/A',
                    'Status' => ucfirst($batch->status ?? 'pending'),
                    'Total Amount' => number_format($batch->total_amount ?? 0, 2),
                    'Transaction Count' => $batch->transaction_count ?? 0,
                    'Created At' => $batch->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return $this->SuccessMessage([
                'data' => $exportData,
                'filename' => 'admin_batches_export_' . date('Y-m-d_H-i-s') . '.csv'
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to export batches: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Process batch settlement
     */
    public function processSettlement(int $id): JsonResponse
    {
        try {
            $batch = Batch::findOrFail($id);

            // Check if batch can be settled
            if ($batch->status !== 'pending') {
                return $this->ErrorMessage('Only pending batches can be processed for settlement.', null, 400);
            }

            // Get captured transactions from this batch
            $capturedTransactions = $batch->transactions()
                ->where('status', 'captured')
                ->get();

            if ($capturedTransactions->isEmpty()) {
                return $this->ErrorMessage('No captured transactions found in this batch to settle.', null, 400);
            }

            // Process the settlement
            DB::beginTransaction();
            
            try {
                // Mark all captured transactions as approved
                $capturedTransactions->each(function ($transaction) {
                    $transaction->update([
                        'status' => 'approved',
                        'processed_at' => now()
                    ]);
                });

                // Create settlement record
                $settlement = \App\Models\Settlement::create([
                    'settlement_number' => $this->generateSettlementNumber($batch->merchant_id),
                    'batch_id' => $batch->id,
                    'merchant_id' => $batch->merchant_id,
                    'status' => 'settled',
                    'total_amount' => $capturedTransactions->sum('amount'),
                    'transaction_count' => $capturedTransactions->count(),
                    'settled_at' => now(),
                    'country_id' => $batch->country_id,
                    'currency_id' => $batch->currency_id,
                ]);

                // Mark batch as settled
                $batch->update([
                    'status' => 'settled',
                    'settled_at' => now()
                ]);

                // Update batch totals
                $batch->updateTotals();

                DB::commit();

                return $this->SuccessMessage([
                    'success' => true,
                    'message' => 'Settlement processed successfully! ' . $capturedTransactions->count() . ' transactions totaling $' . number_format($capturedTransactions->sum('amount'), 2),
                    'settlement' => $settlement
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to process settlement: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Generate unique settlement number
     */
    private function generateSettlementNumber(string $merchantId): string
    {
        $prefix = 'SETTLE';
        $date = now()->format('Ymd');
        $merchantCode = str_pad($merchantId, 4, '0', STR_PAD_LEFT);
        
        // Get the count of settlements for this merchant on this date
        $settlementCount = \App\Models\Settlement::where('merchant_id', $merchantId)
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->count();
        
        // Generate sequence number (1-based, padded to 3 digits)
        $sequence = str_pad($settlementCount + 1, 3, '0', STR_PAD_LEFT);
        
        return "{$prefix}{$date}{$sequence}";
    }
}

