<?php

namespace App\Services;

use App\Repositories\TransactionRepository;
use App\Models\Transaction;
use App\Models\Batch;
use App\Models\PaymentMethod;
use App\Models\Currency;
use App\Models\Terminal;
use App\Models\PaymentByLink;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class TransactionService
{
    protected $transactionRepository;
    protected $paymentGatewayService;

    /**
     * Build a frontend POS invoice URL using encrypted transaction id.
     */
    private function buildPosInvoiceUrl(string $encryptedId): string
    {
        $frontendBaseUrl = env('FRONTEND_URL', config('app.url', 'https://corenetpay.com'));
        return rtrim($frontendBaseUrl, '/') . '/pos-invoice/' . $encryptedId;
    }

    /**
     * Build a frontend payment-link invoice URL using payment link UUID.
     */
    private function buildPaymentLinkInvoiceUrl(string $uuid): string
    {
        $frontendBaseUrl = env('FRONTEND_URL', config('app.url', 'https://corenetpay.com'));
        return rtrim($frontendBaseUrl, '/') . '/link-invoice/' . $uuid;
    }

    /**
     * Mask card number and expose only last 4 digits.
     */
    private function maskCardNumber(?string $cardNumber): ?string
    {
        if (!$cardNumber) {
            return null;
        }

        $digitsOnly = preg_replace('/\D+/', '', $cardNumber);
        if (!$digitsOnly) {
            return null;
        }

        $lastFour = substr($digitsOnly, -4);
        return '****' . $lastFour;
    }

    /**
     * Prefer transaction columns; fall back to related PaymentMethod (pan_token, expiry_month/year).
     *
     * @return array{0: ?string, 1: ?string} [masked pan or null, expiry string or null]
     */
    private function resolveInvoiceCardAndExpiry(Transaction $transaction): array
    {
        if (!$transaction->relationLoaded('paymentMethod')) {
            $transaction->load('paymentMethod');
        }

        $pm = $transaction->paymentMethod;

        $cardRaw = $transaction->card_number;
        if (!$cardRaw && $pm && ! empty($pm->pan_token)) {
            $cardRaw = $pm->pan_token;
        }

        $expiry = $transaction->expiry;
        if (! $expiry && $pm && $pm->expiry_month && $pm->expiry_year) {
            $y = (string) $pm->expiry_year;
            $yy = strlen($y) <= 2 ? $y : substr($y, -2);
            $expiry = sprintf('%02d/%s', (int) $pm->expiry_month, $yy);
        }

        return [$this->maskCardNumber($cardRaw), $expiry];
    }

    /**
     * Public wrapper for checkout/receipt APIs that need masked PAN + expiry from txn + payment method.
     *
     * @return array{0: ?string, 1: ?string}
     */
    public function getMaskedCardAndExpiryForTransaction(Transaction $transaction): array
    {
        return $this->resolveInvoiceCardAndExpiry($transaction);
    }

    public function __construct(
        TransactionRepository $transactionRepository,
        PaymentGatewayService $paymentGatewayService
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->paymentGatewayService = $paymentGatewayService;
    }

    /**
     * Create a new transaction
     */
    public function createTransaction(array $data): Transaction
    {
        $this->validateTransactionData($data);
        
        return $this->transactionRepository->create($data);
    }

    /**
     * Same persistence path as {@see processPosTransaction} (PaymentMethod + Transaction rows)
     * without terminal auth or the acquirer SDK — used when Stripe has already captured funds
     * (payment link webhook / confirm-intent).
     *
     * Expects the same nested shape as {@see \App\Http\Requests\Api\PosTransactionRequest} plus:
     * - merchant_id (required)
     * - country_id, terminal_id, user_id (optional)
     * - description (optional)
     * - transaction_metadata (optional) merged into transactions.metadata
     *
     * Optional on paymentMethod: cardBrand (maps to payment_methods.card_brand).
     */
    public function recordStripePaymentLinkTransaction(array $data): Transaction
    {
        $merchantId = $data['merchant_id'] ?? null;
        if (!$merchantId) {
            throw new \InvalidArgumentException('merchant_id is required');
        }

        $td = $data['transactionDetails'] ?? [];
        $pmIn = $data['paymentMethod'] ?? [];
        $sd = $data['securityData'] ?? [];

        if (empty($td['currency'])) {
            throw new \InvalidArgumentException('transactionDetails.currency is required');
        }

        $currency = Currency::find($td['currency']);
        $currencyData = $currency ? [
            'id' => $currency->id,
            'country' => $currency->country,
            'name' => $currency->name,
            'symbol' => $this->resolveCurrencySymbol($currency),
            'currency_code' => $this->resolveCurrencyCode($currency),
        ] : null;

        if (!$currencyData) {
            throw new \InvalidArgumentException('Invalid currency ID or unable to fetch currency details from database', 400);
        }

        return DB::transaction(function () use ($data, $merchantId, $td, $pmIn, $sd, $currencyData) {
            $paymentMethod = PaymentMethod::create([
                'entry_mode' => $pmIn['entryMode'] ?? 'CNP',
                'pan_token' => $pmIn['panToken'] ?? '',
                'cardholder_name' => $pmIn['cardholderName'] ?? null,
                'expiry_month' => isset($pmIn['expiryMonth']) ? (int) $pmIn['expiryMonth'] : null,
                'expiry_year' => isset($pmIn['expiryYear']) ? (int) $pmIn['expiryYear'] : null,
                'payment_channel' => $pmIn['paymentChannel'] ?? null,
                'card_brand' => $pmIn['cardBrand'] ?? null,
            ]);

            $invoiceNo = 'QR-INV-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));

            $expiryStr = '';
            if (isset($pmIn['expiryMonth'], $pmIn['expiryYear'])) {
                $expiryStr = str_pad((string) $pmIn['expiryMonth'], 2, '0', STR_PAD_LEFT) . '/' . $pmIn['expiryYear'];
            }

            $transactionData = [
                'amount' => $td['amount'],
                'original_amount' => $td['amount'],
                'currency_id' => $td['currency'],
                'currency_symbol' => $currencyData['symbol'] ?? null,
                'currency_object' => json_encode($currencyData),
                'transaction_id' => Transaction::generateTransactionId(),
                'timestamp' => $td['timestamp'] ?? now(),
                'transaction_type' => $td['transactionType'] ?? 'payment_link',
                'payment_method_id' => $paymentMethod->id,
                'merchant_id' => $merchantId,
                'terminal_id' => $data['terminal_id'] ?? null,
                'emv_data' => $sd['emvData'] ?? null,
                'pin_block' => $sd['pinBlock'] ?? null,
                'ksn' => $sd['ksn'] ?? null,
                'status' => 'approved',
                'invoice_no' => $invoiceNo,
                'user_id' => $data['user_id'] ?? null,
                'card_number' => $pmIn['panToken'] ?? null,
                'expiry' => $expiryStr !== '' ? $expiryStr : null,
                'method' => $pmIn['entryMode'] ?? 'CNP',
                'country_id' => $data['country_id'] ?? null,
                'payment_type' => 'web',
                'partner_id' => $data['partner_id'] ?? null,
                'service_category_id' => $data['service_category_id'] ?? null,
                'service_id' => $data['service_id'] ?? null,
                'description' => $data['description'] ?? 'Payment Link Transaction',
                'sdk' => 'stripe_sdk',
                'transaction_datetime' => now(),
                'metadata' => array_merge(
                    ['source' => 'payment_link'],
                    $data['transaction_metadata'] ?? []
                ),
            ];

            return Transaction::create($transactionData);
        });
    }

    /**
     * Update an existing transaction
     */
    public function updateTransaction(int $id, array $data): Transaction
    {
        $transaction = $this->transactionRepository->findById($id);
        
        if (!$transaction) {
            throw new \Exception('Transaction not found');
        }

        $this->validateTransactionData($data, $id);
        
        return $this->transactionRepository->update($transaction, $data);
    }

    /**
     * Delete a transaction
     */
    public function deleteTransaction(int $id): bool
    {
        $transaction = $this->transactionRepository->findById($id);
        
        if (!$transaction) {
            throw new \Exception('Transaction not found');
        }

        return $this->transactionRepository->delete($transaction);
    }

    /**
     * Get transaction by ID
     */
    public function getTransaction(int $id): ?Transaction
    {
        return $this->transactionRepository->findById($id);
    }

    /**
     * Get all transactions with pagination
     */
    public function getAllTransactions(int $perPage = 15): LengthAwarePaginator
    {
        return $this->transactionRepository->getAllPaginated($perPage);
    }

    /**
     * Get transactions by status
     */
    public function getTransactionsByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->transactionRepository->getByStatus($status, $perPage);
    }

    /**
     * Get transactions by merchant
     */
    public function getTransactionsByMerchant(int $merchantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->transactionRepository->getByMerchant($merchantId, $perPage);
    }

    /**
     * Get transactions by terminal
     */
    public function getTransactionsByTerminal(int $terminalId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->transactionRepository->getByTerminal($terminalId, $perPage);
    }

    /**
     * Get transactions by user
     */
    public function getTransactionsByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->transactionRepository->getByUser($userId, $perPage);
    }

    /**
     * Get transactions by date range
     */
    public function getTransactionsByDateRange(string $startDate, string $endDate, int $perPage = 15): LengthAwarePaginator
    {
        return $this->transactionRepository->getByDateRange($startDate, $endDate, $perPage);
    }

    /**
     * Get transactions by payment method
     */
    public function getTransactionsByMethod(string $method, int $perPage = 15): LengthAwarePaginator
    {
        return $this->transactionRepository->getByMethod($method, $perPage);
    }

    /**
     * Search transactions
     */
    public function searchTransactions(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->transactionRepository->search($query, $perPage);
    }

    /**
     * Get transaction statistics
     */
    public function getStatistics(): array
    {
        return $this->transactionRepository->getStatistics();
    }

    /**
     * Get transaction statistics for a merchant
     */
    public function getStatisticsForMerchant(string $merchantId): array
    {
        return $this->transactionRepository->getStatisticsForMerchant($merchantId);
    }

    /**
     * Get transaction statistics by type
     */
    public function getStatisticsByType(string $type): array
    {
        return $this->transactionRepository->getStatisticsByType($type);
    }

    /**
     * Get transaction statistics by type for a merchant
     */
    public function getStatisticsByTypeForMerchant(string $merchantId, string $type): array
    {
        return $this->transactionRepository->getStatisticsByTypeForMerchant($merchantId, $type);
    }

    /**
     * Get transactions by batch number
     */
    public function getTransactionsByBatchNumber(string $batchNo): Collection
    {
        return $this->transactionRepository->getByBatchNumber($batchNo);
    }

    /**
     * Get transaction by trace number
     */
    public function getTransactionByTraceNumber(string $traceNo): ?Transaction
    {
        return $this->transactionRepository->getByTraceNumber($traceNo);
    }

    /**
     * Get transaction by RRN
     */
    public function getTransactionByRRN(string $rrn): ?Transaction
    {
        return $this->transactionRepository->getByRRN($rrn);
    }

    /**
     * Get recent transactions
     */
    public function getRecentTransactions(int $limit = 10): Collection
    {
        return $this->transactionRepository->getRecent($limit);
    }

    /**
     * Get transaction counts by status
     */
    public function getTransactionCountsByStatus(): array
    {
        return $this->transactionRepository->getCountByStatus();
    }

    /**
     * Get transaction counts by method
     */
    public function getTransactionCountsByMethod(): array
    {
        return $this->transactionRepository->getCountByMethod();
    }

    /**
     * Validate transaction data
     */
    private function validateTransactionData(array $data, ?int $transactionId = null): void
    {
        $rules = [
            'batch_no' => 'nullable|string|max:255',
            'trace_no' => 'nullable|string|max:255',
            'rrn' => 'nullable|string|max:255',
            'auth_code' => 'nullable|string|max:255',
            'mid' => 'nullable|string|max:255',
            'tid' => 'nullable|string|max:255',
            'transaction_id' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'transaction_datetime' => 'nullable|date',
            'invoice_no' => 'nullable|string|max:255',
            'card_number' => 'nullable|string|max:255',
            'expiry' => 'nullable|string|max:255',
            'method' => 'nullable|string|max:255',
            'ref_no' => 'nullable|string|max:255',
            'atc' => 'nullable|string|max:255',
            'tvr' => 'nullable|string|max:255',
            'app_name' => 'nullable|string|max:255',
            'tsi' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'sdk' => 'nullable|string|max:255',
            'terminal_id' => 'nullable|uuid', // UUID from AuthService
            'merchant_id' => 'nullable|uuid',
            // 'user_id' => 'nullable|exists:users,id',
        ];

        // Add unique validation for transaction_id if updating
        if ($transactionId) {
            $rules['transaction_id'] = 'nullable|string|max:255|unique:transactions,transaction_id,' . $transactionId;
        } else {
            $rules['transaction_id'] = 'nullable|string|max:255|unique:transactions,transaction_id';
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Get filtered transactions
     */
    public function getFilteredTransactions(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->transactionRepository->getAll();

        // Apply filters
        if (isset($filters['status']) && $filters['status']) {
            $query = $query->where('state', $filters['status']);
        }

        if (isset($filters['merchant_id']) && $filters['merchant_id']) {
            $query = $query->where('merchant_id', $filters['merchant_id']);
        }

        if (isset($filters['terminal_id']) && $filters['terminal_id']) {
            $query = $query->where('terminal_id', $filters['terminal_id']);
        }

        if (isset($filters['method']) && $filters['method']) {
            $query = $query->where('method', $filters['method']);
        }

        if (isset($filters['start_date']) && $filters['start_date']) {
            $query = $query->where('transaction_datetime', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date']) && $filters['end_date']) {
            $query = $query->where('transaction_datetime', '<=', $filters['end_date']);
        }

        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query = $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'LIKE', "%{$search}%")
                  ->orWhere('rrn', 'LIKE', "%{$search}%")
                  ->orWhere('auth_code', 'LIKE', "%{$search}%")
                  ->orWhere('invoice_no', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Process POS transaction
     */
    public function processPosTransaction(array $data)
    {
        try {
            // Begin database transaction
            DB::beginTransaction();

            // Log::info('Merchant Details', [
            //     'merchant_id' => Auth::guard('external')->user()->merchant_id,
            //     'terminal_id' => $data['merchantDetails']['terminalId']
            // ]);
            // dd($data);
            // Get terminal_id from the authenticated user
            $terminalId = Auth::guard('external')->user()->current_terminal_id ?? $data['merchantDetails']['terminalId'] ?? null;
            if (!$terminalId) { 
                DB::rollback();
                throw new \Exception('No active terminal session', 404);
            }

            // Check for duplicate transaction
            $duplicateCheck = $this->checkForDuplicateTransaction(
                $terminalId,
                $data['transactionDetails']['amount'],
                $data['paymentMethod']['panToken'],
                $data['transactionDetails']['timestamp']
            );

            if ($duplicateCheck['is_duplicate']) {
                DB::rollback();
                throw new \Exception('Duplicate transaction detected. A transaction with the same amount, card, and terminal was processed within the last minute.', 409);
            }

            // Validate currency from local database
            $currency = Currency::find($data['transactionDetails']['currency']);
            $currencyData = $currency ? [
                'id' => $currency->id,
                'country' => $currency->country,
                'name' => $currency->name,
                'symbol' => $this->resolveCurrencySymbol($currency),
                'currency_code' => $this->resolveCurrencyCode($currency),
            ] : null;
            
            if (!$currencyData) {
                DB::rollback();
                throw new \Exception('Invalid currency ID or unable to fetch currency details from database', 400);
            }

            // dd($data['paymentMethod']['paymentChannel']);
            // Create payment method (same as card payment)
            $paymentMethod = PaymentMethod::create([
                'entry_mode' => $data['paymentMethod']['entryMode'],
                'pan_token' => $data['paymentMethod']['panToken'],
                'cardholder_name' => $data['paymentMethod']['cardholderName'] ?? null,
                'expiry_month' => $data['paymentMethod']['expiryMonth'] ?? null,
                'expiry_year' => $data['paymentMethod']['expiryYear'] ?? null,
                'payment_channel' => $data['paymentMethod']['paymentChannel'] ?? null, // e.g. Google Pay, Apple Pay, Samsung Pay
            ]); 

            // Generate random invoice number with QR prefix
            $invoiceNo = 'QR-INV-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));

            // Generate unique transaction ID
            $transactionId = 'TXN-' . date('YmdHis') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));

            // Prepare initial transaction data
            $transactionData = [
                'amount' => $data['transactionDetails']['amount'],
                'original_amount' => $data['transactionDetails']['amount'],
                'currency_id' => $data['transactionDetails']['currency'],
                'currency_symbol' => $currencyData['symbol'] ?? null,
                'currency_object' => json_encode($currencyData),
                'transaction_id' => $transactionId,
                'timestamp' => $data['transactionDetails']['timestamp'],
                'transaction_type' => $data['transactionDetails']['transactionType'],
                'payment_method_id' => $paymentMethod->id,
                'merchant_id' => Auth::guard('external')->user()->merchant_id,
                'terminal_id' => $terminalId, // UUID from user's current_terminal_id
                'emv_data' => $data['securityData']['emvData'] ?? null,
                'pin_block' => $data['securityData']['pinBlock'] ?? null,
                'ksn' => $data['securityData']['ksn'] ?? null,
                'status' => 'PENDING',
                'invoice_no' => $invoiceNo,
                'user_id' => Auth::guard('external')->user()->id,
                'card_number' => $data['paymentMethod']['panToken'],
                'expiry' => $data['paymentMethod']['expiryMonth'] . '/' . $data['paymentMethod']['expiryYear'],
                'method' => $data['paymentMethod']['entryMode'],
                // dd(Auth::guard('external')->user()->merchant),
                'country_id' => Auth::guard('external')->user()->merchant?->country_id,
                'payment_type' => 'mobile',
                'partner_id' => $data['partner_id'] ?? null,
                'service_category_id' => $data['service_category_id'] ?? null,
                'service_id' => $data['service_id'] ?? null,
            ];

            // Create initial transaction
            $transaction = Transaction::create($transactionData);

            // Process payment through external gateway/SDK (pass terminal_id)
            $sdkApiResponse = $this->paymentGatewayService->processPayment($transactionData, $terminalId);

            // Update transaction with SDK response data
            $transaction->update([
                'trace_no' => $sdkApiResponse['trace_no'],
                'rrn' => $sdkApiResponse['rrn'],
                'auth_code' => $sdkApiResponse['auth_code'],
                'mid' => $sdkApiResponse['mid'],
                'tid' => $sdkApiResponse['tid'],
                'atc' => $sdkApiResponse['atc'],
                'tvr' => $sdkApiResponse['tvr'],
                'app_name' => $sdkApiResponse['app_name'],
                'tsi' => $sdkApiResponse['tsi'],
                'sdk' => $sdkApiResponse['sdk'],
                'ref_no' => $sdkApiResponse['rrn'],
                'state' => $sdkApiResponse['status'],
                'transaction_datetime' => now(),
            ]);

            // Update transaction status based on API response
            if ($sdkApiResponse['status'] === 'captured') {
                $transaction->markAsCaptured(json_encode($sdkApiResponse));
            } else {
                $transaction->markAsDeclined(json_encode($sdkApiResponse));
            }

            // dd($transaction);
            // Schedule batch update to happen AFTER commit to avoid deadlocks
            DB::afterCommit(function () use ($transaction) {
                $this->updateBatchTotals($transaction);
            });
            
            // Commit the transaction
            DB::commit();

            $encryptedId = encrypt($transaction->id);

            return [
                'message' => 'POS transaction processed successfully',
                'transaction' => $transaction->load('paymentMethod'),
                'sdk_response' => $sdkApiResponse, 
                'transaction_encrypted_id' => $encryptedId,
                'invoice_url' => $this->buildPosInvoiceUrl($encryptedId)
            ];

        } catch (\Exception $e) {
            // Rollback the transaction on any error
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get transactions with filters for authenticated user
     */
    public function getAuthenticatedUserTransactions(array $filters, int $limit = 10)
    {
        // Check if user has an active terminal session
        // if (!Auth::user()->current_terminal_id) {
        //     throw new \Exception('No active terminal session. Please login with a device first.', 400);
        // }

        $query = Transaction::where([
            'merchant_id' => Auth::user()->merchant_id,
            'user_id' => Auth::user()->id,
        ]);
        
        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['batch_id'])) {
            $query->where('batch_id', $filters['batch_id']);
        }

        // Apply search filter
        if (!empty($filters['search'])) {
            $query->where(function($query) use ($filters) {
                $query->orWhere('transaction_id', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('amount', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('card_number', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('ref_no', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Apply date filters
        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }
       
        return $query->with(['merchant'])
            ->latest('transaction_datetime')
            ->paginate($limit);
    }

    /**
     * Get POS transactions with filters
     */
    public function getPosTransactionsFiltered(array $filters, int $perPage = 10)
    {
        $query = Transaction::with('paymentMethod');
        
        // Filter by status if provided
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        return $query->latest()->paginate($perPage);
    }

    /**
     * Get a single POS transaction by ID
     */
    public function getPosTransactionById(int $id)
    {
        return Transaction::with('paymentMethod')->findOrFail($id);
    }

    /**
     * Build printable POS invoice data for a transaction by encrypted ID
     *
     * This is used by external POS invoice print pages (no auth, encrypted ID in URL).
     */
    public function getPosInvoiceDataByEncryptedId(string $encryptedId): array
    {
        try {
            $id = decrypt($encryptedId);
        } catch (\Exception $e) {
            throw new \Exception('Invalid invoice reference', 400);
        }

        $transaction = Transaction::with(['paymentMethod', 'merchant', 'user'])->find($id);

        if (!$transaction) {
            throw new \Exception('Transaction not found', 404);
        }

        // Fetch terminal details locally from SoftPos DB
        $merchantData = null;
        $terminalData = null;

        try {
            if ($transaction->terminal_id) {
                $terminal = Terminal::query()
                    ->where('id', $transaction->terminal_id)
                    ->orWhere('terminal_id', $transaction->terminal_id)
                    ->first();

                if ($terminal) {
                    $terminalData = [
                        'terminal_id' => $terminal->terminal_id ?? $terminal->id,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch local terminal details for POS invoice: ' . $e->getMessage());
        }

        // Merchant details - prefer AuthService data, fallback to local SoftPos merchant
        $merchant = $transaction->merchant;
        $shopData = [
            'name' => $merchantData['name'] ?? $merchant->name ?? $merchant->business_name ?? 'Shop',
            'phone' => $merchantData['phone'] ?? $merchant->phone ?? '',
            'address' => $merchantData['address'] ?? $merchant->address ?? '',
            'email' => $merchantData['email'] ?? $merchant->email ?? '',
            'merchant_code' => $merchantData['code'] ?? $merchant->merchant_code ?? null,
        ];

        // Customer details (if available from metadata or payment link)
        $customerData = null;
        $metadata = $transaction->metadata ?? [];

        $customerName = $metadata['customer_name'] ?? $metadata['customer']['name'] ?? null;
        $customerPhone = $metadata['customer_phone'] ?? $metadata['customer']['phone'] ?? null;

        if ($customerName || $customerPhone) {
            $customerData = [
                'name' => $customerName ?? 'Customer',
                'phone' => $customerPhone ?? null,
            ];
        }

        // Currency and amounts
        $currencyObject = $transaction->currency_object ?? null;
        if (is_string($currencyObject)) {
            $currencyObject = json_decode($currencyObject, true);
        }
        $currencySymbol = $transaction->currency_symbol ?? ($currencyObject['symbol'] ?? null) ?? '$';

        $amount = (float) ($transaction->amount ?? 0);

        // Build simplified footer (no VAT breakdown yet for card transactions)
        $invoiceFooter = [
            'subtotal' => round($amount, 2),
            'subtotal_after_discount' => round($amount, 2),
            'total_discount' => 0.0,
            'total_tax' => 0.0,
            'tax_percentage' => 0.0,
            'vat_breakdown' => [],
            'shipping_cost' => 0.0,
            'grand_total' => round($amount, 2),
            'calculated_grand_total' => round($amount, 2),
            'paid_amount' => round($amount, 2),
            'due_amount' => 0.0,
        ];

        [$cardMasked, $expiryResolved] = $this->resolveInvoiceCardAndExpiry($transaction);

        // Build invoice-like payload
        return [
            'id' => $transaction->id,
            'transaction_id' => $transaction->transaction_id ?? null,
            'reference_no' => $transaction->invoice_no ?? $transaction->transaction_id ?? 'N/A',
            'receipt_number' => $transaction->invoice_no ?? $transaction->transaction_id ?? ('RCPT-' . $transaction->id),
            'date' => optional($transaction->transaction_datetime ?? $transaction->created_at)->format('Y-m-d'),
            'time' => optional($transaction->transaction_datetime ?? $transaction->created_at)->format('H:i:s'),
            'created_at' => optional($transaction->created_at)->toIso8601String(),
            'payment_status' => $transaction->status ?? 'PENDING',
            'payment_method' => $transaction->method ?? 'Card',
            'payment_type' => $transaction->transaction_type ?? $transaction->payment_type ?? $transaction->type ?? 'purchase',
            'total_qty' => 1,
            'currency_symbol' => $currencySymbol,
            'currency_object' => $currencyObject,
            'amount' => $amount,
            'grand_total' => $amount,
            'customer' => $customerData,
            'shop' => $shopData,
            'products' => [], // Card POS transaction has no product line items
            'card_number' => $cardMasked,
            'expiry' => $expiryResolved,
            'merchant_id' => $merchantData['code'] ?? $merchant->merchant_code ?? $transaction->merchant_id ?? null,
            'terminal_id' => $transaction->terminal_id ?? $terminalData['terminal_id'] ?? null,
            'ref_number' => $transaction->ref_no ?? $transaction->rrn ?? null,
            'invoice_url' => $this->buildPosInvoiceUrl($encryptedId),
            'powered_by' => 'CoreNet Technologies',
            'footer' => $invoiceFooter,
        ];
    }

    /**
     * Map payment-link metadata line_items (price in smallest currency unit — same as checkout / Stripe) to invoice products[].
     *
     * @param  array<int, mixed>  $lineItems
     * @return array<int, array{name: string, qty: int, net_unit_price: float, total: float}>
     */
    private function mapPaymentLinkLineItemsToProducts(array $lineItems): array
    {
        $products = [];
        foreach ($lineItems as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = (string) ($row['name'] ?? 'Item');
            $qty = (int) ($row['quantity'] ?? $row['qty'] ?? 1);
            if ($qty < 1) {
                $qty = 1;
            }
            $priceSmallest = (int) ($row['price'] ?? 0);
            $unitMajor = round($priceSmallest / 100, 2);
            $lineTotal = round($unitMajor * $qty, 2);
            $image = $row['image'] ?? null;
            $image = is_string($image) && $image !== '' ? $image : null;
            $products[] = [
                'name' => $name,
                'qty' => $qty,
                'net_unit_price' => $unitMajor,
                'total' => $lineTotal,
                'image' => $image,
            ];
        }

        return $products;
    }

    /**
     * Build printable payment-link invoice data by payment link UUID.
     */
    public function getPaymentLinkInvoiceDataByUuid(string $uuid): array
    {
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $uuid)) {
            throw new \Exception('Invalid payment link UUID', 400);
        }

        $paymentLink = PaymentByLink::query()->where('uuid', $uuid)->first();

        $transaction = Transaction::with(['paymentMethod', 'merchant', 'user'])
            ->where('transaction_type', 'sale')
            ->where(function ($query) use ($uuid, $paymentLink) {
                $query->where('metadata->payment_link_uuid', $uuid);
                if ($paymentLink) {
                    $query->orWhere('metadata->payment_link_id', (string) $paymentLink->id)
                        ->orWhere('metadata->payment_link_id', $paymentLink->id);
                }
            })
            ->latest('id')
            ->first();

        if (!$transaction) {
            throw new \Exception('Payment link transaction not found', 404);
        }

        $merchantData = null;
        $terminalData = null;

        try {
            if ($transaction->terminal_id) {
                $terminal = Terminal::query()
                    ->where('id', $transaction->terminal_id)
                    ->orWhere('terminal_id', $transaction->terminal_id)
                    ->first();

                if ($terminal) {
                    $terminalData = [
                        'terminal_id' => $terminal->terminal_id ?? $terminal->id,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch local terminal details for payment-link invoice: ' . $e->getMessage());
        }

        $merchant = $transaction->merchant;
        $shopData = [
            'name' => $merchantData['name'] ?? $merchant->name ?? $merchant->business_name ?? 'Shop',
            'phone' => $merchantData['phone'] ?? $merchant->phone ?? '',
            'address' => $merchantData['address'] ?? $merchant->address ?? '',
            'email' => $merchantData['email'] ?? $merchant->email ?? '',
            'merchant_code' => $merchantData['code'] ?? $merchant->merchant_code ?? null,
        ];

        $metadata = is_array($transaction->metadata)
            ? $transaction->metadata
            : (json_decode((string) $transaction->metadata, true) ?: []);

        $customerName = $metadata['customer_name'] ?? $paymentLink->customer_name ?? null;
        $customerPhone = $metadata['customer_phone'] ?? $paymentLink->customer_phone ?? null;
        $customerData = null;
        if ($customerName || $customerPhone) {
            $customerData = [
                'name' => $customerName ?? 'Customer',
                'phone' => $customerPhone ?? null,
            ];
        }

        $currencyObject = $transaction->currency_object ?? null;
        if (is_string($currencyObject)) {
            $currencyObject = json_decode($currencyObject, true);
        }
        $currencySymbol = $transaction->currency_symbol ?? ($currencyObject['symbol'] ?? null) ?? '$';
        $amount = (float) ($transaction->amount ?? 0);

        $linkMetadata = [];
        if ($paymentLink && is_array($paymentLink->metadata)) {
            $linkMetadata = $paymentLink->metadata;
        }

        $lineItemsSource = [];
        if (! empty($metadata['line_items']) && is_array($metadata['line_items'])) {
            $lineItemsSource = $metadata['line_items'];
        } elseif (! empty($linkMetadata['line_items']) && is_array($linkMetadata['line_items'])) {
            $lineItemsSource = $linkMetadata['line_items'];
        }

        $products = $this->mapPaymentLinkLineItemsToProducts($lineItemsSource);
        $totalQty = 1;
        if ($products !== []) {
            $totalQty = max(1, (int) array_sum(array_column($products, 'qty')));
        }

        // Receipt totals: prefer payment-link metadata (same keys as checkout), else derive from line items / charged amount.
        $sumLines = $products !== []
            ? round((float) array_sum(array_column($products, 'total')), 2)
            : round($amount, 2);
        $subtotal = $sumLines;
        if (isset($linkMetadata['subtotal']) && $linkMetadata['subtotal'] !== '' && $linkMetadata['subtotal'] !== null) {
            $subtotal = round((float) $linkMetadata['subtotal'], 2);
        }

        $totalTax = 0.0;
        $taxRaw = $linkMetadata['tax_total'] ?? $linkMetadata['tax'] ?? null;
        if ($taxRaw !== null && $taxRaw !== '') {
            $totalTax = round((float) $taxRaw, 2);
        }

        $shippingCost = 0.0;
        $shipRaw = $linkMetadata['shipping'] ?? $linkMetadata['shipping_total'] ?? null;
        if ($shipRaw !== null && $shipRaw !== '') {
            $shippingCost = round((float) $shipRaw, 2);
        }

        $grandTotal = round($amount, 2);

        [$cardMasked, $expiryResolved] = $this->resolveInvoiceCardAndExpiry($transaction);

        $invoiceFooter = [
            'subtotal' => $subtotal,
            'subtotal_after_discount' => $subtotal,
            'total_discount' => 0.0,
            'total_tax' => $totalTax,
            'tax_percentage' => isset($linkMetadata['tax_rate']) ? (float) $linkMetadata['tax_rate'] : 0.0,
            'vat_breakdown' => [],
            'shipping_cost' => $shippingCost,
            'grand_total' => $grandTotal,
            'calculated_grand_total' => $grandTotal,
            'paid_amount' => $grandTotal,
            'due_amount' => 0.0,
        ];

        return [
            'id' => $transaction->id,
            'transaction_id' => $transaction->transaction_id ?? null,
            'reference_no' => $transaction->invoice_no ?? $transaction->transaction_id ?? 'N/A',
            'receipt_number' => $transaction->invoice_no ?? $transaction->transaction_id ?? ('RCPT-' . $transaction->id),
            'date' => optional($transaction->transaction_datetime ?? $transaction->created_at)->format('Y-m-d'),
            'time' => optional($transaction->transaction_datetime ?? $transaction->created_at)->format('H:i:s'),
            'created_at' => optional($transaction->created_at)->toIso8601String(),
            'payment_status' => $transaction->status ?? 'APPROVED',
            'payment_method' => $transaction->method ?? 'Card',
            'payment_type' => 'payment_link',
            'transaction_type' => 'payment_link',
            'total_qty' => $totalQty,
            'currency_symbol' => $currencySymbol,
            'currency_object' => $currencyObject,
            'amount' => $amount,
            'grand_total' => $amount,
            'customer' => $customerData,
            'shop' => $shopData,
            'products' => $products,
            'card_number' => $cardMasked,
            'expiry' => $expiryResolved,
            'merchant_id' => $merchantData['code'] ?? $merchant->merchant_code ?? $transaction->merchant_id ?? null,
            'terminal_id' => $transaction->terminal_id ?? $terminalData['terminal_id'] ?? null,
            'ref_number' => $transaction->ref_no ?? $transaction->rrn ?? null,
            'invoice_url' => $this->buildPaymentLinkInvoiceUrl($uuid),
            'powered_by' => 'CoreNet Technologies',
            'footer' => $invoiceFooter,
        ];
    }

    /**
     * Register a new terminal
     * 
     * Note: Terminal management has been moved to AuthService.
     * This method is deprecated and should not be used.
     * Please use AuthService API for terminal registration.
     * 
     * @deprecated Use AuthService API for terminal management
     * @throws \Exception
     */
    public function registerTerminal(array $data)
    {
        throw new \Exception(
            'Terminal registration has been moved to AuthService. ' .
            'Please use the AuthService API to register terminals.'
        );
    }

    /**
     * Get user transactions with filters
     */
    public function getUserTransactionsFiltered(array $filters, int $limit = 10)
    {
        $query = Transaction::where('user_id', Auth::user()->id);

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply search filter
        if (!empty($filters['search'])) {
            $query->where(function($query) use ($filters) {
                $query->orWhere('transaction_id', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('amount', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('card_number', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('ref_no', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Apply date filters
        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }
       
        return $query->with(['merchant', 'user'])
            ->latest('transaction_datetime')
            ->paginate($limit);
    }

    private function resolveCurrencyCode(Currency $currency): string
    {
        return $currency->getTranslation('currency_code', app()->getLocale(), false)
            ?? $currency->getTranslation('currency_code', 'en', false)
            ?? '';
    }

    private function resolveCurrencySymbol(Currency $currency): string
    {
        return $currency->getTranslation('symbol', app()->getLocale(), false)
            ?? $currency->getTranslation('symbol', 'en', false)
            ?? '';
    }

    /**
     * Update batch totals after transaction commits to prevent deadlocks
     */
    private function updateBatchTotals($transaction)
    {
        try {
            $merchantId = $transaction->merchant_id;
            $currency = $transaction->currency_id;
            $currencySymbol = $transaction->currency_symbol;
            $amount = $transaction->amount;
            // dd($merchantId, $transaction->user_id, $batch);

            // dd($merchantId, $currency, $amount, $transaction);
            // Use a separate transaction for batch update to avoid deadlocks
            DB::transaction(function () use ($merchantId, $currency, $currencySymbol, $amount, $transaction) {
                // Get or create the batch per merchant and user
                $batch = Batch::getOrCreateDailyBatch($merchantId, $transaction->user_id, $currency, $currencySymbol);

                // dd($batch);
                // Atomic increment to avoid race conditions
                DB::table('batches')
                    ->where('id', $batch->id)
                    ->where('user_id', $transaction->user_id)
                    ->where('merchant_id', $merchantId)
                    ->update([
                        'total_amount' => DB::raw('total_amount + ' . (float) $amount),
                        'transaction_count' => DB::raw('transaction_count + 1'),
                        'updated_at' => now(),
                    ]);

                // Update transaction with batch info
                $transaction->update([
                    'batch_id' => $batch->id,
                    'batch_no' => $batch->batch_number,
                ]);

                Log::info("Batch updated successfully", [
                    'batch_id' => $batch->id,
                    'transaction_id' => $transaction->id,
                    'amount_added' => $amount,
                    'new_total' => $batch->total_amount + $amount
                ]);
            });

        } catch (\Exception $e) {
            dd($e->getMessage());
            Log::error("Failed to update batch totals", [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'merchant_id' => $transaction->merchant_id,
                'currency_id' => $transaction->currency_id
            ]);
        }
    }

    /**
     * Check for duplicate transactions based on terminal, amount, pan_token, and time
     */
    private function checkForDuplicateTransaction($terminalId, $amount, $panToken, $timestamp)
    {
        try {
            // Convert timestamp to Carbon instance
            $transactionTime = Carbon::parse($timestamp);
            
            // Get the start of the minute for the transaction time
            $minuteStart = $transactionTime->copy()->startOfMinute();
            $minuteEnd = $transactionTime->copy()->endOfMinute();

            // Check for existing transaction with same criteria within the same minute
            $duplicateTransaction = Transaction::where('terminal_id', $terminalId)
                ->where('amount', $amount)
                ->where('card_number', $panToken)
                ->whereBetween('created_at', [$minuteStart, $minuteEnd])
                ->whereIn('status', ['PENDING', 'APPROVED', 'CAPTURED', 'PROCESSED'])
                ->first();

            if ($duplicateTransaction) {
                return [
                    'is_duplicate' => true,
                    'duplicate_transaction' => $duplicateTransaction,
                    'message' => 'Found duplicate transaction within the same minute'
                ];
            }

            return [
                'is_duplicate' => false,
                'duplicate_transaction' => null,
                'message' => 'No duplicate transaction found'
            ];

        } catch (\Exception $e) {
            // Log the error but don't fail the transaction
            Log::error('Error checking for duplicate transaction: ' . $e->getMessage(), [
                'terminal_id' => $terminalId,
                'amount' => $amount,
                'pan_token' => $panToken,
                'timestamp' => $timestamp
            ]);

            // Return false to allow transaction to proceed if duplicate check fails
            return [
                'is_duplicate' => false,
                'duplicate_transaction' => null,
                'message' => 'Duplicate check failed, allowing transaction to proceed'
            ];
        }
    }

    /**
     * Process QR Code Payment Transaction
     * Similar to POS card payment but with QR code data
     * 
     * @param array $data Payment data from QR code scan
     * @return array Transaction result with QR payment details
     */
    public function processQrPayment(array $data)
    {
        try {
            // Begin database transaction
            DB::beginTransaction();

            // Get terminal_id from the authenticated user
            $terminalId = Auth::guard('external')->user()->current_terminal_id ?? $data['merchantDetails']['terminalId'] ?? null;
            if (!$terminalId) { 
                DB::rollback();
                throw new \Exception('No active terminal session', 404);
            }

            // Check for duplicate transaction using PAN token as identifier
            $duplicateCheck = $this->checkForDuplicateQrTransaction(
                $terminalId,
                $data['transactionDetails']['amount'],
                $data['paymentMethod']['panToken'],
                $data['transactionDetails']['timestamp']
            );

            if ($duplicateCheck['is_duplicate']) {
                DB::rollback();
                throw new \Exception('Duplicate transaction detected. A QR payment with the same amount and PAN token was processed within the last minute.', 409);
            }

            // Validate currency from local database
            $currency = Currency::find($data['transactionDetails']['currency']);
            $currencyData = $currency ? [
                'id' => $currency->id,
                'country' => $currency->country,
                'name' => $currency->name,
                'symbol' => $this->resolveCurrencySymbol($currency),
                'currency_code' => $this->resolveCurrencyCode($currency),
            ] : null;
            
            if (!$currencyData) {
                DB::rollback();
                throw new \Exception('Invalid currency ID or unable to fetch currency details from database', 400);
            }

            // dd($data['paymentMethod']['paymentChannel']);
            // Create payment method (same as card payment)
            $paymentMethod = PaymentMethod::create([
                'entry_mode' => $data['paymentMethod']['entryMode'],
                'pan_token' => $data['paymentMethod']['panToken'],
                'cardholder_name' => $data['paymentMethod']['cardholderName'] ?? null,
                'expiry_month' => $data['paymentMethod']['expiryMonth'] ?? null,
                'expiry_year' => $data['paymentMethod']['expiryYear'] ?? null,
                'payment_channel' => $data['paymentMethod']['paymentChannel'] ?? null, // e.g. Google Pay, Apple Pay, Samsung Pay
            ]);

            // Generate random invoice number with QR prefix
            $invoiceNo = 'QR-INV-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));

            // Generate unique transaction ID with QR prefix
            $transactionId = 'QR-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));

            // Generate RRN (Retrieval Reference Number) - 12 digits
            $rrn = date('ymd') . substr(str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT), 0, 6);

            // Generate trace number - 6 digits
            $traceNo = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Generate auth code - 6 digits (simulated approval)
            $authCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Prepare additional data
            $description = $data['additionalData']['description'] ?? 'QR Code Payment';
            $orderNumber = $data['additionalData']['orderNumber'] ?? null;
            $notes = $data['additionalData']['notes'] ?? null;

            // Simulate QR payment gateway call (in production, call actual QR payment gateway)
            $qrGatewayResponse = $this->simulateQrPaymentGateway($data);

            // Determine transaction status based on gateway response
            $transactionStatus = $qrGatewayResponse['status'] ?? 'APPROVED';
            $declineReason = $qrGatewayResponse['decline_reason'] ?? null;
            $errorMessage = $qrGatewayResponse['error_message'] ?? null;

            // Create transaction record (batch will be assigned later via updateBatchTotals)
            $transaction = Transaction::create([
                'trace_no' => $traceNo,
                'rrn' => $rrn,
                'auth_code' => $authCode,
                'transaction_id' => $transactionId,
                'state' => $transactionStatus,
                'status' => strtolower($transactionStatus),
                'transaction_type' => $data['transactionDetails']['transactionType'],
                'description' => $description,
                'transaction_datetime' => $data['transactionDetails']['timestamp'],
                'invoice_no' => $invoiceNo,
                'amount' => $data['transactionDetails']['amount'],
                'currency_id' => $currencyData['id'],
                'terminal_id' => $terminalId,
                'merchant_id' => Auth::guard('external')->user()->merchant_id,
                'user_id' => Auth::guard('external')->id(),
                'payment_method_id' => $paymentMethod->id,
                
                // Mark as QR payment
                'method' => 'qr',
                'payment_type' => 'qr',
                'card_number' => $data['paymentMethod']['panToken'],
                'expiry' => ($data['paymentMethod']['expiryMonth'] ?? '') . '/' . ($data['paymentMethod']['expiryYear'] ?? ''),
                'order_number' => $orderNumber,
                'notes' => $notes,
                
                // Status fields
                'decline_reason' => $declineReason,
                'error_message' => $errorMessage,
            ]);

            // Schedule batch update to happen AFTER commit (same as card payments)
            if ($transactionStatus === 'APPROVED') {
                DB::afterCommit(function () use ($transaction) {
                    $this->updateBatchTotals($transaction);
                });
            }

            DB::commit();

            // Log transaction creation for debugging
            Log::info('QR Payment Transaction Created', [
                'transaction_id' => $transaction->transaction_id,
                'status' => $transaction->status,
                'state' => $transaction->state,
                'gateway_status' => $transactionStatus,
            ]);

            // Return same structure as card payment (processPosTransaction)
            $encryptedId = encrypt($transaction->id);

            return [
                'message' => 'QR payment processed successfully',
                'transaction' => $transaction->load('paymentMethod'),
                'sdk_response' => $qrGatewayResponse,
                'transaction_encrypted_id' => $encryptedId,
                'invoice_url' => $this->buildPosInvoiceUrl($encryptedId)
            ];

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('QR Payment Processing Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Check for duplicate QR transaction
     * Uses PAN token instead of QR code for duplicate detection
     */
    private function checkForDuplicateQrTransaction($terminalId, $amount, $panToken, $timestamp)
    {
        try {
            // Check for transactions with same terminal, amount, and PAN token within last 1 minute
            $oneMinuteAgo = Carbon::parse($timestamp)->subMinute();
            
            $duplicate = Transaction::where('terminal_id', $terminalId)
                ->where('amount', $amount)
                ->where('card_number', $panToken)
                ->where('method', 'qr')
                ->where('created_at', '>=', $oneMinuteAgo)
                ->first();

            if ($duplicate) {
                return [
                    'is_duplicate' => true,
                    'duplicate_transaction' => $duplicate,
                    'message' => 'Duplicate QR transaction detected'
                ];
            }

            return [
                'is_duplicate' => false,
                'duplicate_transaction' => null,
                'message' => 'No duplicate found'
            ];
        } catch (\Exception $e) {
            Log::error('Duplicate QR transaction check failed', [
                'error' => $e->getMessage(),
                'terminal_id' => $terminalId,
                'amount' => $amount,
                'pan_token' => $panToken,
                'timestamp' => $timestamp
            ]);

            return [
                'is_duplicate' => false,
                'duplicate_transaction' => null,
                'message' => 'Duplicate check failed, allowing transaction to proceed'
            ];
        }
    }

    /**
     * Simulate QR Payment Gateway Call
     * In production, replace this with actual QR payment gateway integration
     * Uses same structure as card payment but with QR method
     */
    private function simulateQrPaymentGateway(array $data)
    {
        // Simulate gateway processing delay
        // usleep(500000); // 0.5 second delay (commented for faster response)

        // Simulate 95% approval rate
        $isApproved = (rand(1, 100) <= 95);

        if ($isApproved) {
            return [
                'status' => 'APPROVED',
                'gateway_transaction_id' => 'QRG-' . strtoupper(substr(md5(uniqid()), 0, 16)),
                'gateway_response_code' => '00',
                'gateway_response_message' => 'QR payment approved',
                'entry_mode' => $data['paymentMethod']['entryMode'],
                'pan_token' => $data['paymentMethod']['panToken'],
                'processed_at' => now()->toIso8601String()
            ];
        } else {
            $declineReasons = [
                'Insufficient balance',
                'Payment method declined',
                'Invalid payment data',
                'Gateway temporarily unavailable',
                'Transaction limit exceeded'
            ];

            return [
                'status' => 'DECLINED',
                'gateway_response_code' => '05',
                'gateway_response_message' => 'QR payment declined',
                'decline_reason' => $declineReasons[array_rand($declineReasons)],
                'processed_at' => now()->toIso8601String()
            ];
        }
    }

    /**
     * Get QR transactions with filters
     */
    public function getQrTransactionsFiltered(array $filters, int $perPage = 10)
    {
        $query = Transaction::with(['terminal', 'merchant', 'currency', 'batch'])
            ->where('method', 'qr');

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', strtolower($filters['status']));
        }

        if (isset($filters['qr_type'])) {
            $query->where('qr_type', $filters['qr_type']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('transaction_id', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('qr_code', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('customer_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('invoice_no', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        // Get authenticated user's merchant transactions only
        $merchantId = Auth::guard('external')->user()->merchant_id;
        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get QR transaction by ID
     */
    public function getQrTransactionById($id)
    {
        $transaction = Transaction::with(['terminal', 'merchant', 'currency', 'batch', 'user'])
            ->where('payment_method', 'qr_code')
            ->findOrFail($id);

        // Check if user has access to this transaction
        $merchantId = Auth::guard('external')->user()->merchant_id;
        if ($transaction->merchant_id != $merchantId) {
            throw new \Exception('Unauthorized access to this transaction', 403);
        }

        return $transaction;
    }
} 