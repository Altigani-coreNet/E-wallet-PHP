<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Wallet;
use App\Services\CustomerService;
use App\Services\WalletService;
use Illuminate\Database\Seeder;

/**
 * Demo wallet flow using production services only (IFRS double-entry).
 *
 * 1. Master wallet funded 1,000,000 from bank (Dr 1000 / Cr 2000)
 * 2. Master -> Jon 1,950
 * 3. Jon -> Ahmed 450
 */
class WalletSeeder extends Seeder
{
    public function run(): void
    {
        $walletService = app(WalletService::class);
        $customerService = app(CustomerService::class);

        $master = $walletService->createMasterWallet();
        $walletService->topUpFromBank($master, 1_000_000, 'Master wallet initial funding');

        $jon = $customerService->create([
            'name' => 'Jon',
            'email' => 'jon.wallet.demo@example.com',
            'phone' => '+249900000001',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $ahmed = $customerService->create([
            'name' => 'Ahmed',
            'email' => 'ahmed.wallet.demo@example.com',
            'phone' => '+249900000002',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $jonWallet = $walletService->walletForCustomer($jon);
        $ahmedWallet = $walletService->walletForCustomer($ahmed);

        if (! $jonWallet || ! $ahmedWallet) {
            $this->command->error('Demo customer wallets were not created. Ensure ChartOfAccountSeeder has run.');

            return;
        }

        $walletService->transfer($master->fresh(), $jonWallet, 1_950, 'Master -> Jon');
        $walletService->transfer($jonWallet->fresh(), $ahmedWallet, 450, 'Jon -> Ahmed');

        $masterBalance = Wallet::query()->where('wallet_id', WalletService::MASTER_WALLET_ID)->value('balance');
        $jonBalance = Wallet::query()->where('customer_id', $jon->id)->value('balance');
        $ahmedBalance = Wallet::query()->where('customer_id', $ahmed->id)->value('balance');

        $this->command->info('Wallet demo seeded successfully.');
        $this->command->info("Master wallet balance: {$masterBalance}");
        $this->command->info("Jon wallet balance: {$jonBalance}");
        $this->command->info("Ahmed wallet balance: {$ahmedBalance}");
    }
}
