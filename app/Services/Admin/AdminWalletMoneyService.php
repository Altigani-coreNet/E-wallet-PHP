<?php

namespace App\Services\Admin;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Modules\CustomerAuth\Notifications\CustomerNotificationType;
use App\Modules\CustomerAuth\Services\CustomerSystemNotificationService;
use App\Services\AccountingService;
use App\Services\IdempotencyService;
use App\Services\LedgerService;
use App\Services\WalletService;
use InvalidArgumentException;

class AdminWalletMoneyService
{
    public const SCOPE_CASH_IN = 'admin.wallet.cash_in';

    public const SCOPE_CASH_OUT = 'admin.wallet.cash_out';

    public function __construct(
        private readonly WalletService $walletService,
        private readonly AccountingService $accountingService,
        private readonly IdempotencyService $idempotencyService,
        private readonly CustomerSystemNotificationService $customerSystemNotificationService,
    ) {
    }

    /**
     * @return array{amount: float, description: ?string, wallet: Wallet, transaction: WalletTransaction|null, posting_reference: string}
     */
    public function cashIn(
        string $walletUuid,
        float $amount,
        ?string $description = null,
        ?string $idempotencyKey = null,
        int $createdBy = 0
    ): array {
        return $this->idempotencyService->execute(
            $this->idempotencyOwnerId($walletUuid),
            self::SCOPE_CASH_IN.':'.$walletUuid,
            $idempotencyKey,
            function () use ($walletUuid, $amount, $description, $createdBy) {
                $wallet = $this->resolveActiveWallet($walletUuid);

                $this->walletService->cashIn($wallet, $amount, $description, $createdBy);

                $wallet->refresh()->load(['customer.merchant']);

                $transaction = WalletTransaction::query()
                    ->where('wallet_id', $wallet->id)
                    ->where('type', 'topup')
                    ->latest('id')
                    ->first();

                if ($wallet->customer) {
                    $this->customerSystemNotificationService->send(
                        $wallet->customer,
                        CustomerNotificationType::CashIn,
                        [
                            'amount' => round($amount, 2),
                            'currency' => $wallet->currency_code,
                        ],
                    );
                }

                return [
                    'amount' => round($amount, 2),
                    'description' => $description,
                    'wallet' => $wallet,
                    'transaction' => $transaction,
                    'posting_reference' => LedgerService::REF_WALLET_CASH_IN,
                ];
            }
        );
    }

    /**
     * @return array{amount: float, description: ?string, wallet: Wallet, transaction: WalletTransaction|null, posting_reference: string}
     */
    public function cashOut(
        string $walletUuid,
        float $amount,
        ?string $description = null,
        ?string $idempotencyKey = null,
        int $createdBy = 0
    ): array {
        return $this->idempotencyService->execute(
            $this->idempotencyOwnerId($walletUuid),
            self::SCOPE_CASH_OUT.':'.$walletUuid,
            $idempotencyKey,
            function () use ($walletUuid, $amount, $description, $createdBy) {
                $wallet = $this->resolveActiveWallet($walletUuid);

                $this->walletService->cashOut($wallet, $amount, $description, $createdBy);

                $wallet->refresh()->load(['customer.merchant']);

                $transaction = WalletTransaction::query()
                    ->where('wallet_id', $wallet->id)
                    ->where('type', 'adjustment')
                    ->where('direction', 'debit')
                    ->latest('id')
                    ->first();

                return [
                    'amount' => round($amount, 2),
                    'description' => $description,
                    'wallet' => $wallet,
                    'transaction' => $transaction,
                    'posting_reference' => LedgerService::REF_WALLET_CASH_OUT,
                ];
            }
        );
    }

    /**
     * @return array{amount: float, description: ?string, posting_reference: string}
     */
    public function recordOpeningCapital(
        float $amount,
        ?string $description = null,
        ?string $idempotencyKey = null,
        int $createdBy = 0
    ): array {
        return $this->accountingService->recordOpeningCapital(
            $amount,
            $description,
            $idempotencyKey,
            $createdBy
        );
    }

    private function resolveActiveWallet(string $walletUuid): Wallet
    {
        $wallet = Wallet::query()->find($walletUuid);

        if (! $wallet) {
            throw new InvalidArgumentException('Wallet was not found.');
        }

        if ($wallet->status !== 'active') {
            throw new InvalidArgumentException('Wallet is not active.');
        }

        return $wallet;
    }

    private function idempotencyOwnerId(string $walletUuid): ?string
    {
        $wallet = Wallet::query()->find($walletUuid);

        return $wallet?->customer_id ? (string) $wallet->customer_id : null;
    }
}
