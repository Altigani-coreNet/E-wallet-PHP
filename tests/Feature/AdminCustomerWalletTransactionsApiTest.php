<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Wallet;
use Database\Seeders\ChartOfAccountSeeder;
use Laravel\Passport\Passport;
use Tests\CustomerAuthTestCase;
use Tests\Support\AdminWalletTestHelpers;

class AdminCustomerWalletTransactionsApiTest extends CustomerAuthTestCase
{
    use AdminWalletTestHelpers;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountSeeder::class);
        $this->admin = Admin::factory()->active()->create();
    }

    public function test_customer_wallet_routes_require_authentication(): void
    {
        $customer = Customer::factory()->active()->create();

        $this->getJson("/api/v2/admin/customers/{$customer->id}/wallet")->assertUnauthorized();
        $this->getJson("/api/v2/admin/customers/{$customer->id}/transactions")->assertUnauthorized();
    }

    public function test_wallet_endpoint_returns_summary_and_recent_transactions(): void
    {
        [$senderWallet, $recipientWallet] = $this->createTransferPair(1000, 0);

        foreach ([10, 20, 30, 40, 50, 60] as $amount) {
            $this->transferBetweenWallets($senderWallet, $recipientWallet, (float) $amount, "Transfer {$amount}");
        }

        $sender = $senderWallet->customer;

        $response = $this->actingAsAdminApi()->getJson("/api/v2/admin/customers/{$sender->id}/wallet");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.wallet.id', $senderWallet->id)
            ->assertJsonPath('data.summary.transaction_count', 7)
            ->assertJsonCount(5, 'data.recent_transactions')
            ->assertJsonPath('data.recent_transactions.0.amount', 60)
            ->assertJsonPath('data.recent_transactions.0.direction', 'debit');
    }

    public function test_transactions_endpoint_returns_paginated_list_newest_first(): void
    {
        [$senderWallet, $recipientWallet] = $this->createTransferPair(1000, 0);

        foreach ([10, 20, 30, 40, 50] as $amount) {
            $this->transferBetweenWallets($senderWallet, $recipientWallet, (float) $amount, "Transfer {$amount}");
        }

        $sender = $senderWallet->customer;

        $response = $this->actingAsAdminApi()->getJson(
            "/api/v2/admin/customers/{$sender->id}/transactions?per_page=3"
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 6)
            ->assertJsonPath('data.per_page', 3)
            ->assertJsonPath('data.current_page', 1)
            ->assertJsonPath('data.last_page', 2)
            ->assertJsonPath('data.data.0.amount', 50)
            ->assertJsonPath('data.data.0.direction', 'debit');
    }

    public function test_transactions_support_direction_filter(): void
    {
        [$senderWallet, $recipientWallet] = $this->createTransferPair(500, 0);
        $this->transferBetweenWallets($senderWallet, $recipientWallet, 100, 'Filter test transfer');

        $sender = $senderWallet->customer;

        $response = $this->actingAsAdminApi()->getJson(
            "/api/v2/admin/customers/{$sender->id}/transactions?direction=debit"
        );

        $response->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.direction', 'debit')
            ->assertJsonPath('data.data.0.type', 'transfer');
    }

    public function test_transactions_support_type_and_amount_filters(): void
    {
        [$senderWallet, $recipientWallet] = $this->createTransferPair(500, 0);
        $this->transferBetweenWallets($senderWallet, $recipientWallet, 75, 'Amount filter test');

        $sender = $senderWallet->customer;

        $response = $this->actingAsAdminApi()->getJson(
            "/api/v2/admin/customers/{$sender->id}/transactions?type=transfer&min_amount=70&max_amount=80"
        );

        $response->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.amount', 75);
    }

    public function test_transactions_support_search_filter(): void
    {
        [$senderWallet, $recipientWallet] = $this->createTransferPair(500, 0);
        $this->transferBetweenWallets($senderWallet, $recipientWallet, 55, 'UniqueSearchMarker transfer');

        $sender = $senderWallet->customer;

        $response = $this->actingAsAdminApi()->getJson(
            "/api/v2/admin/customers/{$sender->id}/transactions?search=UniqueSearchMarker"
        );

        $response->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.description', 'UniqueSearchMarker transfer');
    }

    public function test_customer_without_wallet_returns_empty_payload(): void
    {
        $customer = Customer::factory()->active()->create();

        $walletResponse = $this->actingAsAdminApi()->getJson("/api/v2/admin/customers/{$customer->id}/wallet");
        $walletResponse->assertOk()
            ->assertJsonPath('data.wallet', null)
            ->assertJsonPath('data.summary.transaction_count', 0)
            ->assertJsonPath('data.recent_transactions', []);

        $txResponse = $this->actingAsAdminApi()->getJson("/api/v2/admin/customers/{$customer->id}/transactions");
        $txResponse->assertOk()
            ->assertJsonPath('data.total', 0)
            ->assertJsonPath('data.data', []);
    }

    public function test_transactions_are_scoped_to_customer_wallet_only(): void
    {
        [$senderWallet, $recipientWallet] = $this->createTransferPair(500, 0);
        $this->transferBetweenWallets($senderWallet, $recipientWallet, 100, 'Scoped transfer');

        $sender = $senderWallet->customer;
        $recipient = $recipientWallet->customer;

        $senderResponse = $this->actingAsAdminApi()->getJson(
            "/api/v2/admin/customers/{$sender->id}/transactions?direction=debit"
        );

        $senderResponse->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.direction', 'debit');

        $recipientResponse = $this->actingAsAdminApi()->getJson(
            "/api/v2/admin/customers/{$recipient->id}/transactions?direction=credit"
        );

        $recipientResponse->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.direction', 'credit');
    }

    public function test_wallet_endpoint_returns_not_found_for_missing_customer(): void
    {
        $this->actingAsAdminApi()
            ->getJson('/api/v2/admin/customers/00000000-0000-4000-8000-000000000099/wallet')
            ->assertNotFound();
    }

    private function actingAsAdminApi(): self
    {
        Passport::actingAs($this->admin, [], 'admin-api');

        return $this->withHeaders([
            'Accept' => 'application/json',
            'Accept-Language' => 'en',
            'X-App-Locale' => 'en',
        ]);
    }
}
