<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Merchant;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class MerchantDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_dashboard_loads_with_transaction_data()
    {
        // Create a merchant
        $merchant = Merchant::factory()->create();
        
        // Create a user associated with the merchant
        $user = User::factory()->create([
            'merchant_id' => $merchant->id
        ]);

        // Create some test transactions
        Transaction::factory()->create([
            'merchant_id' => $merchant->id,
            'amount' => 100.00,
            'status' => 'approved',
            'created_at' => Carbon::now()
        ]);

        Transaction::factory()->create([
            'merchant_id' => $merchant->id,
            'amount' => 200.00,
            'status' => 'approved',
            'created_at' => Carbon::now()->subDays(1)
        ]);

        // Authenticate as the user
        $this->actingAs($user);

        // Visit the dashboard
        $response = $this->get('/merchant/dashboard');

        // Assert the response is successful
        $response->assertStatus(200);
        
        // Assert the view contains transaction data
        $response->assertViewHas('dailyStats');
        $response->assertViewHas('weeklyStats');
        $response->assertViewHas('monthlyStats');
        $response->assertViewHas('transactionSummary');
    }

    public function test_merchant_dashboard_without_transactions()
    {
        // Create a merchant
        $merchant = Merchant::factory()->create();
        
        // Create a user associated with the merchant
        $user = User::factory()->create([
            'merchant_id' => $merchant->id
        ]);

        // Authenticate as the user
        $this->actingAs($user);

        // Visit the dashboard
        $response = $this->get('/merchant/dashboard');

        // Assert the response is successful
        $response->assertStatus(200);
        
        // Assert the view contains empty transaction data
        $response->assertViewHas('dailyStats');
        $response->assertViewHas('weeklyStats');
        $response->assertViewHas('monthlyStats');
        $response->assertViewHas('transactionSummary');
    }
}
