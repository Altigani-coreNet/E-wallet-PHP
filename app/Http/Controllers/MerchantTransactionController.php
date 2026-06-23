<?php

namespace App\Http\Controllers;

use App\Http\Resources\MerchantTransactionDetailResource;
use App\Http\Resources\MerchantTransactionResource;
use App\Models\Transaction;
use App\Models\Merchant;
use App\Mail\TransactionReceiptMail;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Yajra\DataTables\Facades\DataTables;
// use Carbon\Carbon;

class MerchantTransactionController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $type = request()->get('type');

        $merchant = Auth::guard('external')->user()->merchant_id;
        // dd($merchant    );
        if (!$merchant) {
            abort(403, 'Merchant not found');
        }

        // Build statistics similar to admin but scoped to merchant and optional type
        if ($type) {
            $statistics = app(\App\Services\TransactionService::class)
                ->getStatisticsByTypeForMerchant($merchant, $type);
        } else {
            $statistics = app(\App\Services\TransactionService::class)
                ->getStatisticsForMerchant($merchant);
        }
        
        // Extract individual variables for the view
        $saleTransactions = $statistics['saleTransactions'] ?? 0;
        $saleTransactionsAmount = $statistics['saleTransactionsAmount'] ?? 0;
        $refundTransactions = $statistics['refundTransactions'] ?? 0;
        $refundTransactionsAmount = $statistics['refundTransactionsAmount'] ?? 0;
        $voidTransactions = $statistics['voidTransactions'] ?? 0;
        $voidTransactionsAmount = $statistics['voidTransactionsAmount'] ?? 0;
        
        $has_toolbar = true;
        return view('merchant.transactions.index', compact(
            'statistics', 
            'merchant', 
            'type',
            'saleTransactions',
            'saleTransactionsAmount',
            'refundTransactions',
            'refundTransactionsAmount',
            'voidTransactions',
            'voidTransactionsAmount',
            'has_toolbar'
        ));
    }

    public function show(Request $request, Transaction $transaction)
    {
        $merchant = Auth::guard('external')->user()->merchant;
        
        if (!$merchant || $transaction->merchant_id != $merchant->id) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['error' => 'Unauthorized access to transaction'], 403);
            }
            abort(403, 'Unauthorized access to transaction');
        }

        // Load the transaction with all necessary relationships including logs
        // Note: logs.performer uses morphTo which may reference ExternalUser (not a DB model)
        $transaction->load([
            'merchant' => function ($q) {
                $q->with(['country:id,name']);
            },
            'user',
            'currency',
            'country:id,name',
            'paymentMethod',
            'logs', // Load logs without performer to avoid ExternalUser issues
            'batch',
        ]);

        // Return JSON for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            // Build additional fields for POS invoice/receipt links
            try {
                $encryptedId = encrypt($transaction->id);
            } catch (\Exception $e) {
                $encryptedId = null;
            }

            $invoiceUrl = null;
            if ($encryptedId) {
                $invoiceUrl = env('FRONTEND_URL', 'https://corenetpay.com') . '/pos-invoice/' . $encryptedId;
            }
            $transaction->invoice_url = $invoiceUrl;
            $transaction->transaction_encrypted_id = $encryptedId;

            return response()->json(new MerchantTransactionDetailResource($transaction));
        }

        return view('merchant.transactions.show', compact('transaction', 'merchant'));
    }

    public function data(Request $request)
    {
        $merchantId = $request->get('merchant_id') ?? auth()->guard('external')->user()->merchant_id;
        
        // dd($merchantId)
        if (!$merchantId) {
            return response()->json(['error' => 'Merchant ID is required'], 400);
        }

        $query = Transaction::where('merchant_id', $merchantId);

        // Apply user_id filter if provided
        if ($request->has('user_id') && !empty($request->user_id)) {
            $query->where('user_id', $request->user_id);
        }

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $searchValue = $request->search;
            $query->where(function ($q) use ($searchValue) {
                $q->where('transaction_id', 'like', "%{$searchValue}%")
                  ->orWhere('rrn', 'like', "%{$searchValue}%")
                  ->orWhere('auth_code', 'like', "%{$searchValue}%")
                  ->orWhere('amount', 'like', "%{$searchValue}%")
                  ->orWhereHas('user', function ($userQuery) use ($searchValue) {
                      $userQuery->where('name', 'like', "%{$searchValue}%");
                  });
            });
        }

        // Apply status filter
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Apply payment_type filter
        if ($request->has('payment_type') && !empty($request->payment_type)) {
            $query->where('payment_type', $request->payment_type);
        }

        // Apply terminal_id filter
        if ($request->has('terminal_id') && !empty($request->terminal_id)) {
            $query->where('terminal_id', $request->terminal_id);
        }

        // Apply type filter (maps to status)
        if ($request->has('type') && !empty($request->type)) {
            $type = $request->type;
            
            // Map type to status
            if ($type === 'refunded') {
                $query->where('status', 'REFUNDED');
            } elseif ($type === 'voided') {
                $query->where('status', 'VOIDED');
            } elseif ($type === 'sale') {
                $query->whereIn('status', ['APPROVED', 'CAPTURED', 'PENDING']);
            } else {
                // If it's a direct type field, use it
                $query->where('type', $type);
            }
        }

        // Apply date filters (support both from/to_date and start/end_date)
        $fromDate = $request->from_date ?? $request->start_date;
        $toDate = $request->to_date ?? $request->end_date;

        if (!empty($fromDate)) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if (!empty($toDate)) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        // Merchant dashboard API: names + symbols only; full payment_method. Blade DataTables still needs merchant.
        if ($request->expectsJson() || $request->is('api/*')) {
            $query->with([
                'user:id,name',
                'currency:id,symbol',
                'country:id,name',
                'paymentMethod',
            ]);
        } else {
            $query->with(['merchant', 'user', 'currency']);
        }

        // For API response
        if ($request->expectsJson() || $request->is('api/*')) {
            $perPage = $request->get('per_page', 25);
            
            // Handle sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            // Validate sort column (whitelist allowed columns)
            $allowedSortColumns = ['transaction_id', 'amount', 'created_at', 'status', 'payment_type'];
            if (!in_array($sortBy, $allowedSortColumns)) {
                $sortBy = 'created_at';
            }
            
            // Validate sort order
            $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';
            
            $transactions = $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
            
            return response()->json([
                'data' => MerchantTransactionResource::collection($transactions->items()),
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
            ]);
        }

        // For DataTables response
        return DataTables::of($query)
            ->addColumn('record_select', function ($transaction) {
                return view('merchant.transactions.partials.record_select', ['id' => $transaction->id])->render();
            })
            ->editColumn('transaction_id', function ($transaction) {
                return $transaction->transaction_id ?? 'N/A';
            })
            ->editColumn('payment_type', function ($transaction) {
                return $transaction->payment_type ?? 'N/A';
            })
            ->editColumn('amount', function ($transaction) {
                return '$' . number_format($transaction->amount, 2);
            })
            ->addColumn('currency', function ($transaction) {
                return $transaction->currency_id ?? 'USD';
            })
            ->addColumn('amount', function ($transaction) {
                return '$ ' . number_format($transaction->amount, 2);
            })
            ->editColumn('terminal_id', function ($transaction) {
                return $transaction->terminal_id ?? 'N/A';
            })
            ->editColumn('user_id', function ($transaction) {
                return $transaction->user ? $transaction->user->name : 'N/A';
            })
            ->editColumn('status', function ($transaction) {
                return view('merchant.transactions.partials.status', ['status' => $transaction->status])->render();
            })
            ->editColumn('created_at', function ($transaction) {
                return $transaction->created_at ? $transaction->created_at->format('M d, Y H:i:s') : 'N/A';
            })
            ->addColumn('actions', function ($transaction) {
                return view('merchant.transactions.partials.actions', compact('transaction'))->render();
            })
            ->editColumn('merchant', function ($transaction) {
                return $transaction->merchant->name ?? 'N/A';
            })
            ->addColumn('payment_method', function ($transaction) {
                return $transaction->payment_method->name ?? 'N/A';
            })
            ->rawColumns(['record_select', 'status', 'actions'])
            ->make(true);
    }

    public function statistics(Request $request, $merchantId = null)
    {
        // Get merchant ID from request or parameter
        $merchantId = $merchantId ?? $request->get('merchant_id') ?? Auth::guard('external')->user()->merchant_id;
        
        if (!$merchantId) {
            return response()->json(['error' => 'Merchant ID is required'], 400);
        }

        $type = $request->get('type');
        
        // Apply user_id filter if provided
        $userId = $request->get('user_id');
        $baseQuery = Transaction::where('merchant_id', $merchantId);
        if ($userId) {
            $baseQuery->where('user_id', $userId);
        }
        
        // Total transactions count and amount
        $totalCount = (clone $baseQuery)->count();
        $totalAmount = (clone $baseQuery)->sum('amount');
        
        // Sale transactions (APPROVED + CAPTURED + PENDING)
        $saleCount = (clone $baseQuery)
            ->whereIn('status', ['APPROVED', 'CAPTURED', 'PENDING'])
            ->count();
        $saleAmount = (clone $baseQuery)
            ->whereIn('status', ['APPROVED', 'CAPTURED', 'PENDING'])
            ->sum('amount');
        
        // Voided transactions
        $voidCount = (clone $baseQuery)
            ->where('status', 'VOIDED')
            ->count();
        $voidAmount = (clone $baseQuery)
            ->where('status', 'VOIDED')
            ->sum('amount');
        
        // Refunded transactions
        $refundedCount = (clone $baseQuery)
            ->where('status', 'REFUNDED')
            ->count();
        $refundedAmount = (clone $baseQuery)
            ->where('status', 'REFUNDED')
            ->sum('amount');
        
        $approvalRate = $totalCount > 0 ? round(($saleCount / $totalCount) * 100, 1) : 0;

        $statistics = [
            'total_count' => $totalCount,
            'total_amount' => $totalAmount,
            'saleTransactions' => $saleCount,
            'saleTransactionsAmount' => $saleAmount,
            'refundTransactions' => $refundedCount,
            'refundTransactionsAmount' => $refundedAmount,
            'voidTransactions' => $voidCount,
            'voidTransactionsAmount' => $voidAmount,
            'approval_rate' => $approvalRate
        ];

        // Return JSON for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($statistics);
        }

        return $statistics;
    }

    private function getStatistics($merchantId)
    {
        $request = request();
        return $this->statistics($request, $merchantId);
    }

    /**
     * Bulk delete transactions
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:transactions,id'
        ]);

        $merchant = Auth::guard('external')->user()->merchant;
        
        if (!$merchant) {
            return response()->json(['error' => 'Merchant not found'], 404);
        }

        try {
            // Only delete transactions belonging to this merchant
            $deletedCount = Transaction::where('merchant_id', $merchant->id)
                ->whereIn('id', $request->ids)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} transaction(s)",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk delete transactions error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete transactions'
            ], 500);
        }
    }

    /**
     * Get merchant terminals for dropdown
     */
    public function getTerminals(Request $request)
    {
        $merchant = Auth::guard('external')->user()->merchant;
        
        if (!$merchant) {
            return response()->json(['error' => 'Merchant not found'], 404);
        }

        $terminals = $merchant->terminals()
            ->select('id', 'name', 'terminal_id')
            ->where('is_active', true)
            ->get();

        return response()->json($terminals);
    }

    public function export(Request $request)
    {
        $merchant = Auth::guard('external')->user()->merchant;
        
        if (!$merchant) {
            abort(403, 'Merchant not found');
        }

        $query = Transaction::where('merchant_id', $merchant->id)
            ->with(['merchant']);

        // Apply user_id filter if provided
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhere('rrn', 'like', "%{$search}%")
                  ->orWhere('auth_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->filled('terminal_id')) {
            $query->where('terminal_id', $request->terminal_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $transactions = $query->get();

        $filename = 'transactions_' . $merchant->id . '_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Transaction ID', 'RRN', 'Merchant', 'Payment Type', 
                'Card Number', 'Amount', 'Status', 'Batch No', 'SDK', 'Created At'
            ]);

            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->transaction_id ?? 'N/A',
                    $transaction->rrn ?? 'N/A',
                    $transaction->merchant->name ?? 'N/A',
                    $transaction->payment_type ?? 'N/A',
                    $transaction->card_number ? '**** **** **** ' . substr($transaction->card_number, -4) : 'N/A',
                    '$' . number_format($transaction->amount, 2),
                    $transaction->status ?? 'N/A',
                    $transaction->batch_no ?? 'N/A',
                    $transaction->sdk_id ?? 'N/A',
                    $transaction->created_at ? $transaction->created_at->format('M d, Y H:i:s') : 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function receipt(Transaction $transaction)
    {
        $merchant = Auth::guard('external')->user()->merchant;
        
        if (!$merchant || $transaction->merchant_id != $merchant->id) {
            abort(403, 'Unauthorized access to transaction');
        }

        // Generate QR code data for the transaction
        $qrData = $this->generateTransactionQRData($transaction);

        return view('merchant.transactions.receipt', compact('transaction', 'qrData'));
    }

    /**
     * Generate QR code data for transaction receipt
     */
    private function generateTransactionQRData(Transaction $transaction): string
    {
        $qrData = [
            'transaction_id' => $transaction->transaction_id,
            'amount' => $transaction->amount,
            'currency_id' => $transaction->currency_id,
            'status' => $transaction->status,
            'date' => $transaction->created_at->toISOString(),
            'merchant' => $transaction->merchant ? $transaction->merchant->name : 'Unknown',
            'type' => 'receipt'
        ];

        return json_encode($qrData);
    }

    public function sendReceipt(Request $request, Transaction $transaction)
    {
        $merchant = Auth::guard('external')->user()->merchant;
        
        if (!$merchant || $transaction->merchant_id != $merchant->id) {
            abort(403, 'Unauthorized access to transaction');
        }

        $request->validate([
            'email' => 'required|email|max:255',
            'message' => 'nullable|string|max:1000'
        ]);

        try {
            // Generate PDF invoice
            $pdfPath = $this->generateInvoicePDF($transaction);
            
            // Send the email with the transaction receipt and PDF attachment
            Mail::to($request->email)->send(new TransactionReceiptMail($transaction, $request->message, $pdfPath));
            
            // Clean up the temporary PDF file
            if ($pdfPath && file_exists($pdfPath)) {
                unlink($pdfPath);
            }
            
            return response()->json([
                'success' => true,
                'message' => __('translation.receipt_sent_successfully')
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Failed to send transaction receipt email: ' . $e->getMessage(), [
                'transaction_id' => $transaction->id,
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('translation.receipt_send_failed')
            ], 500);
        }
    }

    public function voidTransaction(Request $request, Transaction $transaction)
    {
        $merchant = $this->resolveMerchantUser()?->merchant;

        if (!$merchant || $transaction->merchant_id != $merchant->id) {
            abort(403, 'Unauthorized access to transaction');
        }

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        try {
            if (!in_array($transaction->status, ['pending', 'captured'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => __('translation.transaction_void_failed') . ': Transaction can only be voided when pending or captured.',
                ], 400);
            }

            $transaction->void($request->reason, ['reason' => $request->reason]);

            return response()->json([
                'success' => true,
                'message' => __('translation.transaction_voided_successfully'),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => __('translation.transaction_void_failed') . ': ' . $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('translation.transaction_void_failed'),
            ], 500);
        }
    }

    public function refundTransaction(Request $request, Transaction $transaction)
    {
        $merchant = $this->resolveMerchantUser()?->merchant;

        if (!$merchant || $transaction->merchant_id != $merchant->id) {
            abort(403, 'Unauthorized access to transaction');
        }

        $available = $transaction->getAvailableAmountForRefund();

        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . max($available, 0.01),
            'reason' => 'required|string|max:255',
        ]);

        try {
            if ($transaction->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => __('translation.refund_failed') . ': Transaction must be approved to refund.',
                ], 400);
            }

            $transaction->refund(
                (float) $request->amount,
                $request->reason,
                ['reason' => $request->reason]
            );

            return response()->json([
                'success' => true,
                'message' => __('translation.refund_initiated_successfully'),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => __('translation.refund_failed') . ': ' . $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('translation.refund_failed'),
            ], 500);
        }
    }

    /**
     * Merchant API routes may authenticate via api or external Passport guard.
     */
    private function resolveMerchantUser(): ?\App\Models\User
    {
        return Auth::guard('external')->user() ?? Auth::guard('api')->user();
    }

    /**
     * Generate PDF invoice for the transaction
     */
    private function generateInvoicePDF(Transaction $transaction)
    {
        try {
            // Create a temporary file path
            $filename = 'invoice_' . $transaction->transaction_id . '_' . time() . '.html';
            $pdfPath = storage_path('app/temp/' . $filename);
            
            // Ensure temp directory exists
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                if (!mkdir($tempDir, 0755, true)) {
                    Log::error('Failed to create temp directory: ' . $tempDir);
                    return null;
                }
            }
            
            // Generate HTML content for the invoice
            $htmlContent = View::make('merchant.transactions.invoice-pdf', compact('transaction'))->render();
            
            // Save as HTML file (can be converted to PDF using browser print or PDF libraries)
            if (file_put_contents($pdfPath, $htmlContent) === false) {
                Log::error('Failed to write PDF file: ' . $pdfPath);
                return null;
            }
            
            return $pdfPath;
        } catch (\Exception $e) {
            Log::error('Failed to generate invoice PDF: ' . $e->getMessage(), [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
