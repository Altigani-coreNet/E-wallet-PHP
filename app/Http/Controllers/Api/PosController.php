<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Services\TransactionService;
use App\Services\ServiceTransactionService;
use App\Services\WebhookService;
use App\Http\Requests\Api\PosTransactionRequest;
use App\Http\Requests\Api\TransactionCreateRequest;
use App\Http\Requests\Api\TransactionUpdateRequest;
use App\Http\Requests\Api\TerminalRegisterRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\MerchantTransactionResource;
use App\Models\Product;
use App\Models\ServiceTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Transactions",
 *     description="API Endpoints for managing POS transactions"
 * )
 */
class PosController extends Controller
{
    use ApiResponse;
    
    protected $transactionService;
    protected $serviceTransactionService;
    protected $webhookService;

    public function __construct(
        TransactionService $transactionService,
        ServiceTransactionService $serviceTransactionService,
        WebhookService $webhookService
    )
    {
        $this->transactionService = $transactionService;
        $this->serviceTransactionService = $serviceTransactionService;
        $this->webhookService = $webhookService;
    }
    
    /**
     * @OA\Post(
     *     path="/api/pos",
     *     tags={"POS"},
     *     summary="Process POS request",
     *     description="Process a POS transaction request with card data from payment method and SDK data from terminal",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"transactionDetails", "paymentMethod", "merchantDetails", "securityData"},
     *             @OA\Property(
     *                 property="transactionDetails",
     *                 type="object",
     *                 required={"amount", "currency", "timestamp", "transactionType"},
     *                 @OA\Property(property="amount", type="number", example=100.00, description="Transaction amount"),
     *                 @OA\Property(property="currency", type="string", example="USD", description="Currency code"),
     *                 @OA\Property(property="timestamp", type="string", format="date-time", description="Transaction timestamp"),
     *                 @OA\Property(property="transactionType", type="string", example="SALE", description="Transaction type")
     *             ),
     *             @OA\Property(
     *                 property="paymentMethod",
     *                 type="object",
     *                 required={"entryMode", "panToken", "cardholderName", "expiryMonth", "expiryYear"},
     *                 @OA\Property(property="entryMode", type="string", example="CHIP", description="Card entry mode"),
     *                 @OA\Property(property="panToken", type="string", example="411111****1111", description="Masked card number"),
     *                 @OA\Property(property="cardholderName", type="string", example="John Doe", description="Cardholder name"),
     *                 @OA\Property(property="expiryMonth", type="integer", example=12, description="Card expiry month"),
     *                 @OA\Property(property="expiryYear", type="integer", example=2025, description="Card expiry year")
     *             ),
     *             @OA\Property(
     *                 property="merchantDetails",
     *                 type="object",
     *                 required={"merchantId", "terminalId"},
     *                 @OA\Property(property="merchantId", type="string", example="1", description="Merchant ID"),
     *                 @OA\Property(property="terminalId", type="string", example="1", description="Terminal ID")
     *             ),
     *             @OA\Property(
     *                 property="securityData",
     *                 type="object",
     *                 @OA\Property(property="emvData", type="string", example="EMV_DATA", description="EMV transaction data"),
     *                 @OA\Property(property="pinBlock", type="string", example="PIN_BLOCK", description="PIN block data"),
     *                 @OA\Property(property="ksn", type="string", example="KSN_DATA", description="Key serial number")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="POS transaction processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="POS transaction processed successfully"),
     *                 @OA\Property(property="transaction", type="object"),
     *                 @OA\Property(property="sdk_response", type="object")
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
     *             @OA\Property(property="message", type="string", example="Failed to process POS transaction"),
     *             @OA\Property(property="Error_Code", type="string", example="POS_TRANSACTION_ERROR")
     *         )
     *     )
     * )
     */
    public function pos(PosTransactionRequest $request)
    {
        try {
            $result = $this->transactionService->processPosTransaction($request->validated());
            return $this->SuccessMessage($result, 201);
        } catch (\Exception $e) {
            $code = is_numeric($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            $errorCode = $code === 404 ? 'TERMINAL_NOT_FOUND' : ($code === 409 ? 'DUPLICATE_TRANSACTION' : 'POS_TRANSACTION_ERROR');
            return $this->ErrorMessage($e->getMessage(), $errorCode, $code);
        }
    }


    public function posV2(PosTransactionRequest $request)
    {
        try {
            $result = $this->transactionService->processPosTransaction($request->validated());
            
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
            return $this->ErrorMessage($e->getMessage(), 'POS_TRANSACTION_ERROR', 500);
        }
    }

    /**
     * Same POS flow as posV2, with additional service transaction processing.
     */
    public function servicePosV2(PosTransactionRequest $request)
    {
        try {
            $payload = $request->validated();
            $result = $this->transactionService->processPosTransaction($payload);

            $serviceTransaction = null;
            if (isset($result['transaction']) && $result['transaction'] instanceof \App\Models\Transaction) {
                $serviceTransaction = $this->serviceTransactionService->processAfterPos($result['transaction'], $payload);
            }

            $result['service_transaction'] = $serviceTransaction;
            $result['meta'] = $this->formatThirdPartyPayloadMeta(
                $serviceTransaction?->request_payload ?? [],
                $payload['product_id'] ?? null
            );
            
            

            return $this->SuccessMessage($result, 201);
        } catch (\Exception $e) {
            return $this->ErrorMessage($e->getMessage(), 'SERVICE_POS_TRANSACTION_ERROR', 500);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/transactions",
     *     tags={"Transactions"},
     *     summary="Create a new transaction",
     *     description="Create a new POS transaction with all relevant details",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={},
     *             @OA\Property(property="batch_no", type="string", example="101447", description="Batch number for the transaction group"),
     *             @OA\Property(property="trace_no", type="string", example="543938", description="Unique trace number within the batch"),
     *             @OA\Property(property="rrn", type="string", example="921803539266", description="Retrieval Reference Number - globally unique ID"),
     *             @OA\Property(property="auth_code", type="string", example="607655", description="Approval code from the issuer/bank"),
     *             @OA\Property(property="mid", type="string", example="********3000", description="Merchant ID from acquiring bank"),
     *             @OA\Property(property="tid", type="string", example="**1001", description="Terminal ID identifying the payment terminal"),
     *             @OA\Property(property="transaction_id", type="string", example="M1815041566501543938", description="System-generated transaction ID"),
     *             @OA\Property(property="state", type="string", example="APPROVED", description="Transaction status (APPROVED, DECLINED, etc.)"),
     *             @OA\Property(property="description", type="string", example="Payment for services", description="Transaction description"),
     *             @OA\Property(property="transaction_datetime", type="string", format="date-time", example="2024-07-21 19:10:06", description="Transaction date and time"),
     *             @OA\Property(property="invoice_no", type="string", example="INV-001", description="Invoice reference number"),
     *             @OA\Property(property="card_number", type="string", example="492124******2228", description="Masked card number"),
     *             @OA\Property(property="expiry", type="string", example="12/25", description="Card expiry date"),
     *             @OA\Property(property="method", type="string", example="VISA", description="Card network (VISA, MASTERCARD, etc.)"),
     *             @OA\Property(property="ref_no", type="string", example="921803539266", description="Reference number (same as RRN)"),
     *             @OA\Property(property="atc", type="string", example="0025", description="Application Transaction Counter for EMV security"),
     *             @OA\Property(property="tvr", type="string", example="0000000000", description="Terminal Verification Results - bit flags for checks"),
     *             @OA\Property(property="app_name", type="string", example="Visa Debit", description="Card application name"),
     *             @OA\Property(property="tsi", type="string", example="0000", description="Transaction Status Information flags"),
     *             @OA\Property(property="terminal_id", type="integer", example=1, description="Foreign key to terminals table"),
     *             @OA\Property(property="merchant_id", type="integer", example=1, description="Foreign key to merchants table")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transaction created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Transaction created successfully"),
     *                 @OA\Property(
     *                     property="transaction",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="batch_no", type="string", example="101447"),
     *                     @OA\Property(property="trace_no", type="string", example="543938"),
     *                     @OA\Property(property="rrn", type="string", example="921803539266"),
     *                     @OA\Property(property="auth_code", type="string", example="607655"),
     *                     @OA\Property(property="mid", type="string", example="********3000"),
     *                     @OA\Property(property="tid", type="string", example="**1001"),
     *                     @OA\Property(property="transaction_id", type="string", example="M1815041566501543938"),
     *                     @OA\Property(property="state", type="string", example="APPROVED"),
     *                     @OA\Property(property="description", type="string", example="Payment for services"),
     *                     @OA\Property(property="transaction_datetime", type="string", format="date-time", example="2024-07-21 19:10:06"),
     *                     @OA\Property(property="invoice_no", type="string", example="INV-001"),
     *                     @OA\Property(property="card_number", type="string", example="492124******2228"),
     *                     @OA\Property(property="expiry", type="string", example="12/25"),
     *                     @OA\Property(property="method", type="string", example="VISA"),
     *                     @OA\Property(property="ref_no", type="string", example="921803539266"),
     *                     @OA\Property(property="atc", type="string", example="0025"),
     *                     @OA\Property(property="tvr", type="string", example="0000000000"),
     *                     @OA\Property(property="app_name", type="string", example="Visa Debit"),
     *                     @OA\Property(property="tsi", type="string", example="0000"),
     *                     @OA\Property(property="terminal_id", type="integer", example=1),
     *                     @OA\Property(property="merchant_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The terminal_id field must exist in terminals table."),
     *             @OA\Property(property="Error_Code", type="string", example="VALIDATION_ERROR")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to create transaction: Database error"),
     *             @OA\Property(property="Error_Code", type="string", example="TRANSACTION_CREATE_ERROR")
     *         )
     *     )
     * )
     */
    public function createTransaction(TransactionCreateRequest $request)
    {
        try {
            $transaction = $this->transactionService->createTransaction($request->validated());
            
            return $this->SuccessMessage([
                'message' => 'Transaction created successfully',
                'transaction' => $transaction
            ], 201);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create transaction: ' . $e->getMessage(), 'TRANSACTION_CREATE_ERROR', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/transactions",
     *     tags={"Transactions"},
     *     summary="Get all transactions",
     *     description="Retrieve a paginated list of all transactions",
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
     *         name="start_date",
     *         in="query",
     *         description="Filter transactions from this date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Filter transactions up to this date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search transactions by transaction_id, amount, card_number, or ref_no",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="merchant_id",
     *         in="query",
     *         description="Filter by merchant ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="terminal_id",
     *         in="query",
     *         description="Filter by terminal ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transactions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="transactions",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="data", type="array", @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="batch_no", type="string", example="101447"),
     *                         @OA\Property(property="trace_no", type="string", example="543938"),
     *                         @OA\Property(property="rrn", type="string", example="921803539266"),
     *                         @OA\Property(property="auth_code", type="string", example="607655"),
     *                         @OA\Property(property="mid", type="string", example="********3000"),
     *                         @OA\Property(property="tid", type="string", example="**1001"),
     *                         @OA\Property(property="transaction_id", type="string", example="M1815041566501543938"),
     *                         @OA\Property(property="state", type="string", example="APPROVED"),
     *                         @OA\Property(property="description", type="string", example="Payment for services"),
     *                         @OA\Property(property="transaction_datetime", type="string", format="date-time", example="2024-07-21 19:10:06"),
     *                         @OA\Property(property="invoice_no", type="string", example="INV-001"),
     *                         @OA\Property(property="card_number", type="string", example="492124******2228"),
     *                         @OA\Property(property="expiry", type="string", example="12/25"),
     *                         @OA\Property(property="method", type="string", example="VISA"),
     *                         @OA\Property(property="ref_no", type="string", example="921803539266"),
     *                         @OA\Property(property="atc", type="string", example="0025"),
     *                         @OA\Property(property="tvr", type="string", example="0000000000"),
     *                         @OA\Property(property="app_name", type="string", example="Visa Debit"),
     *                         @OA\Property(property="tsi", type="string", example="0000"),
     *                         @OA\Property(property="terminal_id", type="integer", example=1),
     *                         @OA\Property(property="merchant_id", type="integer", example=1),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time")
     *                     )),
     *                     @OA\Property(property="first_page_url", type="string"),
     *                     @OA\Property(property="from", type="integer"),
     *                     @OA\Property(property="last_page", type="integer"),
     *                     @OA\Property(property="last_page_url", type="string"),
     *                     @OA\Property(property="next_page_url", type="string"),
     *                     @OA\Property(property="path", type="string"),
     *                     @OA\Property(property="per_page", type="integer"),
     *                     @OA\Property(property="prev_page_url", type="string"),
     *                     @OA\Property(property="to", type="integer"),
     *                     @OA\Property(property="total", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to fetch transactions: Database error"),
     *             @OA\Property(property="Error_Code", type="string", example="TRANSACTION_FETCH_ERROR")
     *         )
     *     )
     * )
     */
    public function getTransactions(Request $request)
    {
        try {
            $filters = $request->only(['status', 'batch_id', 'search', 'start_date', 'end_date']);
            $limit = $request->input('limit', 10);
            
            $transactions = $this->transactionService->getAuthenticatedUserTransactions($filters, $limit);
            
            return $this->SuccessMessage([
                'transactions' => $transactions
            ], 200);
        } catch (\Exception $e) {
            $code = $e->getCode() == 400 ? 400 : 500;
            $errorCode = $e->getCode() == 400 ? 'NO_ACTIVE_TERMINAL' : 'TRANSACTION_FETCH_ERROR';
            return $this->ErrorMessage($e->getMessage(), $errorCode, $code);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/transactions/{id}",
     *     tags={"Transactions"},
     *     summary="Get a specific transaction",
     *     description="Retrieve details of a specific transaction by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Transaction ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="transaction",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="batch_no", type="string", example="101447"),
     *                     @OA\Property(property="trace_no", type="string", example="543938"),
     *                     @OA\Property(property="rrn", type="string", example="921803539266"),
     *                     @OA\Property(property="auth_code", type="string", example="607655"),
     *                     @OA\Property(property="mid", type="string", example="********3000"),
     *                     @OA\Property(property="tid", type="string", example="**1001"),
     *                     @OA\Property(property="transaction_id", type="string", example="M1815041566501543938"),
     *                     @OA\Property(property="state", type="string", example="APPROVED"),
     *                     @OA\Property(property="description", type="string", example="Payment for services"),
     *                     @OA\Property(property="transaction_datetime", type="string", format="date-time", example="2024-07-21 19:10:06"),
     *                     @OA\Property(property="invoice_no", type="string", example="INV-001"),
     *                     @OA\Property(property="card_number", type="string", example="492124******2228"),
     *                     @OA\Property(property="expiry", type="string", example="12/25"),
     *                     @OA\Property(property="method", type="string", example="VISA"),
     *                     @OA\Property(property="ref_no", type="string", example="921803539266"),
     *                     @OA\Property(property="atc", type="string", example="0025"),
     *                     @OA\Property(property="tvr", type="string", example="0000000000"),
     *                     @OA\Property(property="app_name", type="string", example="Visa Debit"),
     *                     @OA\Property(property="tsi", type="string", example="0000"),
     *                     @OA\Property(property="terminal_id", type="integer", example=1),
     *                     @OA\Property(property="merchant_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Transaction not found"),
     *             @OA\Property(property="Error_Code", type="string", example="TRANSACTION_NOT_FOUND")
     *         )
     *     )
     * )
     */
    public function getTransaction($id)
    {
        try {
            $transaction = $this->transactionService->getTransaction($id);
            $transaction->load('currency');
            if (!$transaction) {
                return $this->ErrorMessage('Transaction not found', 'TRANSACTION_NOT_FOUND', 404);
            }

            $encryptedId = encrypt($transaction->id);
            $invoiceUrl = rtrim(env('FRONTEND_URL', config('app.url', 'https://corenetpay.com')), '/') . '/pos-invoice/' . $encryptedId;
            
            return $this->SuccessMessage([
                'transaction' => new MerchantTransactionResource($transaction),
                'transaction_encrypted_id' => $encryptedId,
                'invoice_url' => $invoiceUrl,
            ], 200);
        } catch (\Exception $e) {
            // return $e->getMessage();
            return $this->ErrorMessage('Transaction not found', 'TRANSACTION_NOT_FOUND', 404);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/pos/transactions",
     *     tags={"POS"},
     *     summary="Get all POS transactions",
     *     description="Retrieve a paginated list of all POS transactions",
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
     *     @OA\Response(
     *         response=200,
     *         description="POS transactions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="transactions", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="amount", type="number", example=85.00),
     *                     @OA\Property(property="currency", type="string", example="AED"),
     *                     @OA\Property(property="transaction_id", type="string", example="term-tx-20250812124141-f5g6h7i8"),
     *                     @OA\Property(property="status", type="string", example="APPROVED"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 ))
     *             )
     *         )
     *     )
     * )
     */
    public function getPosTransactions(Request $request)
    {
        try {
            $filters = $request->only(['status']);
            $perPage = $request->input('per_page', 10);
            
            $transactions = $this->transactionService->getPosTransactionsFiltered($filters, $perPage);
            
            return $this->SuccessMessage([
                'transactions' => $transactions
            ], 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch POS transactions: ' . $e->getMessage(), 'POS_TRANSACTION_FETCH_ERROR', 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/pos/transactions/{id}",
     *     tags={"POS"},
     *     summary="Get a specific POS transaction",
     *     description="Retrieve details of a specific POS transaction by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="POS Transaction ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="POS transaction retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="transaction", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function getPosTransaction($id)
    {
        try {
            $transaction = $this->transactionService->getPosTransactionById($id);
            $encryptedId = encrypt($transaction->id);
            
            return $this->SuccessMessage([
                'transaction' => $transaction,
                'transaction_encrypted_id' => $encryptedId,
                'invoice_url' => rtrim(env('FRONTEND_URL', config('app.url', 'https://corenetpay.com')), '/') . '/pos-invoice/' . $encryptedId,
            ], 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('POS transaction not found', 'POS_TRANSACTION_NOT_FOUND', 404);
        }
    }

    /**
     * Public POS invoice endpoint (no auth) - used by external POS invoice print pages
     * 
     * URL uses encrypted transaction ID.
     */
    public function posInvoicePublic($encryptedId)
    {
        try {
            $invoiceData = $this->transactionService->getPosInvoiceDataByEncryptedId($encryptedId);
            $serviceTransaction = ServiceTransaction::query()
                ->where('transaction_id', $invoiceData['id'] ?? null)
                ->latest('id')
                ->first();

            if ($serviceTransaction) {
                $invoiceData['meta'] = $this->formatThirdPartyPayloadMeta(
                    $serviceTransaction->request_payload ?? [],
                    $serviceTransaction->product_id
                );
            }

            return $this->invoiceSuccessResponse($invoiceData);
        } catch (\Exception $e) {
            return $this->invoiceErrorResponse($e);
        }
    }

    /**
     * Public payment-link invoice endpoint (no auth) - URL uses payment link UUID.
     */
    
    public function linkInvoicePublic($uuid)
    {
        try {
            $invoiceData = $this->transactionService->getPaymentLinkInvoiceDataByUuid($uuid);
            return $this->invoiceSuccessResponse($invoiceData);
        } catch (\Exception $e) {
            return $this->invoiceErrorResponse($e);
        }
    }

    private function invoiceSuccessResponse(array $invoiceData)
    {
        return $this->SuccessMessage((new InvoiceResource($invoiceData))->resolve(), 200);
    }

    private function invoiceErrorResponse(\Exception $e)
    {
        $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
        $message = $code === 404 ? 'Invoice not found' : 'Failed to load invoice: ' . $e->getMessage();

        return $this->ErrorMessage($message, 'INVOICE_ERROR', $code);
    }

    /**
     * @OA\Post(
     *     path="/api/terminals/register",
     *     tags={"Terminals"},
     *     summary="Register a new terminal",
     *     description="Register a new terminal via API (auto registration)",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "merchant_id"},
     *             @OA\Property(property="name", type="string", example="Terminal 001", description="Terminal name"),
     *             @OA\Property(property="terminal_id", type="string", example="TERM12345678", description="Unique terminal identifier"),
     *             @OA\Property(property="merchant_id", type="integer", example=1, description="Merchant ID"),
     *             @OA\Property(property="branch_id", type="integer", example=1, description="Branch ID (optional)"),
     *             @OA\Property(property="model", type="string", example="Verifone VX520", description="Terminal model"),
     *             @OA\Property(property="manufacturer", type="string", example="Verifone", description="Terminal manufacturer"),
     *             @OA\Property(property="serial_no", type="string", example="SN123456789", description="Serial number"),
     *             @OA\Property(property="sdk_id", type="string", example="SDK001", description="SDK ID"),
     *             @OA\Property(property="sdk_version", type="string", example="1.0.0", description="SDK Version"),
     *             @OA\Property(property="android_os", type="string", example="Android 11", description="Android OS version"),
     *             @OA\Property(property="is_active", type="boolean", example=true, description="Terminal status")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Terminal registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Terminal registered successfully"),
     *                 @OA\Property(
     *                     property="terminal",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Terminal 001"),
     *                     @OA\Property(property="terminal_id", type="string", example="TERM12345678"),
     *                     @OA\Property(property="merchant_id", type="integer", example=1),
     *                     @OA\Property(property="branch_id", type="integer", example=1),
     *                     @OA\Property(property="model", type="string", example="Verifone VX520"),
     *                     @OA\Property(property="manufacturer", type="string", example="Verifone"),
     *                     @OA\Property(property="serial_no", type="string", example="SN123456789"),
     *                     @OA\Property(property="sdk_id", type="string", example="SDK001"),
     *                     @OA\Property(property="sdk_version", type="string", example="1.0.0"),
     *                     @OA\Property(property="android_os", type="string", example="Android 11"),
     *                     @OA\Property(property="add_type", type="string", example="auto"),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The name field is required."),
     *             @OA\Property(property="Error_Code", type="string", example="VALIDATION_ERROR")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to register terminal: Database error"),
     *             @OA\Property(property="Error_Code", type="string", example="TERMINAL_REGISTRATION_ERROR")
     *         )
     *     )
     * )
     */
    public function registerTerminal(TerminalRegisterRequest $request)
    {
        try {
            $terminal = $this->transactionService->registerTerminal($request->validated());

            return $this->SuccessMessage([
                'message' => 'Terminal registered successfully',
                'terminal' => $terminal
            ], 201);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to register terminal: ' . $e->getMessage(), 'TERMINAL_REGISTRATION_ERROR', 500);
        }
    }

    /**
     * Get transactions for the current authenticated user
     */
    public function getUserTransactions(Request $request)
    {
        try {
            $filters = $request->only(['status', 'search', 'start_date', 'end_date']);
            $limit = $request->input('limit', 10);
            
            $transactions = $this->transactionService->getUserTransactionsFiltered($filters, $limit);
            
            return $this->SuccessMessage([
                'transactions' => $transactions
            ], 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch user transactions: ' . $e->getMessage(), 'USER_TRANSACTION_FETCH_ERROR', 500);
        }
    }

    private function formatThirdPartyPayloadMeta(array $payload, ?string $productId): array
    {
        $product = null;
        if ($productId) {
            $product = Product::query()
                ->with([
                    'service',
                    'serviceForms.fields',
                ])
                ->find($productId);
        }

        // Map request payload keys -> translated field labels (like catalog form fields).
        $fieldLabelByKey = [];
        foreach (($product?->serviceForms ?? []) as $form) {
            foreach (($form?->fields ?? []) as $field) {
                $normalizedKey = strtolower(trim((string) ($field->key ?? '')));
                if ($normalizedKey === '') {
                    continue;
                }

                $label = app()->getLocale() === 'ar'
                    ? ((string) ($field->label_ar ?: $field->label_en))
                    : ((string) ($field->label_en ?: $field->label_ar));

                if ($label !== '') {
                    $fieldLabelByKey[$normalizedKey] = $label;
                }
            }
        }

        $formattedPayload = array_map(
            static function ($key, $value) use ($fieldLabelByKey) {
                $rawKey = (string) $key;
                $normalizedKey = strtolower(trim($rawKey));

                return [
                    'key' => $fieldLabelByKey[$normalizedKey] ?? $rawKey,
                    'value' => $value,
                ];
            },
            array_keys($payload),
            $payload
        );

        return array_merge(
            [
                [
                    'key' => __('translation.service'),
                    'value' => $product?->service?->service_name,
                ],
                [
                    'key' => __('translation.product'),
                    'value' => $product?->name,
                ],
                
            ],
            $formattedPayload
        );
    }
}
