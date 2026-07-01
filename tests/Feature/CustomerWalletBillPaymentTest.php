<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\TransactionLine;
use App\Models\WalletBillPayment;
use App\Models\WalletTransaction;
use App\Modules\Accounting\Services\AccountBalanceService;
use App\Services\LedgerService;
use App\Services\WalletService;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Support\Facades\Http;
use Tests\CustomerAuthTestCase;
use Tests\Support\CustomerServicesCatalogTestHelper;

class CustomerWalletBillPaymentTest extends CustomerAuthTestCase
{
    use CustomerServicesCatalogTestHelper;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.otp.mock_code' => 111111]);

        $this->seed(ChartOfAccountSeeder::class);
        $master = app(WalletService::class)->createMasterWallet();
        app(WalletService::class)->cashIn($master, 1000000, 'Fund master float');
    }

    public function test_customer_can_pay_bill_with_otp(): void
    {
        Http::fake([
            'https://bill-mock.test/pay' => Http::response(['success' => true, 'reference' => 'MOCK-1'], 200),
        ]);

        [$customer, $catalog] = $this->createBillPaymentCustomer(200);

        $idempotencyKey = 'bill-pay-'.uniqid();
        $otp = $this->requestBillPaymentOtp($customer, $catalog, 100, $idempotencyKey, 'Electricity bill');

        $response = $this->withCustomerToken($customer)
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/customer/wallet/bill-payment', [
                'service_id' => $catalog['service']->id,
                'product_id' => $catalog['product']->id,
                'amount' => 100,
                'service_payload' => ['account_number' => '12345'],
                'description' => 'Electricity bill',
                'otp_token' => $otp['otp_token'],
                'otp' => 111111,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.amount', 100)
            ->assertJsonPath('data.wallet.balance', 100)
            ->assertJsonPath('data.bill_payment.status', 'completed')
            ->assertJsonPath('data.transaction.type', 'bill_payment');

        $this->assertTrue(app(AccountBalanceService::class)->isSystemBalanced());
    }

    public function test_bill_payment_rejects_insufficient_balance(): void
    {
        Http::fake();

        [$customer, $catalog] = $this->createBillPaymentCustomer(50);

        $this->withCustomerToken($customer)
            ->postJson('/api/v1/customer/wallet/bill-payment/otp', [
                'service_id' => $catalog['service']->id,
                'product_id' => $catalog['product']->id,
                'amount' => 100,
            ])
            ->assertStatus(422);

        $this->assertSame(0, TransactionLine::query()->where('reference', LedgerService::REF_BILL_PAYMENT)->count());
    }

    public function test_bill_payment_idempotency_prevents_double_debit(): void
    {
        Http::fake([
            'https://bill-mock.test/pay' => Http::response(['success' => true, 'reference' => 'MOCK-1'], 200),
        ]);

        [$customer, $catalog] = $this->createBillPaymentCustomer(200);
        $idempotencyKey = 'bill-idem-'.uniqid();
        $otp = $this->requestBillPaymentOtp($customer, $catalog, 100, $idempotencyKey);

        $payload = [
            'service_id' => $catalog['service']->id,
            'product_id' => $catalog['product']->id,
            'amount' => 100,
            'service_payload' => ['account_number' => '12345'],
            'otp_token' => $otp['otp_token'],
            'otp' => 111111,
        ];

        $first = $this->withCustomerToken($customer)
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/customer/wallet/bill-payment', $payload);

        $first->assertOk();

        $second = $this->withCustomerToken($customer)
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/customer/wallet/bill-payment', $payload);

        $second->assertOk();
        $this->assertSame($first->json('data.bill_payment.id'), $second->json('data.bill_payment.id'));
        $this->assertSame(1, WalletTransaction::query()->where('type', 'bill_payment')->count());
    }

    public function test_partner_without_payable_account_returns_422(): void
    {
        $catalog = $this->seedCustomerServicesCatalog();
        $customer = Customer::factory()->active()->create(['country_id' => $catalog['country']->id]);
        app(WalletService::class)->createForCustomer($customer);
        app(WalletService::class)->cashIn(
            app(WalletService::class)->walletForCustomer($customer),
            200,
            'Fund'
        );

        $this->withCustomerToken($customer)
            ->postJson('/api/v1/customer/wallet/bill-payment/otp', [
                'service_id' => $catalog['service']->id,
                'product_id' => $catalog['product']->id,
                'amount' => 100,
            ])
            ->assertStatus(422);
    }

    public function test_partner_api_failure_returns_502_without_ledger(): void
    {
        Http::fake([
            'https://bill-mock.test/pay' => Http::response(['error' => 'fail'], 500),
        ]);

        [$customer, $catalog] = $this->createBillPaymentCustomer(200);
        $idempotencyKey = 'bill-fail-'.uniqid();
        $otp = $this->requestBillPaymentOtp($customer, $catalog, 100, $idempotencyKey);

        $this->withCustomerToken($customer)
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->postJson('/api/v1/customer/wallet/bill-payment', [
                'service_id' => $catalog['service']->id,
                'product_id' => $catalog['product']->id,
                'amount' => 100,
                'service_payload' => ['account_number' => '12345'],
                'otp_token' => $otp['otp_token'],
                'otp' => 111111,
            ])
            ->assertStatus(502);

        $this->assertSame(0, TransactionLine::query()->where('reference', LedgerService::REF_BILL_PAYMENT)->count());
        $this->assertSame(
            WalletBillPayment::STATUS_FAILED,
            WalletBillPayment::query()->latest('id')->value('status')
        );
    }

    /**
     * @return array{0: Customer, 1: array}
     */
    private function createBillPaymentCustomer(float $balance): array
    {
        $catalog = $this->seedPartnerWithPayableAccount(null, 'https://bill-mock.test/pay');
        $customer = Customer::factory()->active()->create(['country_id' => $catalog['country']->id]);
        $wallet = app(WalletService::class)->createForCustomer($customer);
        app(WalletService::class)->cashIn($wallet, $balance, 'Fund customer');

        return [$customer, $catalog];
    }

    /**
     * @param  array<string, mixed>  $catalog
     * @return array{otp_token: string, expires_at: string}
     */
    private function requestBillPaymentOtp(
        Customer $customer,
        array $catalog,
        float $amount,
        ?string $idempotencyKey = null,
        ?string $description = null
    ): array {
        $headers = [];
        if ($idempotencyKey !== null) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        $response = $this->withCustomerToken($customer)
            ->withHeaders($headers)
            ->postJson('/api/v1/customer/wallet/bill-payment/otp', [
                'service_id' => $catalog['service']->id,
                'product_id' => $catalog['product']->id,
                'amount' => $amount,
                'service_payload' => ['account_number' => '12345'],
                'description' => $description,
            ]);

        $response->assertCreated();

        return [
            'otp_token' => $response->json('data.otp_token'),
            'expires_at' => $response->json('data.expires_at'),
        ];
    }

    private function withCustomerToken(Customer $customer): self
    {
        $auth = app(\App\Modules\CustomerAuth\Support\CustomerJwtService::class)->createToken(
            $customer->id,
            $customer->email ?: $customer->phone,
        );

        return $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
            'Accept' => 'application/json',
        ]);
    }
}
