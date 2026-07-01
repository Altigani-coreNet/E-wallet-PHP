<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\TransactionLine;
use App\Models\WalletBillPayment;
use App\Modules\Accounting\Services\AccountBalanceService;
use App\Services\AccountingService;
use App\Services\LedgerService;
use App\Services\WalletBillPaymentService;
use App\Services\WalletService;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\Support\AdminWalletTestHelpers;
use Tests\Support\CustomerServicesCatalogTestHelper;
use Tests\TestCase;

class WalletBillPaymentServiceTest extends TestCase
{
    use AdminWalletTestHelpers;
    use CustomerServicesCatalogTestHelper;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountSeeder::class);
        app(AccountingService::class)->recordOpeningCapital(1000, 'Opening equity');
        app(WalletService::class)->createMasterWallet();
    }

    public function test_partner_api_success_posts_single_bill_payment_entry(): void
    {
        Http::fake([
            'https://bill-mock.test/pay' => Http::response(['success' => true, 'reference' => 'MOCK-1'], 200),
        ]);

        [$customer, $wallet, $catalog] = $this->seedBillPaymentCustomer(200);
        $service = app(WalletBillPaymentService::class);

        $result = $service->process(
            $customer,
            $wallet,
            $catalog['service'],
            $catalog['product'],
            100,
            ['account_number' => '12345'],
            'Electricity bill',
            'bill-svc-1',
        );

        $this->assertSame('completed', $result['bill_payment']->status);
        $this->assertGreaterThanOrEqual(2, TransactionLine::query()->where('reference', LedgerService::REF_BILL_PAYMENT)->count());
        $this->assertTrue(app(AccountBalanceService::class)->isSystemBalanced());
    }

    public function test_partner_api_failure_creates_no_ledger_lines(): void
    {
        Http::fake([
            'https://bill-mock.test/pay' => Http::response(['error' => 'fail'], 500),
        ]);

        [$customer, $wallet, $catalog] = $this->seedBillPaymentCustomer(200);
        $service = app(WalletBillPaymentService::class);

        $this->expectException(\RuntimeException::class);

        try {
            $service->process(
                $customer,
                $wallet,
                $catalog['service'],
                $catalog['product'],
                100,
            );
        } finally {
            $this->assertSame(0, TransactionLine::query()->where('reference', LedgerService::REF_BILL_PAYMENT)->count());
            $this->assertSame(
                WalletBillPayment::STATUS_FAILED,
                WalletBillPayment::query()->latest('id')->value('status')
            );
        }
    }

    public function test_partner_without_payable_account_is_rejected(): void
    {
        $catalog = $this->seedCustomerServicesCatalog();
        [$customer, $wallet] = $this->seedBillPaymentCustomer(200, skipPayable: true);

        $this->expectException(InvalidArgumentException::class);

        app(WalletBillPaymentService::class)->process(
            $customer,
            $wallet,
            $catalog['service'],
            $catalog['product'],
            100,
        );
    }

    /**
     * @return array{0: Customer, 1: \App\Models\Wallet, 2: array}
     */
    private function seedBillPaymentCustomer(float $balance, bool $skipPayable = false): array
    {
        $catalog = $skipPayable
            ? $this->seedCustomerServicesCatalog()
            : $this->seedPartnerWithPayableAccount(null, 'https://bill-mock.test/pay');

        $customer = Customer::factory()->active()->create([
            'country_id' => $catalog['country']->id,
        ]);

        $wallet = $this->createFundedWallet($customer, $balance);

        return [$customer, $wallet, $catalog];
    }
}
