<?php

namespace App\Modules\CustomerAuth\Services;

use App\Exceptions\RecipientWalletException;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Service;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Modules\CustomerAuth\Notifications\CustomerNotificationType;
use App\Services\IdempotencyService;
use App\Services\WalletBillPaymentService;
use App\Services\WalletService;
use Carbon\Carbon;
use InvalidArgumentException;

class CustomerWalletService
{
    public const SCOPE_TRANSFER = 'wallet.transfer';

    public const SCOPE_WITHDRAW = 'wallet.withdraw';

    public const SCOPE_BILL_PAYMENT = 'wallet.bill_payment';

    public function __construct(
        private readonly WalletService $walletService,
        private readonly IdempotencyService $idempotencyService,
        private readonly CustomerSystemNotificationService $customerSystemNotificationService,
        private readonly WalletTransferOtpService $walletTransferOtpService,
        private readonly WalletBillPaymentService $walletBillPaymentService,
    ) {
    }

    public function dashboard(Customer $customer): array
    {
        $this->assertCustomerCanUseWallet($customer);

        $wallet = $this->resolveActiveWalletForCustomer($customer);

        $recentTransactions = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (WalletTransaction $transaction) => $this->formatTransaction($transaction))
            ->values()
            ->all();

        return [
            'wallet' => $this->formatWallet($wallet),
            'last_transaction' => $recentTransactions[0] ?? null,
            'recent_transactions' => $recentTransactions,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function transactions(Customer $customer, array $filters = []): array
    {
        $this->assertCustomerCanUseWallet($customer);

        $wallet = $this->resolveActiveWalletForCustomer($customer);
        $perPage = (int) ($filters['per_page'] ?? 15);
        $page = (int) ($filters['page'] ?? 1);

        $query = WalletTransaction::query()
            ->where('wallet_id', $wallet->id);

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }

        if (! empty($filters['date_from'])) {
            $from = Carbon::parse($filters['date_from']);
            $query->where('created_at', '>=', $this->isDateOnly($filters['date_from'])
                ? $from->copy()->startOfDay()
                : $from);
        }

        if (! empty($filters['date_to'])) {
            $to = Carbon::parse($filters['date_to']);
            $query->where('created_at', '<=', $this->isDateOnly($filters['date_to'])
                ? $to->copy()->endOfDay()
                : $to);
        }

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', $search)
                    ->orWhere('note', 'like', $search)
                    ->orWhere('reference', 'like', $search);
            });
        }

        $paginator = $query->latest('id')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()
                ->map(fn (WalletTransaction $transaction) => $this->formatTransaction($transaction))
                ->values()
                ->all(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    private function isDateOnly(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value)) === 1;
    }

    public function queryRecipient(Customer $sender, string $identifier): array
    {
        $this->assertCustomerCanUseWallet($sender);

        $fromWallet = $this->resolveActiveWalletForCustomer($sender);
        $this->assertNotSelfIdentifier($fromWallet, $sender, $identifier);

        $recipientWallet = $this->walletService->resolveRecipient(
            $identifier,
            $fromWallet,
            allowPhone: true,
            selfWalletContext: 'query'
        );

        if ($recipientWallet->isMaster()) {
            $this->assertWalletIsActive($recipientWallet, 'Recipient wallet is not available to receive transfers.');

            return [
                'wallet_id' => $recipientWallet->wallet_id,
                'name' => 'Master Wallet',
                'email' => null,
                'status' => $recipientWallet->status,
                'currency_code' => $recipientWallet->currency_code,
            ];
        }

        $recipient = $recipientWallet->customer;
        $this->assertRecipientCanReceive($recipient);
        $this->assertWalletIsActive($recipientWallet, 'Recipient wallet is not available to receive transfers.');

        return [
            'wallet_id' => $recipientWallet->wallet_id,
            'name' => $recipient->name,
            'email' => $recipient->email,
            'status' => $recipientWallet->status,
            'currency_code' => $recipientWallet->currency_code,
        ];
    }

    public function resolveRecipient(Customer $sender, string $identifier): array
    {
        $this->assertCustomerCanUseWallet($sender);

        $fromWallet = $this->resolveActiveWalletForCustomer($sender);
        $this->assertNotSelfIdentifier($fromWallet, $sender, $identifier);

        $recipientWallet = $this->walletService->resolveRecipient(
            $identifier,
            $fromWallet,
            allowPhone: true,
            selfWalletContext: 'query'
        );

        if ($recipientWallet->isMaster()) {
            $this->assertWalletIsActive($recipientWallet, 'Recipient wallet is not available to receive transfers.');

            return array_merge(
                $this->walletService->formatRecipient($recipientWallet),
                ['owner_name' => 'Master Wallet']
            );
        }

        $recipient = $recipientWallet->customer;
        $this->assertRecipientCanReceive($recipient);
        $this->assertWalletIsActive($recipientWallet, 'Recipient wallet is not available to receive transfers.');

        return $this->walletService->formatRecipient($recipientWallet);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{otp_token: string, expires_at: string}
     */
    public function issueTransferOtp(Customer $sender, array $payload): array
    {
        $normalized = WalletTransferOtpService::normalizePayload($payload);
        $this->assertTransferIntent($sender, $normalized['recipient_wallet_id'], $normalized['amount']);

        return $this->walletTransferOtpService->issue($sender, $normalized);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{otp_token: string, expires_at: string}
     */
    public function issueBillPaymentOtp(Customer $customer, array $payload): array
    {
        $normalized = WalletTransferOtpService::normalizeBillPaymentPayload($payload);
        $this->assertBillPaymentIntent($customer, $normalized['service_id'], $normalized['product_id'], $normalized['amount']);

        return $this->walletTransferOtpService->issueBillPayment($customer, $normalized);
    }

    /**
     * @param  array<string, mixed>  $servicePayload
     */
    public function payBill(
        Customer $customer,
        string $serviceId,
        string $productId,
        float $amount,
        array $servicePayload = [],
        ?string $description = null,
        ?string $idempotencyKey = null,
        ?string $otpToken = null,
        ?int $otpCode = null
    ): array {
        $billPayload = WalletTransferOtpService::normalizeBillPaymentPayload([
            'service_id' => $serviceId,
            'product_id' => $productId,
            'amount' => $amount,
            'service_payload' => $servicePayload,
            'description' => $description,
            'idempotency_key' => $idempotencyKey,
        ]);

        $cached = $this->idempotencyService->findCached(
            $customer->id,
            self::SCOPE_BILL_PAYMENT,
            $idempotencyKey
        );
        if ($cached !== null) {
            return $cached;
        }

        $this->walletTransferOtpService->verifyBillPaymentAndConsume(
            $customer,
            (string) $otpToken,
            (int) $otpCode,
            $billPayload
        );

        [$service, $product, $wallet] = $this->resolveBillPaymentContext(
            $customer,
            $billPayload['service_id'],
            $billPayload['product_id'],
            $billPayload['amount']
        );

        // Partner HTTP + failed audit rows must commit outside idempotency transaction.
        $result = $this->walletBillPaymentService->process(
            $customer,
            $wallet,
            $service,
            $product,
            $billPayload['amount'],
            $billPayload['service_payload'],
            $billPayload['description'],
            $billPayload['idempotency_key'],
        );

        $serviceName = is_array($service->service_name)
            ? ($service->service_name['en'] ?? reset($service->service_name))
            : (string) $service->service_name;

        $this->customerSystemNotificationService->send(
            $customer->fresh(),
            CustomerNotificationType::BillPayment,
            [
                'amount' => $result['total_debited'],
                'currency' => $wallet->currency_code,
                'service_name' => $serviceName ?: 'bill payment',
            ],
        );

        $response = [
            'amount' => $result['amount'],
            'fee' => $result['fee'],
            'total_debited' => $result['total_debited'],
            'wallet' => $this->formatWallet($result['wallet']),
            'bill_payment' => [
                'id' => $result['bill_payment']->id,
                'status' => $result['bill_payment']->status,
                'partner_reference' => $result['bill_payment']->partner_reference,
                'service_id' => $result['bill_payment']->service_id,
                'partner_id' => $result['bill_payment']->partner_id,
            ],
            'transaction' => $result['transaction']
                ? $this->formatTransaction($result['transaction'])
                : null,
        ];

        if ($idempotencyKey !== null && trim($idempotencyKey) !== '') {
            return $this->idempotencyService->remember(
                $customer->id,
                self::SCOPE_BILL_PAYMENT,
                $idempotencyKey,
                $response
            );
        }

        return $response;
    }

    public function transfer(
        Customer $sender,
        string $recipientWalletId,
        float $amount,
        ?string $description = null,
        ?string $idempotencyKey = null,
        ?string $note = null,
        ?string $otpToken = null,
        ?int $otpCode = null
    ): array {
        $transferPayload = WalletTransferOtpService::normalizePayload([
            'recipient_wallet_id' => $recipientWalletId,
            'amount' => $amount,
            'description' => $description,
            'note' => $note,
            'idempotency_key' => $idempotencyKey,
        ]);

        return $this->idempotencyService->execute(
            $sender->id,
            self::SCOPE_TRANSFER,
            $idempotencyKey,
            function () use ($sender, $transferPayload, $otpToken, $otpCode) {
                $this->walletTransferOtpService->verifyAndConsume(
                    $sender,
                    (string) $otpToken,
                    (int) $otpCode,
                    $transferPayload
                );

                $fromWallet = $this->resolveActiveWalletForCustomer($sender);
                $toWallet = $this->walletService->resolveRecipient(
                    $transferPayload['recipient_wallet_id'],
                    $fromWallet,
                    allowPhone: false,
                    selfWalletContext: 'transfer'
                );
                $toWallet->load('customer');

                return $this->executeTransfer(
                    $sender,
                    $fromWallet,
                    $toWallet,
                    $transferPayload['amount'],
                    $transferPayload['description'],
                    $transferPayload['note']
                );
            }
        );
    }

    public function withdraw(
        Customer $customer,
        float $amount,
        ?string $description = null,
        ?string $idempotencyKey = null
    ): array {
        return $this->idempotencyService->execute(
            $customer->id,
            self::SCOPE_WITHDRAW,
            $idempotencyKey,
            function () use ($customer, $amount, $description) {
                $this->assertCustomerCanUseWallet($customer);

                $wallet = $this->resolveActiveWalletForCustomer($customer);
                $this->assertWalletIsActive($wallet, 'Your wallet is not available for withdrawal.');

                $this->walletService->cashOut($wallet, $amount, $description, 0);

                $wallet->refresh();

                $transaction = WalletTransaction::query()
                    ->where('wallet_id', $wallet->id)
                    ->where('type', 'adjustment')
                    ->where('direction', 'debit')
                    ->latest('id')
                    ->first();

                return [
                    'amount' => round($amount, 2),
                    'description' => $description,
                    'wallet' => $this->formatWallet($wallet),
                    'transaction' => $transaction ? $this->formatTransaction($transaction) : null,
                ];
            }
        );
    }

    private function executeTransfer(
        Customer $sender,
        Wallet $fromWallet,
        Wallet $toWallet,
        float $amount,
        ?string $description,
        ?string $note = null
    ): array {
        if ($fromWallet->id === $toWallet->id) {
            throw new InvalidArgumentException('You cannot transfer money to your own wallet.');
        }

        $recipient = $toWallet->customer;

        if ($toWallet->isMaster()) {
            $this->assertWalletIsActive($fromWallet, 'Your wallet is not available for transfers.');
            $this->assertWalletIsActive($toWallet, 'Recipient wallet is not available to receive transfers.');

            if ($fromWallet->currency_code !== $toWallet->currency_code) {
                throw new InvalidArgumentException('Cross-currency transfers are not supported.');
            }

            $fee = $this->walletService->transferFee();

            $this->walletService->transfer(
                $fromWallet,
                $toWallet,
                $amount,
                $description,
                0,
                $note
            );

            $fromWallet->refresh();
            $toWallet->refresh();

            $debitTransaction = WalletTransaction::query()
                ->where('wallet_id', $fromWallet->id)
                ->whereIn('type', ['transfer', 'payment'])
                ->where('direction', 'debit')
                ->latest('id')
                ->first();

            $recipientNet = round($amount - max(0, $fee), 2);

            $this->customerSystemNotificationService->send(
                $sender->fresh(),
                CustomerNotificationType::TransferSent,
                [
                    'amount' => $amount,
                    'currency' => $fromWallet->currency_code,
                    'recipient_name' => 'Master Wallet',
                ],
            );

            return [
                'amount' => round($amount, 2),
                'fee' => round(max(0, $fee), 2),
                'recipient_amount' => $recipientNet,
                'description' => $description,
                'note' => $note,
                'sender_wallet' => $this->formatWallet($fromWallet),
                'recipient' => array_merge(
                    $this->walletService->formatRecipient($toWallet),
                    ['name' => 'Master Wallet', 'owner_name' => 'Master Wallet']
                ),
                'transaction' => $debitTransaction ? $this->formatTransaction($debitTransaction) : null,
            ];
        }

        if (! $recipient) {
            throw new RecipientWalletException('Recipient wallet is not linked to a customer.');
        }

        $this->assertRecipientCanReceive($recipient);
        $this->assertWalletIsActive($fromWallet, 'Your wallet is not available for transfers.');
        $this->assertWalletIsActive($toWallet, 'Recipient wallet is not available to receive transfers.');

        if ($fromWallet->currency_code !== $toWallet->currency_code) {
            throw new InvalidArgumentException('Cross-currency transfers are not supported.');
        }

        $fee = $this->walletService->transferFee();

        $this->walletService->transfer(
            $fromWallet,
            $toWallet,
            $amount,
            $description,
            0,
            $note
        );

        $fromWallet->refresh();
        $toWallet->refresh();

        $debitTransaction = WalletTransaction::query()
            ->where('wallet_id', $fromWallet->id)
            ->whereIn('type', ['transfer', 'payment'])
            ->where('direction', 'debit')
            ->latest('id')
            ->first();

        $recipientNet = round($amount - max(0, $fee), 2);

        $this->customerSystemNotificationService->send(
            $sender->fresh(),
            CustomerNotificationType::TransferSent,
            [
                'amount' => $amount,
                'currency' => $fromWallet->currency_code,
                'recipient_name' => $recipient->name ?: $recipient->phone,
            ],
        );

        $this->customerSystemNotificationService->send(
            $recipient->fresh(),
            CustomerNotificationType::TransferReceived,
            [
                'amount' => $recipientNet,
                'currency' => $toWallet->currency_code,
                'sender_name' => $sender->name ?: $sender->phone,
            ],
        );

        return [
            'amount' => round($amount, 2),
            'fee' => round(max(0, $fee), 2),
            'recipient_amount' => $recipientNet,
            'description' => $description,
            'note' => $note,
            'sender_wallet' => $this->formatWallet($fromWallet),
            'recipient' => array_merge(
                $this->walletService->formatRecipient($toWallet),
                ['name' => $recipient->name]
            ),
            'transaction' => $debitTransaction ? $this->formatTransaction($debitTransaction) : null,
        ];
    }

    private function assertTransferIntent(Customer $sender, string $recipientWalletId, float $amount): void
    {
        $this->assertCustomerCanUseWallet($sender);

        $fromWallet = $this->resolveActiveWalletForCustomer($sender);
        $this->assertNotSelfIdentifier($fromWallet, $sender, $recipientWalletId);

        $toWallet = $this->walletService->resolveRecipient(
            $recipientWalletId,
            $fromWallet,
            allowPhone: false,
            selfWalletContext: 'transfer'
        );
        $toWallet->load('customer');

        if ($toWallet->isMaster()) {
            $this->assertWalletIsActive($fromWallet, 'Your wallet is not available for transfers.');
            $this->assertWalletIsActive($toWallet, 'Recipient wallet is not available to receive transfers.');

            if ($fromWallet->currency_code !== $toWallet->currency_code) {
                throw new InvalidArgumentException('Cross-currency transfers are not supported.');
            }
        } else {
            $recipient = $toWallet->customer;

            if (! $recipient) {
                throw new RecipientWalletException('Recipient wallet is not linked to a customer.');
            }

            $this->assertRecipientCanReceive($recipient);
            $this->assertWalletIsActive($fromWallet, 'Your wallet is not available for transfers.');
            $this->assertWalletIsActive($toWallet, 'Recipient wallet is not available to receive transfers.');

            if ($fromWallet->currency_code !== $toWallet->currency_code) {
                throw new InvalidArgumentException('Cross-currency transfers are not supported.');
            }
        }

        $this->walletService->assertSufficientBalance($fromWallet, $amount);
    }

    private function assertBillPaymentIntent(
        Customer $customer,
        string $serviceId,
        string $productId,
        float $amount
    ): void {
        $this->resolveBillPaymentContext($customer, $serviceId, $productId, $amount);
    }

    /**
     * @return array{0: Service, 1: Product, 2: Wallet}
     */
    private function resolveBillPaymentContext(
        Customer $customer,
        string $serviceId,
        string $productId,
        float $amount
    ): array {
        $this->assertCustomerCanUseWallet($customer);

        $wallet = $this->resolveActiveWalletForCustomer($customer);
        $this->assertWalletIsActive($wallet, 'Your wallet is not available for bill payment.');

        $service = Service::query()
            ->with('partner')
            ->where('id', $serviceId)
            ->where('is_active', true)
            ->where('status', 'active')
            ->first();

        if (! $service) {
            throw new InvalidArgumentException('Service is not available.');
        }

        if ($service->country_id && $customer->country_id && $service->country_id !== $customer->country_id) {
            throw new InvalidArgumentException('Service is not available in your region.');
        }

        $product = Product::query()
            ->where('id', $productId)
            ->where('service_id', $service->id)
            ->where('status', true)
            ->first();

        if (! $product) {
            throw new InvalidArgumentException('Product is not available for this service.');
        }

        $partner = $service->partner;
        if (! $partner || ! $partner->account_id) {
            throw new InvalidArgumentException('Bill payment is not available for this provider.');
        }

        $fee = $this->walletService->billPaymentFee();
        $this->walletService->assertSufficientBalance(
            $wallet,
            round($amount + $fee, 2),
            'Insufficient wallet balance for this bill payment.'
        );

        return [$service, $product, $wallet];
    }

    /**
     * Block lookup/transfer when identifier matches the sender's own wallet or phone.
     */
    private function assertNotSelfIdentifier(Wallet $fromWallet, Customer $sender, string $identifier): void
    {
        $normalized = trim($identifier);

        if ($normalized === '') {
            return;
        }

        $isSelf = $normalized === $fromWallet->wallet_id
            || $normalized === $fromWallet->user_number
            || $normalized === $sender->phone;

        if ($isSelf) {
            throw new InvalidArgumentException('You cannot use your own wallet or phone number as the recipient.');
        }
    }

    private function resolveActiveWalletForCustomer(Customer $customer): Wallet
    {
        $wallet = $this->walletService->walletForCustomer($customer)
            ?? $this->walletService->createForCustomer($customer);

        if (! $wallet) {
            throw new InvalidArgumentException('Your wallet is not available. Please contact support.');
        }

        return $wallet->fresh();
    }

    private function assertCustomerCanUseWallet(Customer $customer): void
    {
        if (! $customer->canAccessWallet()) {
            throw new InvalidArgumentException(
                $customer->walletLoginBlockReason() ?? 'Your account is not allowed to use the wallet.'
            );
        }
    }

    private function assertRecipientCanReceive(Customer $recipient): void
    {
        if (! $recipient->isActive()) {
            $reason = match ($recipient->status) {
                Customer::STATUS_SUSPENDED => 'Recipient account is suspended and cannot receive money.',
                Customer::STATUS_INACTIVE => 'Recipient account is inactive and cannot receive money.',
                Customer::STATUS_PENDING => 'Recipient account is pending approval and cannot receive money.',
                Customer::STATUS_REJECTED => 'Recipient account is not approved and cannot receive money.',
                Customer::STATUS_REQUESTING_UPDATED => 'Recipient account has a pending profile update and cannot receive money.',
                Customer::STATUS_DELETED => 'Recipient account is not available.',
                default => 'Recipient account is not available to receive money.',
            };

            throw new InvalidArgumentException($reason);
        }
    }

    private function assertWalletIsActive(Wallet $wallet, string $message): void
    {
        if ($wallet->status !== 'active') {
            throw new InvalidArgumentException($message);
        }
    }

    private function formatWallet(Wallet $wallet): array
    {
        return [
            'wallet_id' => $wallet->wallet_id,
            'user_number' => $wallet->user_number,
            'currency_code' => $wallet->currency_code,
            'balance' => (float) $wallet->balance,
            'available_balance' => (float) $wallet->available_balance,
            'status' => $wallet->status,
        ];
    }

    private function formatTransaction(WalletTransaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'type' => $transaction->type,
            'direction' => $transaction->direction,
            'amount' => (float) $transaction->amount,
            'balance_after' => (float) $transaction->balance_after,
            'description' => $transaction->description,
            'note' => $transaction->note,
            'created_at' => $transaction->created_at?->toIso8601String(),
        ];
    }
}
