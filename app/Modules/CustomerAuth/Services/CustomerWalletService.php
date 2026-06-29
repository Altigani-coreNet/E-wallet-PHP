<?php

namespace App\Modules\CustomerAuth\Services;

use App\Models\Customer;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\IdempotencyService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CustomerWalletService
{
    public const SCOPE_TRANSFER = 'wallet.transfer';

    public const SCOPE_WITHDRAW = 'wallet.withdraw';

    private const COUNTERPARTY_PAIR_WINDOW_SECONDS = 10;

    public function __construct(
        private readonly WalletService $walletService,
        private readonly IdempotencyService $idempotencyService
    ) {
    }

    public function dashboard(Customer $customer): array
    {
        $this->assertCustomerCanUseWallet($customer);

        $wallet = $this->resolveActiveWalletForCustomer($customer);

        $recentRows = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->latest('id')
            ->limit(5)
            ->get();

        $this->attachCounterparties($recentRows);

        $recentTransactions = $recentRows
            ->map(fn (WalletTransaction $transaction) => $this->formatTransaction(
                $transaction,
                $transaction->getAttribute('counterparty'),
                $wallet->id
            ))
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
        $walletId = $wallet->id;
        $transactions = $paginator->getCollection();
        $this->attachCounterparties($transactions);

        return [
            'data' => $transactions
                ->map(fn (WalletTransaction $transaction) => $this->formatTransaction(
                    $transaction,
                    $transaction->getAttribute('counterparty'),
                    $walletId
                ))
                ->values()
                ->all(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    public function showTransaction(Customer $customer, string $transactionId): array
    {
        $this->assertCustomerCanUseWallet($customer);

        $wallet = $this->resolveActiveWalletForCustomer($customer);

        $transaction = WalletTransaction::query()
            ->with(['wallet.customer'])
            ->where('wallet_id', $wallet->id)
            ->where('id', $transactionId)
            ->first();

        if (! $transaction) {
            throw new ModelNotFoundException('Wallet transaction not found.');
        }

        $this->attachCounterparties(collect([$transaction]));
        $counterparty = $transaction->getAttribute('counterparty');

        $relatedLegs = $this->resolveRelatedTransferLegs($transaction);
        $this->attachCounterparties($relatedLegs);

        return [
            'transaction' => $this->formatTransaction($transaction, $counterparty, $wallet->id),
            'related_transactions' => $relatedLegs
                ->map(fn (WalletTransaction $leg) => $this->formatRelatedTransaction($leg, $wallet->id))
                ->values()
                ->all(),
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
        $recipient = $recipientWallet->customer;

        if (! $recipient) {
            throw new InvalidArgumentException('Recipient wallet is not linked to a customer.');
        }

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
        $recipient = $recipientWallet->customer;

        if (! $recipient) {
            throw new InvalidArgumentException('Recipient wallet is not linked to a customer.');
        }

        $this->assertRecipientCanReceive($recipient);
        $this->assertWalletIsActive($recipientWallet, 'Recipient wallet is not available to receive transfers.');

        return $this->walletService->formatRecipient($recipientWallet);
    }

    public function transfer(
        Customer $sender,
        string $recipientWalletId,
        float $amount,
        ?string $description = null,
        ?string $idempotencyKey = null,
        float $fee = 0.0,
        ?string $note = null
    ): array {
        return $this->idempotencyService->execute(
            $sender->id,
            self::SCOPE_TRANSFER,
            $idempotencyKey,
            function () use ($sender, $recipientWalletId, $amount, $description, $fee, $note) {
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

                return $this->executeTransfer($sender, $fromWallet, $toWallet, $amount, $description, $fee, $note);
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
        float $fee = 0.0,
        ?string $note = null
    ): array {
        if ($fromWallet->id === $toWallet->id) {
            throw new InvalidArgumentException('You cannot transfer money to your own wallet.');
        }

        $recipient = $toWallet->customer;
        if (! $recipient) {
            throw new InvalidArgumentException('Recipient wallet is not linked to a customer.');
        }

        $this->assertRecipientCanReceive($recipient);
        $this->assertWalletIsActive($fromWallet, 'Your wallet is not available for transfers.');
        $this->assertWalletIsActive($toWallet, 'Recipient wallet is not available to receive transfers.');

        if ($fromWallet->currency_code !== $toWallet->currency_code) {
            throw new InvalidArgumentException('Cross-currency transfers are not supported.');
        }

        $this->walletService->transfer(
            $fromWallet,
            $toWallet,
            $amount,
            $description,
            0,
            $fee,
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

    private function formatTransaction(
        WalletTransaction $transaction,
        ?array $counterparty = null,
        ?string $ownWalletId = null
    ): array {
        return [
            'id' => $transaction->id,
            'type' => $transaction->type,
            'direction' => $transaction->direction,
            'amount' => (float) $transaction->amount,
            'signed_amount' => $this->signedAmount($transaction),
            'balance_after' => (float) $transaction->balance_after,
            'reference' => $transaction->reference,
            'reference_id' => $transaction->reference_id,
            'description' => $transaction->description,
            'note' => $transaction->note,
            'counterparty' => $counterparty,
            'is_own' => $ownWalletId !== null && $transaction->wallet_id === $ownWalletId,
            'created_at' => $transaction->created_at?->toIso8601String(),
        ];
    }

    private function formatRelatedTransaction(WalletTransaction $transaction, string $ownWalletId): array
    {
        $wallet = $transaction->relationLoaded('wallet') ? $transaction->wallet : $transaction->wallet()->with('customer')->first();
        $customer = $wallet?->customer;

        return [
            'id' => $transaction->id,
            'type' => $transaction->type,
            'direction' => $transaction->direction,
            'amount' => (float) $transaction->amount,
            'signed_amount' => $this->signedAmount($transaction),
            'balance_after' => (float) $transaction->balance_after,
            'description' => $transaction->description,
            'note' => $transaction->note,
            'is_own' => $transaction->wallet_id === $ownWalletId,
            'wallet' => $wallet ? [
                'wallet_id' => $wallet->wallet_id,
                'owner_name' => $customer?->name,
                'owner_phone' => $customer?->phone,
            ] : null,
            'counterparty' => $transaction->getAttribute('counterparty'),
            'created_at' => $transaction->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  Collection<int, WalletTransaction>  $transactions
     */
    private function attachCounterparties(Collection $transactions): void
    {
        $transferTransactions = $transactions->filter(
            fn (WalletTransaction $transaction) => $transaction->type === 'transfer'
        );

        $counterpartyMap = [];

        foreach ($transferTransactions as $transaction) {
            $counterpartyTx = $this->resolveCounterpartyTransaction($transaction);

            if ($counterpartyTx) {
                $counterpartyMap[$transaction->id] = $this->formatCounterparty($counterpartyTx);
            }
        }

        $transactions->each(function (WalletTransaction $transaction) use ($counterpartyMap) {
            $transaction->setAttribute('counterparty', $counterpartyMap[$transaction->id] ?? null);
        });
    }

    /**
     * @return Collection<int, WalletTransaction>
     */
    private function resolveRelatedTransferLegs(WalletTransaction $transaction): Collection
    {
        if ($transaction->type !== 'transfer') {
            return collect([$transaction]);
        }

        $counterparty = $this->resolveCounterpartyTransaction($transaction);

        if (! $counterparty) {
            return collect([$transaction]);
        }

        if (! $transaction->relationLoaded('wallet')) {
            $transaction->load(['wallet.customer']);
        }

        return collect([$transaction, $counterparty])
            ->unique('id')
            ->sortBy(fn (WalletTransaction $leg) => $leg->direction === 'debit' ? 0 : 1)
            ->values();
    }

    private function resolveCounterpartyTransaction(WalletTransaction $transaction): ?WalletTransaction
    {
        if ($transaction->type !== 'transfer') {
            return null;
        }

        $createdAt = $transaction->created_at;
        if (! $createdAt || ! $transaction->reference || ! $transaction->reference_id) {
            return null;
        }

        $oppositeDirection = $transaction->direction === 'debit' ? 'credit' : 'debit';

        $query = WalletTransaction::query()
            ->where('id', '!=', $transaction->id)
            ->where('type', 'transfer')
            ->where('reference', $transaction->reference)
            ->where('reference_id', $transaction->reference_id)
            ->where('direction', $oppositeDirection)
            ->where('wallet_id', '!=', $transaction->wallet_id)
            ->whereBetween('created_at', [
                $createdAt->copy()->subSeconds(self::COUNTERPARTY_PAIR_WINDOW_SECONDS),
                $createdAt->copy()->addSeconds(self::COUNTERPARTY_PAIR_WINDOW_SECONDS),
            ])
            ->with(['wallet.customer']);

        if ($transaction->note !== null && $transaction->note !== '') {
            $query->where('note', $transaction->note);
        }

        return $query->orderBy('created_at')->first();
    }

    private function formatCounterparty(WalletTransaction $counterpartyTx): array
    {
        $wallet = $counterpartyTx->wallet;
        $customer = $wallet?->customer;

        return [
            'wallet_id' => $wallet?->wallet_id,
            'name' => $customer?->name,
            'phone' => $customer?->phone,
            'direction' => $counterpartyTx->direction,
        ];
    }

    private function signedAmount(WalletTransaction $transaction): float
    {
        $amount = (float) $transaction->amount;

        return $transaction->direction === 'debit' ? -abs($amount) : abs($amount);
    }
}
