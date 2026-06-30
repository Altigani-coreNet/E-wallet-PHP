<?php

namespace App\Services;

use App\Exceptions\RecipientWalletException;
use App\Models\ChartOfAccount;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\AccountCode;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class WalletService
{
    public const MASTER_WALLET_ID = 'WAL-MASTER';

    public const WALLET_ID_SUFFIX = '@fastpay';

    public const MASTER_USER_NUMBER = 'MASTER';

    /** @deprecated Use AccountCode::BANK */
    public const BANK_ACCOUNT_CODE = AccountCode::BANK;

    /** @deprecated Use AccountCode::CUSTOMER_LIABILITY */
    public const WALLET_LIABILITY_ACCOUNT_CODE = AccountCode::CUSTOMER_LIABILITY;

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

        $publicId = $this->generateWalletPublicId($customer);

        $existingByCustomer = Wallet::query()->where('customer_id', $customer->id)->first();
        if ($existingByCustomer) {
            return $existingByCustomer;
        }

        $existingByPhone = Wallet::query()->where('wallet_id', $publicId)->first();
        if ($existingByPhone) {
            throw new InvalidArgumentException('A wallet already exists for this phone number.');
        }

        return Wallet::query()->create([
            'customer_id' => $customer->id,
            'wallet_id' => $publicId,
            'user_number' => (string) ($customer->getCode() ?: $customer->id),
            'merchant_id' => $customer->merchant_id,
            'currency_id' => $this->resolveDefaultCurrencyId(),
            'currency_code' => self::DEFAULT_CURRENCY_CODE,
            'account_id' => $liabilityAccount->id,
            'balance' => '0.00',
            'available_balance' => '0.00',
            'status' => 'active',
        ]);
    }

    public static function formatWalletPublicId(string $phone): string
    {
        $phone = ltrim(trim($phone), '+');

        if ($phone === '') {
            throw new InvalidArgumentException('Phone number is required for wallet ID.');
        }

        return $phone.self::WALLET_ID_SUFFIX;
    }

    public function createMasterWallet(): Wallet
    {
        $liabilityAccount = $this->resolveMasterLiabilityAccount();
        if (! $liabilityAccount) {
            throw new RuntimeException('Master Wallet Liability account (2050) is not configured.');
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

    /**
     * Legacy direct bank top-up (tests / back-compat). Prefer cashIn() for production flows.
     */
    public function topUpFromBank(Wallet $wallet, float $amount, ?string $description = null, int $createdBy = 0): void
    {
        if ($wallet->isMaster()) {
            $this->fundMaster($wallet, $this->normalizeAmount($amount), $description ?? 'Master float funded from bank', $createdBy);

            return;
        }

        $this->postDirectBankToLiability($wallet, $this->normalizeAmount($amount), LedgerService::REF_WALLET_TOPUP, 'topup', $description ?? 'Wallet top-up from bank', $createdBy);
    }

    /**
     * Cash-in. For the master wallet this funds the float from the bank
     * (DR Bank / CR Master). For a customer wallet the master issues e-money
     * to the customer (DR Master / CR Customer); the master must hold enough float.
     */
    public function cashIn(Wallet $userWallet, float $amount, ?string $description = null, int $createdBy = 0): void
    {
        $amount = $this->normalizeAmount($amount);

        $this->assertWalletIsActive($userWallet, 'Wallet is not available for cash-in.');

        if ($userWallet->isMaster()) {
            $this->fundMaster($userWallet, $amount, $description, $createdBy);

            return;
        }

        DB::transaction(function () use ($userWallet, $amount, $description, $createdBy) {
            [$master, $userWallet] = $this->lockWalletsInOrder(
                $this->createMasterWallet(),
                $userWallet
            );

            $this->assertSufficientBalance($master, $amount, 'Master float has insufficient balance for this cash-in.');

            $referenceId = $this->walletReferenceId($userWallet);

            $this->ledgerService->post(
                [
                    ['account_code' => AccountCode::MASTER_LIABILITY, 'debit' => $amount, 'sub_id' => 0],
                    ['account_code' => AccountCode::CUSTOMER_LIABILITY, 'credit' => $amount, 'sub_id' => 1],
                ],
                LedgerService::REF_WALLET_CASH_IN,
                $referenceId,
                createdBy: $createdBy
            );

            $masterBalance = round((float) $master->balance - $amount, 2);
            $userBalance = round((float) $userWallet->balance + $amount, 2);

            $master->update([
                'balance' => $masterBalance,
                'available_balance' => $masterBalance,
            ]);

            $userWallet->update([
                'balance' => $userBalance,
                'available_balance' => $userBalance,
            ]);

            WalletTransaction::create([
                'wallet_id' => $master->id,
                'type' => 'adjustment',
                'direction' => 'debit',
                'amount' => $amount,
                'balance_after' => $masterBalance,
                'reference' => LedgerService::REF_WALLET_CASH_IN,
                'reference_id' => $referenceId,
                'description' => $description ?? 'Master issue to customer (cash-in)',
                'created_by' => $createdBy,
            ]);

            WalletTransaction::create([
                'wallet_id' => $userWallet->id,
                'type' => 'topup',
                'direction' => 'credit',
                'amount' => $amount,
                'balance_after' => $userBalance,
                'reference' => LedgerService::REF_WALLET_CASH_IN,
                'reference_id' => $referenceId,
                'description' => $description ?? 'Wallet cash-in',
                'created_by' => $createdBy,
            ]);
        });
    }

    /**
     * Cash-out. For the master wallet this withdraws float to the bank
     * (DR Master / CR Bank). For a customer wallet the customer redeems
     * e-money back to the master (DR Customer / CR Master).
     */
    public function cashOut(Wallet $userWallet, float $amount, ?string $description = null, int $createdBy = 0): void
    {
        $amount = $this->normalizeAmount($amount);

        $this->assertWalletIsActive($userWallet, 'Wallet is not available for cash-out.');

        if ($userWallet->isMaster()) {
            $this->defundMaster($userWallet, $amount, $description, $createdBy);

            return;
        }

        DB::transaction(function () use ($userWallet, $amount, $description, $createdBy) {
            [$master, $userWallet] = $this->lockWalletsInOrder(
                $this->createMasterWallet(),
                $userWallet
            );

            $this->assertSufficientBalance($userWallet, $amount);

            $referenceId = $this->walletReferenceId($userWallet);

            $this->ledgerService->post(
                [
                    ['account_code' => AccountCode::CUSTOMER_LIABILITY, 'debit' => $amount, 'sub_id' => 0],
                    ['account_code' => AccountCode::MASTER_LIABILITY, 'credit' => $amount, 'sub_id' => 1],
                ],
                LedgerService::REF_WALLET_CASH_OUT,
                $referenceId,
                createdBy: $createdBy
            );

            $masterBalance = round((float) $master->balance + $amount, 2);
            $userBalance = round((float) $userWallet->balance - $amount, 2);

            $master->update([
                'balance' => $masterBalance,
                'available_balance' => $masterBalance,
            ]);

            $userWallet->update([
                'balance' => $userBalance,
                'available_balance' => $userBalance,
            ]);

            WalletTransaction::create([
                'wallet_id' => $userWallet->id,
                'type' => 'adjustment',
                'direction' => 'debit',
                'amount' => $amount,
                'balance_after' => $userBalance,
                'reference' => LedgerService::REF_WALLET_CASH_OUT,
                'reference_id' => $referenceId,
                'description' => $description ?? 'Wallet cash-out',
                'created_by' => $createdBy,
            ]);

            WalletTransaction::create([
                'wallet_id' => $master->id,
                'type' => 'adjustment',
                'direction' => 'credit',
                'amount' => $amount,
                'balance_after' => $masterBalance,
                'reference' => LedgerService::REF_WALLET_CASH_OUT,
                'reference_id' => $referenceId,
                'description' => $description ?? 'Customer redeem to master (cash-out)',
                'created_by' => $createdBy,
            ]);
        });
    }

    /**
     * Operator funds the master float with real cash: DR Bank / CR Master.
     */
    private function fundMaster(Wallet $master, float $amount, ?string $description, int $createdBy): void
    {
        DB::transaction(function () use ($master, $amount, $description, $createdBy) {
            $master = Wallet::query()->whereKey($master->id)->lockForUpdate()->firstOrFail();

            $referenceId = $this->walletReferenceId($master);

            $this->ledgerService->post(
                [
                    ['account_code' => AccountCode::BANK, 'debit' => $amount, 'sub_id' => 0],
                    ['account_code' => AccountCode::MASTER_LIABILITY, 'credit' => $amount, 'sub_id' => 1],
                ],
                LedgerService::REF_WALLET_CASH_IN,
                $referenceId,
                createdBy: $createdBy
            );

            $newBalance = round((float) $master->balance + $amount, 2);

            $master->update([
                'balance' => $newBalance,
                'available_balance' => $newBalance,
            ]);

            WalletTransaction::create([
                'wallet_id' => $master->id,
                'type' => 'topup',
                'direction' => 'credit',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference' => LedgerService::REF_WALLET_CASH_IN,
                'reference_id' => $referenceId,
                'description' => $description ?? 'Master float funded from bank',
                'created_by' => $createdBy,
            ]);
        });
    }

    /**
     * Operator withdraws cash from the master float: DR Master / CR Bank.
     */
    private function defundMaster(Wallet $master, float $amount, ?string $description, int $createdBy): void
    {
        DB::transaction(function () use ($master, $amount, $description, $createdBy) {
            $master = Wallet::query()->whereKey($master->id)->lockForUpdate()->firstOrFail();

            $this->assertSufficientBalance($master, $amount, 'Master float has insufficient balance for this withdrawal.');

            $referenceId = $this->walletReferenceId($master);

            $this->ledgerService->post(
                [
                    ['account_code' => AccountCode::MASTER_LIABILITY, 'debit' => $amount, 'sub_id' => 0],
                    ['account_code' => AccountCode::BANK, 'credit' => $amount, 'sub_id' => 1],
                ],
                LedgerService::REF_WALLET_CASH_OUT,
                $referenceId,
                createdBy: $createdBy
            );

            $newBalance = round((float) $master->balance - $amount, 2);

            $master->update([
                'balance' => $newBalance,
                'available_balance' => $newBalance,
            ]);

            WalletTransaction::create([
                'wallet_id' => $master->id,
                'type' => 'adjustment',
                'direction' => 'debit',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference' => LedgerService::REF_WALLET_CASH_OUT,
                'reference_id' => $referenceId,
                'description' => $description ?? 'Master float withdrawn to bank',
                'created_by' => $createdBy,
            ]);
        });
    }

    public function transferFee(): float
    {
        return round(max(0, (float) config('services.wallet.transfer_fee', 0)), 2);
    }

    public function transfer(
        Wallet $from,
        Wallet $to,
        float $amount,
        ?string $description = null,
        int $createdBy = 0,
        ?string $note = null
    ): void {
        $amount = $this->normalizeAmount($amount);
        $fee = $this->transferFee();

        if ($fee > $amount) {
            throw new InvalidArgumentException('Fee cannot exceed transfer amount.');
        }

        if ($from->id === $to->id) {
            throw new InvalidArgumentException('Cannot transfer to the same wallet.');
        }

        if ($from->isMaster()) {
            throw new InvalidArgumentException('Cannot transfer from the master wallet.');
        }

        $recipientAmount = round($amount - $fee, 2);
        $toIsMaster = $to->isMaster();

        DB::transaction(function () use ($from, $to, $amount, $fee, $recipientAmount, $toIsMaster, $description, $createdBy, $note) {
            [$fromWallet, $toWallet] = $this->lockWalletsInOrder($from, $to);

            $this->assertSufficientBalance($fromWallet, $amount);

            $lines = [
                ['account_code' => AccountCode::CUSTOMER_LIABILITY, 'debit' => $amount, 'sub_id' => 0],
                [
                    'account_code' => $toIsMaster ? AccountCode::MASTER_LIABILITY : AccountCode::CUSTOMER_LIABILITY,
                    'credit' => $recipientAmount,
                    'sub_id' => 1,
                ],
            ];

            if ($fee > 0) {
                $lines[] = ['account_code' => AccountCode::FEE_INCOME, 'credit' => $fee, 'sub_id' => 2];
            }

            $this->ledgerService->post(
                $lines,
                LedgerService::REF_WALLET_TRANSFER,
                $this->walletReferenceId($fromWallet),
                createdBy: $createdBy
            );

            $fromBalance = round((float) $fromWallet->balance - $amount, 2);
            $toBalance = round((float) $toWallet->balance + $recipientAmount, 2);

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
                'note' => $note,
                'created_by' => $createdBy,
            ]);

            WalletTransaction::create([
                'wallet_id' => $toWallet->id,
                'type' => 'transfer',
                'direction' => 'credit',
                'amount' => $recipientAmount,
                'balance_after' => $toBalance,
                'reference' => LedgerService::REF_WALLET_TRANSFER,
                'reference_id' => $this->walletReferenceId($fromWallet),
                'description' => $description ?? 'Wallet transfer in',
                'note' => $note,
                'created_by' => $createdBy,
            ]);
        });
    }

    public function resolveRecipient(
        string $identifier,
        ?Wallet $excludeWallet = null,
        bool $allowPhone = true,
        string $selfWalletContext = 'transfer'
    ): Wallet {
        $identifier = trim($identifier);

        if ($identifier === '') {
            throw new InvalidArgumentException('Recipient identifier is required.');
        }

        $wallet = Wallet::query()
            ->with(['customer'])
            ->where(function ($query) use ($identifier, $allowPhone) {
                $query->where('wallet_id', $identifier)
                    ->orWhere('user_number', $identifier);

                if ($allowPhone) {
                    $query->orWhereHas('customer', function ($customerQuery) use ($identifier) {
                        $customerQuery->where('phone', $identifier);
                    });
                }
            })
            ->first();

        if (! $wallet) {
            throw new RecipientWalletException('Recipient wallet was not found.');
        }

        if (! $wallet->isMaster() && ! $wallet->customer) {
            throw new RecipientWalletException('Recipient wallet is not linked to a customer.');
        }

        if ($excludeWallet !== null && $wallet->id === $excludeWallet->id) {
            throw new InvalidArgumentException($this->selfWalletMessage($selfWalletContext));
        }

        return $wallet;
    }

    private function selfWalletMessage(string $context): string
    {
        return match ($context) {
            'query' => 'You cannot search for your own wallet.',
            default => 'You cannot transfer money to your own wallet.',
        };
    }

    public function formatRecipient(Wallet $wallet): array
    {
        $customer = $wallet->customer;

        return [
            'recipient_wallet_id' => $wallet->wallet_id,
            'wallet_uuid' => $wallet->id,
            'owner_name' => $wallet->isMaster() ? 'Master Wallet' : $customer?->name,
            'wallet_id' => $wallet->wallet_id,
            'user_number' => $wallet->user_number,
            'status' => $wallet->status,
            'currency' => $wallet->currency_code,
            'currency_code' => $wallet->currency_code,
        ];
    }

    public function assertSufficientBalance(Wallet $wallet, float $needed, string $message = 'Insufficient wallet balance for this operation.'): void
    {
        $needed = round($needed, 2);

        if ((float) $wallet->available_balance < $needed) {
            throw new InvalidArgumentException($message);
        }
    }

    public function walletForCustomer(Customer $customer): ?Wallet
    {
        return Wallet::query()->where('customer_id', $customer->id)->first();
    }

    private function postDirectBankToLiability(
        Wallet $wallet,
        float $amount,
        string $reference,
        string $type,
        string $description,
        int $createdBy
    ): void {
        DB::transaction(function () use ($wallet, $amount, $reference, $type, $description, $createdBy) {
            $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $this->ledgerService->post(
                [
                    ['account_code' => AccountCode::BANK, 'debit' => $amount, 'sub_id' => 0],
                    ['account_code' => AccountCode::CUSTOMER_LIABILITY, 'credit' => $amount, 'sub_id' => 1],
                ],
                $reference,
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
                'type' => $type,
                'direction' => 'credit',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference' => $reference,
                'reference_id' => $this->walletReferenceId($wallet),
                'description' => $description,
                'created_by' => $createdBy,
            ]);
        });
    }

    /**
     * @return array{0: Wallet, 1: Wallet}
     */
    private function lockWalletsInOrder(Wallet $first, Wallet $second): array
    {
        $orderedIds = collect([$first->id, $second->id])->sort()->values();

        $locked = [];
        foreach ($orderedIds as $id) {
            $locked[$id] = Wallet::query()->whereKey($id)->lockForUpdate()->firstOrFail();
        }

        return [$locked[$first->id], $locked[$second->id]];
    }

    private function assertWalletIsActive(Wallet $wallet, string $message): void
    {
        if ($wallet->status !== 'active') {
            throw new InvalidArgumentException($message);
        }
    }

    private function normalizeAmount(float $amount): float
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        return $amount;
    }

    private function walletReferenceId(Wallet $wallet): string
    {
        if ($wallet->customer_id === null) {
            return '0';
        }

        return (string) $wallet->customer_id;
    }

    private function generateWalletPublicId(Customer $customer): string
    {
        return self::formatWalletPublicId((string) ($customer->phone ?? ''));
    }

    private function resolveWalletLiabilityAccount(): ?ChartOfAccount
    {
        return ChartOfAccount::query()->byCode(AccountCode::CUSTOMER_LIABILITY)->first();
    }

    private function resolveMasterLiabilityAccount(): ?ChartOfAccount
    {
        return ChartOfAccount::query()->byCode(AccountCode::MASTER_LIABILITY)->first();
    }

    private function resolveDefaultCurrencyId(): ?string
    {
        return Currency::query()
            ->where('currency_code->en', self::DEFAULT_CURRENCY_CODE)
            ->value('id');
    }
}
