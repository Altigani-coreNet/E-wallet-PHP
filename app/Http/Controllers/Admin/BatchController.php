<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Merchant;
use App\Services\BatchService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BatchController extends Controller
{
    public function __construct(public BatchService $batchService)
    {
    }


    public function index(): View
    {
        $statistics = $this->batchService->getStatistics();
        // dd($statistics);
        $merchants = Merchant::select('id', 'name')->where('is_active', true)->orderBy('name')->get();
        return view('admin.batches.index', compact('statistics', 'merchants'));
    }

    public function show(Batch $batch): View
    {
        $batch->load('transactions', 'merchant');
        return view('admin.batches.show', compact('batch'));
    }

    public function data(Request $request)
    {
        return $this->batchService->getAllBatchesForDataTable($request);
    }

    /**
     * Process settlement for a batch
     */
    public function processSettlement(Batch $batch)
    {
        try {
            // Check if batch can be settled
            if ($batch->status !== 'pending') {
                return redirect()->back()->with('error', 'Only pending batches can be processed for settlement.');
            }

            // Get captured transactions from this batch
            $capturedTransactions = $batch->transactions()
                ->where('status', 'captured')
                ->get();

            if ($capturedTransactions->isEmpty()) {
                return redirect()->back()->with('error', 'No captured transactions found in this batch to settle.');
            }

            // Simulate SDK call (since you don't have SDK right now)
            $sdkResponse = $this->simulateSettlementSDKCall($capturedTransactions);

            if ($sdkResponse['success']) {
                // Process the settlement
                $this->processSettlementSuccess($batch, $capturedTransactions);
                return redirect()->back()->with('success', 'Settlement processed successfully! ' . $sdkResponse['message']);
            } else {
                return redirect()->back()->with('error', 'Settlement failed: ' . $sdkResponse['message']);
            }

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to process settlement: ' . $e->getMessage());
        }
    }

    /**
     * Simulate SDK call for settlement
     */
    private function simulateSettlementSDKCall($transactions)
    {
        // This is a mock response - replace with actual SDK call
        $totalAmount = $transactions->sum('amount');
        $transactionIds = $transactions->pluck('transaction_id')->toArray();
        
        // Simulate processing time
        usleep(500000); // 0.5 seconds
        
        // Simulate success (90% success rate for demo)
        $isSuccess = rand(1, 100) <= 90;
        
        if ($isSuccess) {
            return [
                'success' => true,
                'message' => "Successfully processed {$transactions->count()} transactions totaling $" . number_format($totalAmount, 2),
                'transaction_ids' => $transactionIds,
                'settlement_reference' => 'SETTLE' . date('YmdHis') . rand(1000, 9999),
                'processed_at' => now()
            ];
        } else {
            return [
                'success' => false,
                'message' => 'SDK processing failed - please try again',
                'error_code' => 'SDK_001',
                'error_details' => 'Simulated SDK failure'
            ];
        }
    }

    /**
     * Process successful settlement
     */
    private function processSettlementSuccess($batch, $transactions)
    {
        // Start database transaction
        DB::beginTransaction();
        
        try {
            // 1. Mark all captured transactions as approved
            $transactions->each(function ($transaction) {
                $transaction->update([
                    'status' => 'approved',
                    'processed_at' => now()
                ]);
            });

            // 2. Create settlement record
            $settlement = \App\Models\Settlement::create([
                'settlement_number' => $this->generateSettlementNumber($batch->merchant_id),
                'batch_id' => $batch->id,
                'merchant_id' => $batch->merchant_id,
                'status' => 'settled',
                'total_amount' => $transactions->sum('amount'),
                'transaction_count' => $transactions->count(),
                'settled_at' => now(),
                'country_id' => $batch->country_id,
                'currency_id' => $batch->currency_id,
            ]);

            // 3. Mark batch as settled
            $batch->update([
                'status' => 'settled',
                'settled_at' => now()
            ]);

            // 4. Update batch totals
            $batch->updateTotals();

            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate unique settlement number
     */
    private function generateSettlementNumber(int $merchantId): string
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
        
        return "{$prefix}{$date}{$merchantCode}{$sequence}";
    }

    /**
     * Export batches as CSV with current filters
     */
    public function export(Request $request)
    {
        $filters = $request->only(['search', 'status', 'merchant_id', 'country_id', 'from_date', 'to_date']);

        $query = Batch::with(['merchant', 'country']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                  ->orWhereHas('merchant', function ($merchantQuery) use ($search) {
                      $merchantQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['merchant_id'])) {
            $query->where('merchant_id', $filters['merchant_id']);
        }

        if (!empty($filters['country_id'])) {
            $query->where('country_id', $filters['country_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $filename = 'batches_' . date('Y-m-d_H-i-s') . '.csv';

        Log::info('Batch export requested', [
            'filters' => $filters,
            'user_id' => Auth::id() ?? 'guest'
        ]);

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // CSV header
            fputcsv($handle, [
                'ID',
                'Batch Number',
                'Merchant',
                'Status',
                'Total Amount',
                'Transaction Count',
                'Country',
                'Created At',
                'Settled At',
            ]);

            $query->orderBy('created_at', 'desc')->chunk(1000, function ($batches) use ($handle) {
                foreach ($batches as $batch) {
                    fputcsv($handle, [
                        $batch->id,
                        $batch->batch_number,
                        optional($batch->merchant)->name,
                        $batch->status,
                        $batch->total_amount,
                        $batch->transaction_count,
                        optional($batch->country)->name,
                        optional($batch->created_at)?->format('Y-m-d H:i:s'),
                        optional($batch->settled_at)?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
