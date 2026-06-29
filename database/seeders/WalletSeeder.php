<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Wallet;
use App\Services\CustomerService;
use App\Services\WalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo wallet flow using production services only (IFRS double-entry).
 *
 * 1. Operator funds the master float from the bank (Dr Bank / Cr Master)
 * 2. Jon cash-in 1,950 (master issues e-money: Dr Master / Cr Jon)
 * 3. Jon -> Ahmed 450 (customer-to-customer transfer)
 */
class WalletSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'WalletDemo1!';

    public function run(): void
    {
        $walletService = app(WalletService::class);
        $customerService = app(CustomerService::class);

        $master = $walletService->createMasterWallet();
        $walletService->cashIn($master, 1_000_000, 'Master float opening fund');

        $jon = $customerService->create([
            'name' => 'Jon',
            'email' => 'jon.wallet.demo@example.com',
            'phone' => '+249900000001',
            'password' => Hash::make(self::DEMO_PASSWORD),
            'profile_completed' => true,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $ahmed = $customerService->create([
            'name' => 'Ahmed',
            'email' => 'ahmed.wallet.demo@example.com',
            'phone' => '+249900000002',
            'password' => Hash::make(self::DEMO_PASSWORD),
            'profile_completed' => true,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $jonWallet = $walletService->walletForCustomer($jon);
        $ahmedWallet = $walletService->walletForCustomer($ahmed);

        if (! $jonWallet || ! $ahmedWallet) {
            $this->command->error('Demo customer wallets were not created. Ensure ChartOfAccountSeeder has run.');

            return;
        }

        $walletService->cashIn($jonWallet, 1_950, 'Station cash-in for Jon');
        $walletService->transfer($jonWallet->fresh(), $ahmedWallet, 450, 'Jon -> Ahmed');

        $masterBalance = Wallet::query()->where('wallet_id', WalletService::MASTER_WALLET_ID)->value('balance');
        $jonBalance = Wallet::query()->where('customer_id', $jon->id)->value('balance');
        $ahmedBalance = Wallet::query()->where('customer_id', $ahmed->id)->value('balance');

        $this->command->info('Wallet demo seeded successfully.');
        $this->command->info('Demo customers can log in with password: '.self::DEMO_PASSWORD);
        $this->command->info("Master wallet balance: {$masterBalance}");
        $this->command->info("Jon wallet balance: {$jonBalance}");
        $this->command->info("Ahmed wallet balance: {$ahmedBalance}");
    }
}
