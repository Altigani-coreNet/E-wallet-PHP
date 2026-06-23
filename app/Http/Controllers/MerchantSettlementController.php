<?php

namespace App\Http\Controllers;

use App\Models\Settlement;
use App\Models\Merchant;
use App\Models\Transaction;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class MerchantSettlementController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $merchant = Auth::guard('external')->user()->merchant;
        
        if (!$merchant) {
            abort(403, 'Merchant not found');
        }

        $statistics = $this->getStatistics($merchant->id);
        
        return view('merchant.settlements.index', compact('statistics', 'merchant'));
    }

    public function show(Request $request, Settlement $settlement)
    {
        $merchant = Auth::guard('external')->user()->merchant;
        
        if (!$merchant || $settlement->merchant_id != $merchant->id) {
            abort(403, 'Unauthorized access to settlement');
        }

        // Load necessary relationships
        $settlement->load(['batch.transactions', 'merchant', 'currency']);

        // Return JSON for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            // Get currency symbol for settlement
            $currencySymbol = $settlement->currency_symbol ?? '$';
            
            return response()->json([
                'data' => [
                    'id' => $settlement->id,
                    'settlement_number' => $settlement->settlement_number,
                    'merchant' => $merchant ? [
                        'id' => $merchant->id,
                        'name' => $merchant->name,
                    ] : null,
                    'batch' => $settlement->batch ? [
                        'id' => $settlement->batch->id,
                        'batch_number' => $settlement->batch->batch_number,
                        'transactions' => $settlement->batch->transactions ? $settlement->batch->transactions->map(function ($transaction) use ($currencySymbol) {
                            return [
                                'id' => $transaction->id,
                                'transaction_id' => $transaction->transaction_id,
                                'amount' => $transaction->amount,
                                'status' => $transaction->status,
                                'currency_symbol' => $transaction->currency_symbol ?? $currencySymbol ?? '$',
                                'created_at' => $transaction->created_at ? $transaction->created_at->toIso8601String() : null,
                            ];
                        })->toArray() : [],
                    ] : null,
                    'status' => $settlement->status,
                    'currency' => $settlement->currency ? [
                        'id' => $settlement->currency->id,
                        'currency_code' => $settlement->currency->currency_code ?? 'USD',
                        'symbol' => $currencySymbol,
                    ] : [
                        'id' => null,
                        'currency_code' => 'USD',
                        'symbol' => $currencySymbol,
                    ],
                    'currency_symbol' => $currencySymbol,
                    'total_amount' => $settlement->total_amount,
                    'settlement_date' => $settlement->settlement_date ? Carbon::parse($settlement->settlement_date)->toIso8601String() : null,
                    'created_at' => $settlement->created_at ? $settlement->created_at->toIso8601String() : null,
                    'updated_at' => $settlement->updated_at ? $settlement->updated_at->toIso8601String() : null,
                ]
            ]);
        }   

        // Return view for web requests
        return view('merchant.settlements.show', compact('settlement', 'merchant'));
    }

    public function transactions()
    {
        $merchant = Auth::user('external')->merchant;
        
        if (!$merchant) {
            abort(403, 'Merchant not found');
        }

        // Get statistics for refunded and voided transactions
        $statistics = app(\App\Services\TransactionService::class)
            ->getStatisticsByTypeForMerchant($merchant->id, 'refunded');
        
        $voidStatistics = app(\App\Services\TransactionService::class)
            ->getStatisticsByTypeForMerchant($merchant->id, 'voided');

        // Combine statistics
        $combinedStatistics = [
            'refundTransactions' => $statistics['refundTransactions'] ?? 0,
            'refundTransactionsAmount' => $statistics['refundTransactionsAmount'] ?? 0,
            'voidTransactions' => $voidStatistics['voidTransactions'] ?? 0,
            'voidTransactionsAmount' => $voidStatistics['voidTransactionsAmount'] ?? 0,
            'totalTransactions' => ($statistics['refundTransactions'] ?? 0) + ($voidStatistics['voidTransactions'] ?? 0),
            'totalAmount' => ($statistics['refundTransactionsAmount'] ?? 0) + ($voidStatistics['voidTransactionsAmount'] ?? 0),
        ];
        
        return view('merchant.settlements.transactions', compact('combinedStatistics', 'merchant'));
    }

    public function data(Request $request)
    {
        $merchant = Auth::guard('external')->user()->merchant;
        
        if (!$merchant) {
            return response()->json(['error' => 'Merchant not found'], 403);
        }

        $query = Settlement::where('merchant_id', $merchant->id)
            ->with(['merchant', 'batch', 'currency']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('settlement_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Date filters
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        // Handle sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        // Validate sort column (whitelist allowed columns)
        $allowedSortColumns = ['settlement_id', 'batch_number', 'status', 'total_amount', 'settlement_date', 'created_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }
        
        // Validate sort order
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';
        
        // Handle special cases for sorting by related fields
        if ($sortBy === 'batch_number') {
            $query->leftJoin('batches', 'settlements.batch_id', '=', 'batches.id')
                  ->orderBy('batches.batch_number', $sortOrder)
                  ->select('settlements.*');
        } else {
            $query->orderBy('settlements.' . $sortBy, $sortOrder);
        }

        // Get total count before pagination (after joins)
        $total = $query->count();

        // Pagination
        $perPage = $request->input('per_page', 25);
        $page = $request->input('page', 1);
        
        $settlements = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();
        
        // Reload relationships if join was used
        if ($sortBy === 'batch_number') {
            $settlements->load(['merchant', 'batch', 'currency']);
        }

        // Transform data for React
        $data = $settlements->map(function ($settlement) {
            $currencySymbol = $settlement->currency_symbol ?? ($settlement->currency ? $settlement->currency->symbol : '$') ?? '$';
            return [
                'id' => $settlement->id,
                'settlement_id' => $settlement->settlement_id ?? 'N/A',
                'batch_number' => $settlement->batch->batch_number ?? 'N/A',
                'status' => $settlement->status ?? 'pending',
                'status_badge_class' => $this->getStatusBadgeClass($settlement->status ?? 'pending'),
                'currency_code' => $settlement->currency ? $settlement->currency->currency_code : 'USD',
                'currency_symbol' => $currencySymbol,
                'total_amount' => $settlement->total_amount,
                'settlement_date' => $settlement->settlement_date ? Carbon::parse($settlement->settlement_date)->format('M d, Y') : 'N/A',
                'created_at' => $settlement->created_at ? $settlement->created_at->format('M d, Y H:i:s') : 'N/A',
                'created_at_raw' => $settlement->created_at ? $settlement->created_at->toIso8601String() : null,
            ];
        });

        return response()->json([
            'data' => $data,
            'total' => $total,
            'per_page' => (int) $perPage,
            'current_page' => (int) $page,
            'last_page' => ceil($total / $perPage)
        ]);
    }

    public function transactionsData(Request $request)
    {
        $merchant = Auth::guard('external')->user()->merchant;
        
        if (!$merchant) {
            return response()->json(['error' => 'Merchant not found'], 403);
        }

        // Get refunded and voided transactions for the merchant
        $query = Transaction::where('merchant_id', $merchant->id)
            ->whereIn('status', ['refunded', 'voided'])
            ->with(['batch']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('status', $request->input('type'));
        }

        if ($request->filled('terminal_id')) {
            $query->where('terminal_id', $request->input('terminal_id'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        // Get total count before pagination
        $total = $query->count();

        // Pagination
        $perPage = $request->input('per_page', 25);
        $page = $request->input('page', 1);
        
        $transactions = $query->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Transform data for React
        $data = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'transaction_id' => $transaction->transaction_id ?? 'N/A',
                'batch_number' => $transaction->batch->batch_number ?? 'N/A',
                'payment_channel' => $transaction->payment_type ?? 'N/A',
                'type' => $transaction->type ?? 'sale',
                'type_badge_class' => $this->getTransactionTypeBadgeClass($transaction->type ?? 'sale'),
                'status' => $transaction->status ?? 'pending',
                'status_badge_class' => $this->getTransactionStatusBadgeClass($transaction->status ?? 'pending'),
                'amount' => $transaction->amount,
                'created_at' => $transaction->created_at ? $transaction->created_at->format('M d, Y H:i:s') : 'N/A',
                'created_at_raw' => $transaction->created_at ? $transaction->created_at->toIso8601String() : null,
            ];
        });

        return response()->json([
            'data' => $data,
            'total' => $total,
            'per_page' => (int) $perPage,
            'current_page' => (int) $page,
            'last_page' => ceil($total / $perPage)
        ]);
    }

    public function statistics(Request $request)
    {
        $merchant = Auth::guard('external')->user()->merchant;
        
        if (!$merchant) {
            return response()->json(['error' => 'Merchant not found'], 403);
        }

        $merchantId = $merchant->id;
        
        $total = Settlement::where('merchant_id', $merchantId)->count();
        $settled = Settlement::where('merchant_id', $merchantId)
            ->where('status', 'settled')
            ->count();
        $pending = Settlement::where('merchant_id', $merchantId)
            ->where('status', 'pending')
            ->count();
        $failed = Settlement::where('merchant_id', $merchantId)
            ->where('status', 'failed')
            ->count();

        return response()->json([
            'total' => $total,
            'settled' => $settled,
            'pending' => $pending,
            'failed' => $failed
        ]);
    }

    public function transactionsStatistics(Request $request)
    {
        $merchant = Auth::guard('external')->user()->merchant;
        
        if (!$merchant) {
            return response()->json(['error' => 'Merchant not found'], 403);
        }

        // Get statistics for refunded and voided transactions
        $statistics = app(\App\Services\TransactionService::class)
            ->getStatisticsByTypeForMerchant($merchant->id, 'refunded');
        
        $voidStatistics = app(\App\Services\TransactionService::class)
            ->getStatisticsByTypeForMerchant($merchant->id, 'voided');

        // Combine statistics
        $combinedStatistics = [
            'refundTransactions' => $statistics['refundTransactions'] ?? 0,
            'refundTransactionsAmount' => $statistics['refundTransactionsAmount'] ?? 0,
            'voidTransactions' => $voidStatistics['voidTransactions'] ?? 0,
            'voidTransactionsAmount' => $voidStatistics['voidTransactionsAmount'] ?? 0,
            'totalTransactions' => ($statistics['refundTransactions'] ?? 0) + ($voidStatistics['voidTransactions'] ?? 0),
            'totalAmount' => ($statistics['refundTransactionsAmount'] ?? 0) + ($voidStatistics['voidTransactionsAmount'] ?? 0),
        ];
        
        return response()->json($combinedStatistics);
    }

    private function getStatistics($merchantId)
    {
        $total = Settlement::where('merchant_id', $merchantId)->count();
        $settled = Settlement::where('merchant_id', $merchantId)
            ->where('status', 'settled')
            ->count();
        $pending = Settlement::where('merchant_id', $merchantId)
            ->where('status', 'pending')
            ->count();
        $failed = Settlement::where('merchant_id', $merchantId)
            ->where('status', 'failed')
            ->count();

        return [
            'total' => $total,
            'settled' => $settled,
            'pending' => $pending,
            'failed' => $failed
        ];
    }

    private function getStatusBadgeClass($status)
    {
        switch (strtolower($status)) {
            case 'settled':
                return 'badge-light-success';
            case 'pending':
                return 'badge-light-warning';
            case 'failed':
                return 'badge-light-danger';
            default:
                return 'badge-light-secondary';
        }
    }

    private function getTransactionTypeBadgeClass($type)
    {
        switch (strtolower($type)) {
            case 'sale':
                return 'badge-light-success';
            case 'refunded':
                return 'badge-light-warning';
            case 'voided':
                return 'badge-light-danger';
            default:
                return 'badge-light-secondary';
        }
    }

    private function getTransactionStatusBadgeClass($status)
    {
        switch (strtolower($status)) {
            case 'approved':
            case 'completed':
                return 'badge-light-success';
            case 'pending':
                return 'badge-light-warning';
            case 'declined':
            case 'failed':
                return 'badge-light-danger';
            default:
                return 'badge-light-secondary';
        }
    }
}
