<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Admin;
use App\Models\Terminal;
use App\Models\Transaction;
use App\Models\Merchant;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an admin user
        $this->admin = Admin::factory()->create();
    }

    /** @test */
    public function admin_can_access_dashboard()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
    }

    /** @test */
    public function dashboard_shows_correct_statistics()
    {
        // Create some test data
        $merchant = Merchant::factory()->create();
        $branch = Branch::factory()->create(['merchant_id' => $merchant->id]);
        
        // Create terminals with different statuses
        Terminal::factory()->count(5)->create(['terminal_status' => 'online']);
        Terminal::factory()->count(3)->create(['terminal_status' => 'offline']);
        Terminal::factory()->count(2)->create(['terminal_status' => 'testing']);
        
        // Create transactions
        Transaction::factory()->count(10)->create([
            'status' => 'approved',
            'amount' => 100.00
        ]);
        
        Transaction::factory()->count(3)->create([
            'status' => 'refunded',
            'amount' => 50.00
        ]);
        
        Transaction::factory()->count(2)->create([
            'status' => 'voided',
            'amount' => 25.00
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Check that the view has the expected variables
        $response->assertViewHas('totalTerminals', 10);
        $response->assertViewHas('onlineCount', 5);
        $response->assertViewHas('offlineCount', 3);
        $response->assertViewHas('testingCount', 2);
        $response->assertViewHas('totalTransactions', 15);
        $response->assertViewHas('saleTransactions', 10);
        $response->assertViewHas('refundTransactions', 3);
        $response->assertViewHas('voidTransactions', 2);
    }

    /** @test */
    public function dashboard_shows_transaction_summary()
    {
        // Create transactions for different time periods
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();
        $thisYear = now()->startOfYear();
        
        // Today's transactions
        Transaction::factory()->create([
            'created_at' => $today->addHours(2),
            'amount' => 100.00
        ]);
        
        // This week's transactions
        Transaction::factory()->create([
            'created_at' => $thisWeek->addDays(2),
            'amount' => 200.00
        ]);
        
        // This month's transactions
        Transaction::factory()->create([
            'created_at' => $thisMonth->addDays(5),
            'amount' => 300.00
        ]);
        
        // This year's transactions
        Transaction::factory()->create([
            'created_at' => $thisYear->addMonths(2),
            'amount' => 400.00
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Check transaction summary
        $response->assertViewHas('transactionSummary.today.count', 1);
        $response->assertViewHas('transactionSummary.today.amount', 100.00);
        $response->assertViewHas('transactionSummary.this_week.count', 2);
        $response->assertViewHas('transactionSummary.this_week.amount', 300.00);
        $response->assertViewHas('transactionSummary.this_year.count', 4);
        $response->assertViewHas('transactionSummary.this_year.amount', 1000.00);
    }

    /** @test */
    public function dashboard_shows_latest_transactions()
    {
        // Create some transactions
        $transactions = Transaction::factory()->count(5)->create([
            'status' => 'approved',
            'amount' => 100.00
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Check that latest transactions are available
        $response->assertViewHas('latestSales');
        $response->assertViewHas('latestRefunds');
        $response->assertViewHas('latestVoids');
    }

    /** @test */
    public function dashboard_shows_chart_data()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        
        // Check that chart data is available
        $response->assertViewHas('latestSales');
        $response->assertViewHas('latestRefunds');
        $response->assertViewHas('latestVoids');
    }
}
