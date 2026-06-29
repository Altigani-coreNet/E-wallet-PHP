<?php

namespace Tests\Support;

use App\Models\Customer;
use App\Models\Wallet;
use App\Services\WalletService;

trait AdminWalletTestHelpers
{
    protected function createFundedWallet(Customer $customer, float $balance = 500.00): Wallet
    {
        $walletService = app(WalletService::class);
        $master = $walletService->createMasterWallet();
        $customer->load('wallet');

        $wallet = $walletService->walletForCustomer($customer)
            ?? $walletService->createForCustomer($customer);

        if ($balance > 0) {
            // Master float must cover the issue to the customer.
            $walletService->cashIn($master, $balance, 'Master test funding');
            $walletService->cashIn($wallet, $balance, 'Test funding');
            $wallet->refresh();
        }

        return $wallet;
    }

    /**
     * @return array{0: Wallet, 1: Wallet}
     */
    protected function createTransferPair(float $senderBalance = 1000.00, float $recipientBalance = 0.00): array
    {
        $walletService = app(WalletService::class);

        $sender = Customer::factory()->active()->create();
        $recipient = Customer::factory()->active()->create();

        $senderWallet = $this->createFundedWallet($sender, $senderBalance);
        $recipientWallet = $this->createFundedWallet($recipient, $recipientBalance);

        return [$senderWallet, $recipientWallet];
    }

    protected function transferBetweenWallets(Wallet $from, Wallet $to, float $amount, ?string $description = null): void
    {
        app(WalletService::class)->transfer($from, $to, $amount, $description, 0);
    }

    protected function createMasterWallet(float $balance = 0): Wallet
    {
        $walletService = app(WalletService::class);
        $master = $walletService->createMasterWallet();

        if ($balance > 0) {
            $walletService->topUpFromBank($master, $balance, 'Master test funding');
            $master->refresh();
        }

        return $master;
    }
}
