<?php

namespace App\Modules\CustomerAuth\Services;

use App\Models\Customer;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use InvalidArgumentException;

class CustomerWalletService
{
    public function __construct(private readonly WalletService $walletService)
    {
    }

    public function dashboard(Customer $customer): array
    {
        $this->assertCustomerCanUseWallet($customer);

        $wallet = $this->resolveActiveWalletForCustomer($customer);

        $lastTransaction = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->latest()
            ->first();

        return [
            'wallet' => $this->formatWallet($wallet),
            'last_transaction' => $lastTransaction ? $this->formatTransaction($lastTransaction) : null,
        ];
    }

    public function transferByWalletId(Customer $sender, string $recipientWalletId, float $amount, ?string $description = null): array
    {
        $this->assertCustomerCanUseWallet($sender);

        $fromWallet = $this->resolveActiveWalletForCustomer($sender);

        $toWallet = Wallet::query()
            ->with('customer')
            ->where('wallet_id', $recipientWalletId)
            ->first();

        if (! $toWallet) {
            throw new InvalidArgumentException('Recipient wallet was not found.');
        }

        return $this->executeTransfer($sender, $fromWallet, $toWallet, $amount, $description);
    }

    public function transferByPhone(Customer $sender, string $recipientPhone, float $amount, ?string $description = null): array
    {
        $this->assertCustomerCanUseWallet($sender);

        $fromWallet = $this->resolveActiveWalletForCustomer($sender);

        $recipient = Customer::query()
            ->where('phone', $recipientPhone)
            ->first();

        if (! $recipient) {
            throw new InvalidArgumentException('No customer was found with this phone number.');
        }

        $toWallet = $this->walletService->walletForCustomer($recipient);

        if (! $toWallet) {
            throw new InvalidArgumentException('Recipient does not have a wallet.');
        }

        $toWallet->load('customer');

        return $this->executeTransfer($sender, $fromWallet, $toWallet, $amount, $description);
    }

    private function executeTransfer(
        Customer $sender,
        Wallet $fromWallet,
        Wallet $toWallet,
        float $amount,
        ?string $description
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

        if ((float) $fromWallet->available_balance < round($amount, 2)) {
            throw new InvalidArgumentException('Insufficient wallet balance for this transfer.');
        }

        $this->walletService->transfer(
            $fromWallet,
            $toWallet,
            $amount,
            $description,
            $sender->id
        );

        $fromWallet->refresh();
        $toWallet->refresh();

        $debitTransaction = WalletTransaction::query()
            ->where('wallet_id', $fromWallet->id)
            ->where('type', 'transfer')
            ->where('direction', 'debit')
            ->latest()
            ->first();

        return [
            'amount' => round($amount, 2),
            'description' => $description,
            'sender_wallet' => $this->formatWallet($fromWallet),
            'recipient' => [
                'name' => $recipient->name,
                'wallet_id' => $toWallet->wallet_id,
            ],
            'transaction' => $debitTransaction ? $this->formatTransaction($debitTransaction) : null,
        ];
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
            'created_at' => $transaction->created_at?->toIso8601String(),
        ];
    }
}
