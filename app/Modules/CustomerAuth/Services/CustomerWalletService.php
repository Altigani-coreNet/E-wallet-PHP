<?php

namespace App\Modules\CustomerAuth\Services;

use App\Models\Customer;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\IdempotencyService;
use App\Services\WalletService;
use InvalidArgumentException;

class CustomerWalletService
{
    public const SCOPE_TRANSFER = 'wallet.transfer';

    public const SCOPE_WITHDRAW = 'wallet.withdraw';

    public function __construct(
        private readonly WalletService $walletService,
        private readonly IdempotencyService $idempotencyService
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

                $this->walletService->cashOut($wallet, $amount, $description, $customer->id);

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
            $sender->id,
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
