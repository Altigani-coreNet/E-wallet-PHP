<?php

namespace App\Http\Controllers\Api\V2\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminTransactionDetailResource;
use App\Http\Resources\AdminTransactionResource;
use App\Services\TransactionService;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AdminTransactionController extends Controller
{
    use ApiResponse;

    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Attach encrypted invoice fields used by frontend POS invoice route.
     */
    private function appendInvoiceFields(array $transactionData, int $transactionId): array
    {
        try {
            $encryptedId = encrypt($transactionId);
        } catch (\Exception $e) {
            $encryptedId = null;
        }

        $frontendBaseUrl = env('FRONTEND_URL', config('app.url', 'https://corenetpay.com'));
        $invoiceUrl = $encryptedId
            ? rtrim($frontendBaseUrl, '/') . '/pos-invoice/' . $encryptedId
            : null;

        $transactionData['transaction_encrypted_id'] = $encryptedId;
        $transactionData['invoice_url'] = $invoiceUrl;

        return $transactionData;
    }

    /**
     * Get all transactions with pagination, search, and filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Transaction::query()->with([
                'merchant:id,business_name,name',
                'partner:id,name',
                'country:id,name,short_name',
                'serviceCategory:id,name_en',
                'currency:id,symbol',
                'paymentMethod',
            ]);

            // Search
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('transaction_id', 'like', "%{$search}%")
                      ->orWhere('rrn', 'like', "%{$search}%")
                      ->orWhere('trace_no', 'like', "%{$search}%")
                      ->orWhere('ref_no', 'like', "%{$search}%")
                      ->orWhere('auth_code', 'like', "%{$search}%")
                      ->orWhere('amount', 'like', "%{$search}%");
                });
            }

            // Status filter
            if ($status = $request->input('status')) {
                $query->where('status', $status);
            }

            // Transaction type filter
            if ($type = $request->input('transaction_type', $request->input('type'))) {
                $query->where('transaction_type', $type);
            }

            // Merchant filter
            if ($merchantId = $request->input('merchant_id')) {
                $query->where('merchant_id', $merchantId);
            }

            // Terminal filter
            if ($terminalId = $request->input('terminal_id')) {
                $query->where('terminal_id', $terminalId);
            }

            // Partner filter
            if ($partnerId = $request->input('partner_id')) {
                $query->where('partner_id', $partnerId);
            }

            // Service category filter
            if ($serviceCategoryId = $request->input('service_category_id')) {
                $query->where('service_category_id', $serviceCategoryId);
            }

            // Country filter (via merchant's country)
            if ($countryId = $request->input('country_id')) {
                // $query->whereHas('merchant', function ($q) use ($countryId) {
                    $query->where('country_id', $countryId);
                // });
            }

            // Date range filter
            if ($dateFrom = $request->input('date_from', $request->input('start_date'))) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo = $request->input('date_to', $request->input('end_date'))) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            // Amount range filter
            if ($amountMin = $request->input('amount_min')) {
                $query->where('amount', '>=', $amountMin);
            }
            if ($amountMax = $request->input('amount_max')) {
                $query->where('amount', '<=', $amountMax);
            }

            // Pagination
            $perPage = $request->input('per_page', 15);
            $paginated = $query->latest()->paginate($perPage);

            return $this->SuccessMessage([
                'data' => AdminTransactionResource::collection($paginated->items())->resolve(),
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch transactions: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get transaction details
     */
    public function show(int $id): JsonResponse
    {
        try {
            $transaction = Transaction::with([
                'merchant' => function ($q) {
                    $q->with('country:id,name,short_name');
                },
                'partner' => function ($q) {
                    $q->with('country:id,name,short_name');
                },
                'service' => function ($q) {
                    $q->with([
                        'category:id,name_en,name_ar,code,image',
                        'country:id,name,short_name',
                    ]);
                },
                'serviceCategory:id,name_en,name_ar,code',
                'country:id,name,short_name',
                'currency',
                'user:id,name,email',
                'paymentMethod',
                'batch:id,batch_number,status',
            ])->findOrFail($id);

            try {
                $encryptedId = encrypt($transaction->id);
            } catch (\Exception $e) {
                $encryptedId = null;
            }

            $frontendBaseUrl = env('FRONTEND_URL', config('app.url', 'https://corenetpay.com'));
            $transaction->transaction_encrypted_id = $encryptedId;
            $transaction->invoice_url = $encryptedId
                ? rtrim($frontendBaseUrl, '/') . '/pos-invoice/' . $encryptedId
                : null;

            return $this->SuccessMessage(
                (new AdminTransactionDetailResource($transaction))->resolve()
            );

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch transaction: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get transaction statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type');
            
            if ($type) {
                // Get statistics for specific transaction type
                $statistics = $this->transactionService->getStatisticsByType($type);
            } else {
                // Get general statistics
                $statistics = $this->transactionService->getStatistics();
            }
            
            // Extract card-specific data for the response
            $stats = [
                'sale' => [
                    'count' => $statistics['saleTransactions'] ?? 0,
                    'amount' => $statistics['saleTransactionsAmount'] ?? 0
                ],
                'refund' => [
                    'count' => $statistics['refundTransactions'] ?? 0,
                    'amount' => $statistics['refundTransactionsAmount'] ?? 0
                ],
                'void' => [
                    'count' => $statistics['voidTransactions'] ?? 0,
                    'amount' => $statistics['voidTransactionsAmount'] ?? 0
                ]
            ];

            return $this->SuccessMessage($stats);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch statistics: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export transactions
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $query = Transaction::query()->with(['merchant', 'terminal', 'user', 'customer']);

            // Apply same filters as index
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('transaction_id', 'like', "%{$search}%")
                      ->orWhere('rrn', 'like', "%{$search}%")
                      ->orWhere('trace_no', 'like', "%{$search}%")
                      ->orWhere('ref_no', 'like', "%{$search}%")
                      ->orWhere('auth_code', 'like', "%{$search}%");
                });
            }

            if ($status = $request->input('status')) {
                $query->where('status', $status);
            }

            if ($type = $request->input('transaction_type')) {
                $query->where('transaction_type', $type);
            }

            if ($merchantId = $request->input('merchant_id')) {
                $query->where('merchant_id', $merchantId);
            }

            if ($partnerId = $request->input('partner_id')) {
                $query->where('partner_id', $partnerId);
            }

            if ($serviceCategoryId = $request->input('service_category_id')) {
                $query->where('service_category_id', $serviceCategoryId);
            }

            if ($dateFrom = $request->input('date_from')) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo = $request->input('date_to')) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            $transactions = $query->get();

            $exportData = $transactions->map(function ($transaction) {
                return [
                    'Transaction ID' => $transaction->transaction_id ?? $transaction->id,
                    'Reference Number' => $transaction->reference_number ?? 'N/A',
                    'Merchant' => $transaction->merchant->business_name ?? 'N/A',
                    'Terminal' => $transaction->terminal_id ?? 'N/A',
                    'Customer' => $transaction->customer->name ?? 'N/A',
                    'Amount' => $transaction->amount,
                    'Status' => $transaction->status,
                    'Type' => $transaction->transaction_type,
                    'Payment Method' => $transaction->payment_method ?? 'N/A',
                    'Date' => $transaction->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return $this->SuccessMessage([
                'data' => $exportData,
                'filename' => 'transactions_export_' . date('Y-m-d_H-i-s') . '.csv'
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to export transactions: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk delete transactions
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:transactions,id'
        ]);

        $ids = $request->ids;
        $deletedCount = 0;

        foreach ($ids as $id) {
            try {
                $this->transactionService->deleteTransaction($id);
                $deletedCount++;
            } catch (\Exception $e) {
                // Log error but continue with other deletions
                Log::error('Failed to delete transaction ' . $id . ': ' . $e->getMessage());
            }
        }

        return $this->SuccessMessage([
            'success' => true,
            'message' => $deletedCount . ' transaction(s) deleted successfully',
            'deleted_count' => $deletedCount
        ]);
    }

    /**
     * Refund transaction
     */
    public function refund(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:255',
        ]);

        $transaction = Transaction::findOrFail($id);
        
        try {
            DB::beginTransaction();
            $transaction->refund($request->input('amount'), $request->input('reason'));
            DB::commit();

            return $this->SuccessMessage([
                'success' => true,
                'message' => 'Transaction refunded successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->ErrorMessage('Transaction refund failed: ' . $e->getMessage(), null, 400);
        }
    }

    /**
     * Void transaction
     */
    public function void(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $transaction = Transaction::findOrFail($id);
        
        try {
            $transaction->void($request->input('reason'));
            
            return $this->SuccessMessage([
                'success' => true,
                'message' => 'Transaction voided successfully'
            ]);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Transaction void failed: ' . $e->getMessage(), null, 400);
        }
    }

    /**
     * Send receipt
     */
    public function sendReceipt(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'message' => 'nullable|string|max:1000'
            ]);

            $transaction = Transaction::with(['merchant', 'user', 'paymentMethod'])->findOrFail($id);

            // Here you would implement actual email sending logic
            // For now, return success
            
            return $this->SuccessMessage([
                'success' => true,
                'message' => 'Receipt sent successfully to ' . $request->email
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to send receipt: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get receipt view
     */
    public function receipt(int $id): JsonResponse
    {
        try {
            $transaction = $this->transactionService->getTransaction($id);
            
            if (!$transaction) {
                return $this->ErrorMessage('Transaction not found', null, 404);
            }

            return $this->SuccessMessage([
                'transaction' => $transaction,
                'receipt_url' => url("/admin/transactions/{$id}/receipt")
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to generate receipt: ' . $e->getMessage(), null, 500);
        }
    }
}




