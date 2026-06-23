<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    protected $transactionService;

    private function parseDateTimeInput($value, bool $isEnd): ?Carbon
    {
        if (!$value) {
            return null;
        }

        $hasTime = is_string($value) && preg_match('/[T\s]\d{2}:\d{2}(:\d{2})?/', $value) === 1;
        $dt = Carbon::parse($value);

        if ($hasTime) {
            return $dt;
        }

        return $isEnd ? $dt->endOfDay() : $dt->startOfDay();
    }

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Resolve the merchant id of the currently authenticated context, if any.
     */
    private function currentMerchantId(): ?string
    {
        $merchantId = Auth::guard('external')->user()?->merchant?->id
            ?? Auth::guard('api')->user()?->merchant_id
            ?? Auth::user()?->merchant_id;

        return $merchantId !== null ? (string) $merchantId : null;
    }

    /**
     * Reject refunds against a transaction that belongs to another merchant (IDOR).
     * No-op when there is no merchant context (e.g. internal/admin callers).
     *
     * @throws \InvalidArgumentException
     */
    private function assertTransactionOwnedByCurrentMerchant(Transaction $transaction): void
    {
        $merchantId = $this->currentMerchantId();

        if (
            $merchantId !== null
            && $transaction->merchant_id !== null
            && (string) $transaction->merchant_id !== $merchantId
        ) {
            throw new \InvalidArgumentException('This transaction does not belong to your account.');
        }
    }

    /**
     * Guard against duplicate submissions: an identical refund (same parent + amount)
     * created in the last few seconds is treated as a replay, not a new refund.
     *
     * @throws \InvalidArgumentException
     */
    private function assertNotDuplicateRefund(Transaction $transaction, float $amount): void
    {
        $isDuplicate = Transaction::where('reference_transaction_id', $transaction->id)
            ->where('amount', $amount)
            ->where('created_at', '>=', now()->subSeconds(10))
            ->exists();

        if ($isDuplicate) {
            throw new \InvalidArgumentException('A matching refund was just processed. This looks like a duplicate submission.');
        }
    }

    /**
     * Get transaction details
     */
    public function show($id): JsonResponse
    {
        try {
            $transaction = Transaction::with(['user', 'merchant', 'paymentMethod', 'batch'])
                                   ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new \App\Http\Resources\TransactionResource($transaction),
                'message' => 'Transaction retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }
    }

    /**
     * Process a refund for a transaction
     */
    /**
     * @OA\Post(
     *     path="/api/transactions/{id}/refund",
     *     summary="Process a refund for a transaction",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Transaction ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="number", format="float", example=100.00),
     *             @OA\Property(property="description", type="string", example="Refund for returned item"),
     *             @OA\Property(property="reason", type="string", example="Customer returned product"),
     *             @OA\Property(property="metadata", type="object", example={"note": "Manual refund"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Refund processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Refund processed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Failed to process refund",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to process refund")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function refund(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|exists:transactions,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
            'reason' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $metadata = array_merge($request->only(['reason']), $request->input('metadata', []));
            
            $transaction = Transaction::findOrFail($request->transaction_id);

            // Tenant isolation: a merchant may only refund its own transactions.
            $this->assertTransactionOwnedByCurrentMerchant($transaction);

            // Check if transaction status is valid for refund
            if ($transaction->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction must be in approved status to be refunded. Current status: ' . $transaction->status
                ], 400);
            }

            // Row-lock + duplicate guard so a double-submit cannot refund twice.
            $transaction = DB::transaction(function () use ($request, $metadata) {
                $locked = Transaction::lockForUpdate()->findOrFail($request->transaction_id);
                $this->assertNotDuplicateRefund($locked, (float) $request->amount);

                return $locked->refund(
                    $request->amount,
                    $request->description,
                    $metadata
                );
            });

            return response()->json([
                'success' => true,
                'data' => $transaction,
                'message' => 'Refund processed successfully'
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process refund'
            ], 500);
        }
    }

    /**
     * Process a partial refund for a transaction
     */
    public function partialRefund(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|exists:transactions,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
            'reason' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $metadata = array_merge($request->only(['reason']), $request->input('metadata', []));

            // Tenant isolation + duplicate guard (same protections as full refund).
            $owner = Transaction::findOrFail($request->transaction_id);
            $this->assertTransactionOwnedByCurrentMerchant($owner);
            $this->assertNotDuplicateRefund($owner, (float) $request->amount);

            $transaction = $this->transactionService->processPartialRefund(
                $request->transaction_id,
                $request->amount,
                $request->description,
                $metadata
            );

            return response()->json([
                'success' => true,
                'data' => $transaction,
                'message' => 'Partial refund processed successfully'
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process partial refund'
            ], 500);
        }
    }

    /**
     * Void a transaction
     */
    public function void(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|exists:transactions,id',
            'description' => 'nullable|string|max:500',
            'reason' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $metadata = array_merge($request->only(['reason']), $request->input('metadata', []));
            $transaction = Transaction::findOrFail($request->transaction_id);

            // Check if transaction status is valid for void
            if (!in_array($transaction->status, ['pending', 'captured'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction can only be voided when in pending or captured status. Current status: ' . $transaction->status
                ], 400);
            }

            $transaction = $transaction->void(
                $request->description,
                $metadata
            );

            return response()->json([
                'success' => true,
                'data' => $transaction,
                'message' => 'Transaction voided successfully'
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to void transaction'
            ], 500);
        }
    }

    /**
     * Cancel a transaction
     */
    public function cancel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|exists:transactions,id',
            'description' => 'nullable|string|max:500',
            'reason' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $metadata = array_merge($request->only(['reason']), $request->input('metadata', []));
            $transaction = Transaction::findOrFail($request->transaction_id);

            if (!in_array($transaction->status, ['pending', 'captured'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction can only be cancelled when in pending or captured status. Current status: ' . $transaction->status,
                ], 400);
            }

            $transaction = $transaction->cancel(
                $request->description,
                $metadata
            );

            return response()->json([
                'success' => true,
                'data' => $transaction,
                'message' => 'Transaction cancelled successfully'
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel transaction'
            ], 500);
        }
    }

    /**
     * Get transaction audit trail
     */
    public function auditTrail($id): JsonResponse
    {
        try {
            $auditTrail = $this->transactionService->getAuditTrail($id);

            return response()->json([
                'success' => true,
                'data' => $auditTrail,
                'message' => 'Audit trail retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve audit trail'
            ], 500);
        }
    }

    /**
     * Get transaction history
     */
    public function history(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $history = $this->transactionService->getTransactionHistory(
                $id,
                $request->input('limit', 50)
            );

            return response()->json([
                'success' => true,
                'data' => $history,
                'message' => 'Transaction history retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transaction history'
            ], 500);
        }
    }

    /**
     * Get transaction summary
     */
    public function summary(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'merchant_id' => 'nullable|exists:merchants,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $summary = $this->transactionService->getTransactionSummary(
                $request->input('merchant_id'),
                $request->input('date_from'),
                $request->input('date_to')
            );

            return response()->json([
                'success' => true,
                'data' => $summary,
                'message' => 'Transaction summary retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transaction summary'
            ], 500);
        }
    }

    /**
     * Get total transaction amount only.
     */
    public function getTotalTransactionAmount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::guard('external')->user();
            $merchantId = $user?->merchant?->id ?? null;

            if (!$merchantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found from API key context'
                ], 401);
            }

            $query = Transaction::query()
                ->where('merchant_id', $merchantId)
                ->whereRaw('LOWER(status) NOT IN (?, ?)', ['cancelled', 'refunded']);

            $from = $this->parseDateTimeInput($request->input('date_from'), false);
            $to = $this->parseDateTimeInput($request->input('date_to'), true);

            if ($from && $to) {
                $query->whereBetween('created_at', [$from, $to]);
            } elseif ($from) {
                $query->where('created_at', '>=', $from);
            } elseif ($to) {
                $query->where('created_at', '<=', $to);
            }

            $totalAmount = (float) ($query->sum('amount') ?? 0);
            $totalTransactionCount = (int) ($query->count() ?? 0);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_amount' => round($totalAmount, 2),
                    'total_transaction_count' => $totalTransactionCount,
                ],
                'message' => 'Total transaction amount retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve total transaction amount'
            ], 500);
        }
    }

    /**
     * Get transactions with refunds
     */
    public function refundedTransactions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'merchant_id' => 'nullable|exists:merchants,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transactions = $this->transactionService->getTransactionsWithRefunds(
                $request->input('merchant_id'),
                $request->input('limit', 50)
            );

            return response()->json([
                'success' => true,
                'data' => $transactions,
                'message' => 'Refunded transactions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve refunded transactions'
            ], 500);
        }
    }

    /**
     * Get voided transactions
     */
    public function voidedTransactions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'merchant_id' => 'nullable|exists:merchants,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transactions = $this->transactionService->getVoidedTransactions(
                $request->input('merchant_id'),
                $request->input('limit', 50)
            );

            return response()->json([
                'success' => true,
                'data' => $transactions,
                'message' => 'Voided transactions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve voided transactions'
            ], 500);
        }
    }

    /**
     * Get cancelled transactions
     */
    public function cancelledTransactions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'merchant_id' => 'nullable|exists:merchants,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transactions = $this->transactionService->getCancelledTransactions(
                $request->input('merchant_id'),
                $request->input('limit', 50)
            );

            return response()->json([
                'success' => true,
                'data' => $transactions,
                'message' => 'Cancelled transactions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cancelled transactions'
            ], 500);
        }
    }

    /**
     * Get refund details for a transaction
     */
    public function refundDetails($id): JsonResponse
    {
        try {
            $transaction = Transaction::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->transaction_id,
                    'refundable_amount' => $transaction->refundable_amount ?? $transaction->amount,
                    'original_amount' => $transaction->original_amount ?? $transaction->amount,
                ],
                'message' => 'Refund details retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }
    }

    /**
     * Get refund history for a transaction
     */
    public function refundHistory($id): JsonResponse
    {
        try {
            $transaction = Transaction::findOrFail($id);
            $refunds = $transaction->refunds()->where('type', 'refund')->get();
            // dd($refunds);
            return response()->json([
                'success' => true,
                'data' => $refunds,
                'message' => 'Refund history retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve refund history'
            ], 500);
        }
    }

    /**
     * Refund all eligible transactions (admin/bulk action)
     */
    public function refundAll(Request $request): JsonResponse
    {
        $results = [
            'success' => [],
            'failed' => []
        ];
        $eligibleTransactions = Transaction::where('status', 'approved')
            
            ->get();
        foreach ($eligibleTransactions as $transaction) {
            try {
                $amount = $transaction->getAvailableAmountForRefund();
                if ($amount > 0) {
                    $transaction->refund($amount, 'Bulk refund');
                    $results['success'][] = [
                        'transaction_id' => $transaction->id,
                        'refunded_amount' => $amount
                    ];
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ];
            }
        }
        return response()->json([
            'success' => true,
            'results' => $results,
            // 'message' => 'Bulk refund process completed'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/transactions/void",
     *     summary="Void a transaction",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"transaction_id"},
     *             @OA\Property(property="transaction_id", type="integer", example=123),
     *             @OA\Property(property="reason", type="string", example="Customer request")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction voided successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction voided successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Failed to void transaction"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed"
     *     )
     * )
     */
    public function apiVoid(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|exists:transactions,id',
            'reason' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        try {
            $transaction = Transaction::findOrFail($request->transaction_id);
            $transaction->void($request->reason);
            return response()->json([
                'success' => true,
                'message' => 'Transaction voided successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to void transaction: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/transactions/refund",
     *     summary="Refund a transaction",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"transaction_id", "amount"},
     *             @OA\Property(property="transaction_id", type="integer", example=123),
     *             @OA\Property(property="amount", type="number", format="float", example=50.00),
     *             @OA\Property(property="reason", type="string", example="Product returned")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction refunded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction refunded successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Failed to refund transaction"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed"
     *     )
     * )
     */
    public function apiRefund(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|exists:transactions,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        try {
            $transaction = Transaction::findOrFail($request->transaction_id);
            if($transaction->status != 'approved'){
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction is not approved'
                ], 400);
            }
            $transaction->refund($request->amount, $request->reason);
            return response()->json([
                'success' => true,
                'message' => 'Transaction refunded successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refund transaction: ' . $e->getMessage()
            ], 400);
        }
    }
}
