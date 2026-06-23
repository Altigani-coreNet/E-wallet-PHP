<?php

namespace App\Http\Controllers\Api\V3;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Services\TransactionService;
use App\Services\WebhookService;
use App\Http\Requests\Api\V3\QrPaymentRequest;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="QR Code Payments (V3)",
 *     description="API Endpoints for processing QR code payments"
 * )
 */
class QrCodePaymentController extends Controller
{
    use ApiResponse;
    
    protected $transactionService;
    protected $webhookService;

    public function __construct(TransactionService $transactionService, WebhookService $webhookService)
    {
        $this->transactionService = $transactionService;
        $this->webhookService = $webhookService;
    }
    
    /**
     * @OA\Post(
     *     path="/api/v3/qr-payment",
     *     tags={"QR Code Payments (V3)"},
     *     summary="Process QR Code Payment",
     *     description="Process a payment transaction via QR code scan. Works exactly like POS card payment but data comes from QR code",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"transactionDetails", "qrData", "merchantDetails"},
     *             @OA\Property(
     *                 property="transactionDetails",
     *                 type="object",
     *                 required={"amount", "currency", "timestamp", "transactionType"},
     *                 @OA\Property(property="amount", type="number", example=150.00, description="Transaction amount"),
     *                 @OA\Property(property="currency", type="string", example="USD", description="Currency code"),
     *                 @OA\Property(property="timestamp", type="string", format="date-time", description="Transaction timestamp"),
     *                 @OA\Property(property="transactionType", type="string", example="SALE", description="Transaction type (SALE, REFUND)")
     *             ),
     *             @OA\Property(
     *                 property="qrData",
     *                 type="object",
     *                 required={"qrCode", "qrType"},
     *                 @OA\Property(property="qrCode", type="string", example="QR123456789ABCDEF", description="Scanned QR code data"),
     *                 @OA\Property(property="qrType", type="string", example="DYNAMIC", description="QR code type (STATIC, DYNAMIC)"),
     *                 @OA\Property(property="customerName", type="string", example="John Doe", description="Customer name from QR"),
     *                 @OA\Property(property="customerId", type="string", example="CUST12345", description="Customer ID from QR"),
     *                 @OA\Property(property="phoneNumber", type="string", example="+1234567890", description="Customer phone"),
     *                 @OA\Property(property="email", type="string", example="customer@example.com", description="Customer email"),
     *                 @OA\Property(property="walletProvider", type="string", example="PayWallet", description="Wallet/Payment provider")
     *             ),
     *             @OA\Property(
     *                 property="merchantDetails",
     *                 type="object",
     *                 required={"merchantId", "terminalId"},
     *                 @OA\Property(property="merchantId", type="string", example="1", description="Merchant ID"),
     *                 @OA\Property(property="terminalId", type="string", example="1", description="Terminal ID"),
     *                 @OA\Property(property="branchId", type="string", example="1", description="Branch ID (optional)")
     *             ),
     *             @OA\Property(
     *                 property="additionalData",
     *                 type="object",
     *                 @OA\Property(property="description", type="string", example="Payment for order #12345"),
     *                 @OA\Property(property="orderNumber", type="string", example="ORD-12345"),
     *                 @OA\Property(property="notes", type="string", example="Express delivery")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="QR Payment processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="QR payment processed successfully"),
     *                 @OA\Property(
     *                     property="transaction",
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="uuid-here"),
     *                     @OA\Property(property="transaction_id", type="string", example="QR-20260112-ABCD1234"),
     *                     @OA\Property(property="amount", type="number", example=150.00),
     *                     @OA\Property(property="currency", type="string", example="USD"),
     *                     @OA\Property(property="status", type="string", example="APPROVED"),
     *                     @OA\Property(property="payment_method", type="string", example="qr_code"),
     *                     @OA\Property(property="qr_type", type="string", example="DYNAMIC"),
     *                     @OA\Property(property="customer_name", type="string", example="John Doe"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 ),
     *                 @OA\Property(property="qr_response", type="object", description="Response from QR payment gateway")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Terminal not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Terminal not found"),
     *             @OA\Property(property="Error_Code", type="string", example="TERMINAL_NOT_FOUND")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation error message"),
     *             @OA\Property(property="Error_Code", type="string", example="VALIDATION_ERROR")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to process QR payment"),
     *             @OA\Property(property="Error_Code", type="string", example="QR_PAYMENT_ERROR")
     *         )
     *     )
     * )
     */
    public function processQrPayment(QrPaymentRequest $request)
    {
        try {
            $result = $this->transactionService->processQrPayment($request->validated());
            
            // Get merchant ID from authenticated user
            $user = Auth::guard('external')->user();
            $merchantId = $user->merchant->id ?? null;
            
            // Trigger webhook for transaction.created event
            if ($merchantId && isset($result['transaction'])) {
                $this->webhookService->trigger(
                    'transaction.created',
                    $merchantId,
                    [
                        'transaction_id' => $result['transaction']['id'] ?? null,
                        'amount' => $result['transaction']['amount'] ?? null,
                        'currency' => $result['transaction']['currency'] ?? null,
                        'status' => $result['transaction']['status'] ?? null,
                        'transaction_type' => $result['transaction']['transaction_type'] ?? null,
                        'created_at' => $result['transaction']['created_at'] ?? now()->toIso8601String(),
                        'transaction' => $result['transaction'],
                    ]
                );
                
                // Trigger additional webhooks based on transaction status
                if (isset($result['transaction']['status'])) {
                    $status = strtolower($result['transaction']['status']);
                    
                    if ($status === 'approved' || $status === 'completed') {
                        $this->webhookService->trigger(
                            'transaction.approved',
                            $merchantId,
                            [
                                'transaction_id' => $result['transaction']['id'] ?? null,
                                'amount' => $result['transaction']['amount'] ?? null,
                                'status' => $result['transaction']['status'],
                                'transaction' => $result['transaction'],
                            ]
                        );
                        
                        $this->webhookService->trigger(
                            'payment.succeeded',
                            $merchantId,
                            [
                                'transaction_id' => $result['transaction']['id'] ?? null,
                                'amount' => $result['transaction']['amount'] ?? null,
                                'transaction' => $result['transaction'],
                            ]
                        );
                    } elseif ($status === 'declined') {
                        $this->webhookService->trigger(
                            'transaction.declined',
                            $merchantId,
                            [
                                'transaction_id' => $result['transaction']['id'] ?? null,
                                'reason' => $result['transaction']['decline_reason'] ?? 'Unknown',
                                'transaction' => $result['transaction'],
                            ]
                        );
                    } elseif ($status === 'failed') {
                        $this->webhookService->trigger(
                            'transaction.failed',
                            $merchantId,
                            [
                                'transaction_id' => $result['transaction']['id'] ?? null,
                                'error' => $result['transaction']['error_message'] ?? 'Unknown error',
                                'transaction' => $result['transaction'],
                            ]
                        );
                        
                        $this->webhookService->trigger(
                            'payment.failed',
                            $merchantId,
                            [
                                'transaction_id' => $result['transaction']['id'] ?? null,
                                'error' => $result['transaction']['error_message'] ?? 'Unknown error',
                                'transaction' => $result['transaction'],
                            ]
                        );
                    }
                }
            }
            
            return $this->SuccessMessage($result, 201);
        } catch (\Exception $e) {
            return $this->ErrorMessage($e->getMessage(), 'QR_PAYMENT_ERROR', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v3/qr-payment/transactions",
     *     tags={"QR Code Payments (V3)"},
     *     summary="Get all QR payment transactions",
     *     description="Retrieve a paginated list of all QR code payment transactions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by transaction status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"PENDING", "APPROVED", "DECLINED", "FAILED"})
     *     ),
     *     @OA\Parameter(
     *         name="qr_type",
     *         in="query",
     *         description="Filter by QR type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"STATIC", "DYNAMIC"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="QR transactions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="transactions", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="string"),
     *                     @OA\Property(property="transaction_id", type="string"),
     *                     @OA\Property(property="amount", type="number"),
     *                     @OA\Property(property="currency", type="string"),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="payment_method", type="string", example="qr_code"),
     *                     @OA\Property(property="qr_type", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 ))
     *             )
     *         )
     *     )
     * )
     */
    public function getQrTransactions(\Illuminate\Http\Request $request)
    {
        try {
            $filters = $request->only(['status', 'qr_type', 'search', 'start_date', 'end_date']);
            $filters['payment_method'] = 'qr_code'; // Filter only QR transactions
            $perPage = $request->input('per_page', 10);
            
            $transactions = $this->transactionService->getQrTransactionsFiltered($filters, $perPage);
            
            return $this->SuccessMessage([
                'transactions' => $transactions
            ], 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch QR transactions: ' . $e->getMessage(), 'QR_TRANSACTION_FETCH_ERROR', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v3/qr-payment/transactions/{id}",
     *     tags={"QR Code Payments (V3)"},
     *     summary="Get a specific QR payment transaction",
     *     description="Retrieve details of a specific QR code payment transaction by ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="QR Transaction ID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="QR transaction retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="transaction", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="QR transaction not found"
     *     )
     * )
     */
    public function getQrTransaction($id)
    {
        try {
            $transaction = $this->transactionService->getQrTransactionById($id);
            
            return $this->SuccessMessage([
                'transaction' => $transaction
            ], 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('QR transaction not found', 'QR_TRANSACTION_NOT_FOUND', 404);
        }
    }
}

