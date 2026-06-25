<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class WalletService
{
    public const MASTER_WALLET_ID = 'WAL-MASTER';

    public const MASTER_USER_NUMBER = 'MASTER';

    public const BANK_ACCOUNT_CODE = 1000;

    public const WALLET_LIABILITY_ACCOUNT_CODE = 2000;

    public const DEFAULT_CURRENCY_CODE = 'SDG';

    public function __construct(private readonly LedgerService $ledgerService)
    {
    }

    public function createForCustomer(Customer $customer): ?Wallet
    {
        $liabilityAccount = $this->resolveWalletLiabilityAccount();
        if (! $liabilityAccount) {
            return null;
        }

        return Wallet::query()->firstOrCreate(
            ['customer_id' => $customer->id],
            [
                'wallet_id' => $this->generateWalletPublicId($customer),
                'user_number' => (string) ($customer->getCode() ?: $customer->id),
                'merchant_id' => $customer->merchant_id,
                'currency_id' => $this->resolveDefaultCurrencyId(),
                'currency_code' => self::DEFAULT_CURRENCY_CODE,
                'account_id' => $liabilityAccount->id,
                'balance' => '0.00',
                'available_balance' => '0.00',
                'status' => 'active',
            ]
        );
    }

    public function createMasterWallet(): Wallet
    {
        $liabilityAccount = $this->resolveWalletLiabilityAccount();
        if (! $liabilityAccount) {
            throw new RuntimeException('Customer Wallet Liability account (2000) is not configured.');
        }

        return Wallet::query()->firstOrCreate(
            ['wallet_id' => self::MASTER_WALLET_ID],
            [
                'user_number' => self::MASTER_USER_NUMBER,
                'customer_id' => null,
                'merchant_id' => null,
                'currency_id' => $this->resolveDefaultCurrencyId(),
                'currency_code' => self::DEFAULT_CURRENCY_CODE,
                'account_id' => $liabilityAccount->id,
                'balance' => '0.00',
                'available_balance' => '0.00',
                'status' => 'active',
            ]
        );
    }

    public function topUpFromBank(Wallet $wallet, float $amount, ?string $description = null, int $createdBy = 0): void
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Top-up amount must be greater than zero.');
        }

        DB::transaction(function () use ($wallet, $amount, $description, $createdBy) {
            $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $this->ledgerService->post(
                [
                    ['account_code' => self::BANK_ACCOUNT_CODE, 'debit' => $amount, 'sub_id' => 0],
                    ['account_code' => self::WALLET_LIABILITY_ACCOUNT_CODE, 'credit' => $amount, 'sub_id' => 1],
                ],
                LedgerService::REF_WALLET_TOPUP,
                $this->walletReferenceId($wallet),
                createdBy: $createdBy
            );

            $newBalance = round((float) $wallet->balance + $amount, 2);

            $wallet->update([
                'balance' => $newBalance,
                'available_balance' => $newBalance,
            ]);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'topup',
                'direction' => 'credit',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference' => LedgerService::REF_WALLET_TOPUP,
                'reference_id' => $this->walletReferenceId($wallet),
                'description' => $description ?? 'Wallet top-up from bank',
                'created_by' => $createdBy,
            ]);
        });
    }

    public function transfer(Wallet $from, Wallet $to, float $amount, ?string $description = null, int $createdBy = 0): void
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Transfer amount must be greater than zero.');
        }

        if ($from->id === $to->id) {
            throw new InvalidArgumentException('Cannot transfer to the same wallet.');
        }

        DB::transaction(function () use ($from, $to, $amount, $description, $createdBy) {
            $fromWallet = Wallet::query()->whereKey($from->id)->lockForUpdate()->firstOrFail();
            $toWallet = Wallet::query()->whereKey($to->id)->lockForUpdate()->firstOrFail();

            if ((float) $fromWallet->available_balance < $amount) {
                throw new InvalidArgumentException('Insufficient wallet balance for transfer.');
            }

            $this->ledgerService->post(
                [
                    ['account_code' => self::WALLET_LIABILITY_ACCOUNT_CODE, 'debit' => $amount, 'sub_id' => 0],
                    ['account_code' => self::WALLET_LIABILITY_ACCOUNT_CODE, 'credit' => $amount, 'sub_id' => 1],
                ],
                LedgerService::REF_WALLET_TRANSFER,
                $this->walletReferenceId($fromWallet),
                createdBy: $createdBy
            );

            $fromBalance = round((float) $fromWallet->balance - $amount, 2);
            $toBalance = round((float) $toWallet->balance + $amount, 2);

            $fromWallet->update([
                'balance' => $fromBalance,
                'available_balance' => $fromBalance,
            ]);

            $toWallet->update([
                'balance' => $toBalance,
                'available_balance' => $toBalance,
            ]);

            WalletTransaction::create([
                'wallet_id' => $fromWallet->id,
                'type' => 'transfer',
                'direction' => 'debit',
                'amount' => $amount,
                'balance_after' => $fromBalance,
                'reference' => LedgerService::REF_WALLET_TRANSFER,
                'reference_id' => $this->walletReferenceId($fromWallet),
                'description' => $description ?? 'Wallet transfer out',
                'created_by' => $createdBy,
            ]);

            WalletTransaction::create([
                'wallet_id' => $toWallet->id,
                'type' => 'transfer',
                'direction' => 'credit',
                'amount' => $amount,
                'balance_after' => $toBalance,
                'reference' => LedgerService::REF_WALLET_TRANSFER,
                'reference_id' => $this->walletReferenceId($fromWallet),
                'description' => $description ?? 'Wallet transfer in',
                'created_by' => $createdBy,
            ]);
        });
    }

    public function walletForCustomer(Customer $customer): ?Wallet
    {
        return Wallet::query()->where('customer_id', $customer->id)->first();
    }

    private function walletReferenceId(Wallet $wallet): int
    {
        return (int) ($wallet->customer_id ?? 0);
    }

    private function generateWalletPublicId(Customer $customer): string
    {
        return 'WAL-'.str_pad((string) $customer->id, 8, '0', STR_PAD_LEFT);
    }

    private function resolveWalletLiabilityAccount(): ?ChartOfAccount
    {
        return ChartOfAccount::query()->byCode(self::WALLET_LIABILITY_ACCOUNT_CODE)->first();
    }

    private function resolveDefaultCurrencyId(): ?string
    {
        return Currency::query()
            ->where('currency_code->en', self::DEFAULT_CURRENCY_CODE)
            ->value('id');
    }
}
