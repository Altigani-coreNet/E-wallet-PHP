<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Services\WalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Deterministic customers + funded wallets for Cypress / manual wallet API E2E.
 *
 * Sender:  +249977700001 / WalletE2e1!  (balance 1000 SDG)
 * Recipient: +249977700002 / WalletE2e1! (balance 0)
 */
class WalletE2eSeeder extends Seeder
{
    public const SENDER_PHONE = '+249977700001';

    public const RECIPIENT_PHONE = '+249977700002';

    public const E2E_PASSWORD = 'WalletE2e1!';

    public function run(): void
    {
        $this->call(ChartOfAccountSeeder::class);

        $walletService = app(WalletService::class);

        $sender = Customer::query()->updateOrCreate(
            ['phone' => self::SENDER_PHONE],
            [
                'name' => 'Wallet E2E Sender',
                'email' => 'wallet.e2e.sender@example.com',
                'password' => Hash::make(self::E2E_PASSWORD),
                'status' => Customer::STATUS_ACTIVE,
                'profile_completed' => true,
            ]
        );

        $recipient = Customer::query()->updateOrCreate(
            ['phone' => self::RECIPIENT_PHONE],
            [
                'name' => 'Wallet E2E Recipient',
                'email' => 'wallet.e2e.recipient@example.com',
                'password' => Hash::make(self::E2E_PASSWORD),
                'status' => Customer::STATUS_ACTIVE,
                'profile_completed' => true,
            ]
        );

        $senderWallet = $walletService->walletForCustomer($sender)
            ?? $walletService->createForCustomer($sender);

        $recipientWallet = $walletService->walletForCustomer($recipient)
            ?? $walletService->createForCustomer($recipient);

        if ($senderWallet && (float) $senderWallet->balance < 1000) {
            $topUpAmount = 1000 - (float) $senderWallet->balance;
            if ($topUpAmount > 0) {
                $walletService->topUpFromBank($senderWallet, $topUpAmount, 'E2E sender funding');
            }
        }

        $this->command?->info('Wallet E2E fixtures ready.');
        $this->command?->info('Sender phone: '.self::SENDER_PHONE.' wallet: '.$senderWallet?->wallet_id);
        $this->command?->info('Recipient phone: '.self::RECIPIENT_PHONE.' wallet: '.$recipientWallet?->wallet_id);
    }
}
