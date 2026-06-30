<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Modules\CustomerAuth\Support\CustomerJwtService;
use App\Services\WalletService;
use Database\Seeders\ChartOfAccountSeeder;
use Illuminate\Testing\TestResponse;
use Tests\CustomerAuthTestCase;

class CustomerWalletTransferValidationTest extends CustomerAuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.otp.mock_code' => 111111]);

        $this->seed(ChartOfAccountSeeder::class);
        $master = app(WalletService::class)->createMasterWallet();
        app(WalletService::class)->cashIn($master, 1000000, 'Fund master float');
    }

    public function test_transfer_http_rejects_non_numeric_amount(): void
    {
        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);

        $this->transfer($sender, $recipient->wallet->wallet_id, 'abc')
            ->assertStatus(422);
    }

    public function test_transfer_http_ignores_client_fee_field_and_uses_config_fee(): void
    {
        config(['services.wallet.transfer_fee' => 2]);

        [$sender, $recipient] = $this->createFundedCustomers(500.00, 0.00);
        $idempotencyKey = 'ignored-fee-'.uniqid();
        $otp = $this->requestTransferOtp($sender, $recipient->wallet->wallet_id, 5.00, null, $idempotencyKey);

        $this->withCustomerToken($sender)
            ->postJson('/api/v1/customer/wallet/transfer', [
                'recipient_wallet_id' => $recipient->wallet->wallet_id,
                'amount' => 5,
                'fee' => 10,
                'otp_token' => $otp['otp_token'],
                'otp' => 111111,
            ], ['Idempotency-Key' => $idempotencyKey])
            ->assertOk()
            ->assertJsonPath('data.fee', 2)
            ->assertJsonPath('data.recipient_amount', 3);
    }

    /**
     * @return array{0: Customer, 1: Customer}
     */
    private function createFundedCustomers(float $senderBalance, float $recipientBalance): array
    {
        $walletService = app(WalletService::class);

        $sender = Customer::factory()->active()->create();
        $recipient = Customer::factory()->active()->create();

        $senderWallet = $walletService->walletForCustomer($sender) ?? $walletService->createForCustomer($sender);
        $recipientWallet = $walletService->walletForCustomer($recipient) ?? $walletService->createForCustomer($recipient);

        if ($senderBalance > 0) {
            $walletService->cashIn($senderWallet, $senderBalance, 'Test funding');
        }

        if ($recipientBalance > 0) {
            $walletService->cashIn($recipientWallet, $recipientBalance, 'Test funding');
        }

        $sender->setRelation('wallet', $senderWallet->fresh());
        $recipient->setRelation('wallet', $recipientWallet->fresh());

        return [$sender, $recipient];
    }

    /**
     * @return array{otp_token: string, expires_at: string}
     */
    private function requestTransferOtp(
        Customer $sender,
        string $recipientWalletId,
        float $amount,
        ?string $description = null,
        ?string $idempotencyKey = null
    ): array {
        $headers = [];
        if ($idempotencyKey !== null) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        $response = $this->withCustomerToken($sender)
            ->withHeaders($headers)
            ->postJson('/api/v1/customer/wallet/transfer/otp', array_filter([
                'recipient_wallet_id' => $recipientWalletId,
                'amount' => $amount,
                'description' => $description,
            ], fn ($value) => $value !== null));

        $response->assertCreated();

        return [
            'otp_token' => $response->json('data.otp_token'),
            'expires_at' => $response->json('data.expires_at'),
        ];
    }

    private function transfer(Customer $sender, string $recipientWalletId, mixed $amount): TestResponse
    {
        return $this->withCustomerToken($sender)
            ->postJson('/api/v1/customer/wallet/transfer', [
                'recipient_wallet_id' => $recipientWalletId,
                'amount' => $amount,
            ], ['Idempotency-Key' => 'validation-'.uniqid()]);
    }

    private function withCustomerToken(Customer $customer): static
    {
        $auth = app(CustomerJwtService::class)->createToken(
            $customer->id,
            $customer->email ?: $customer->phone,
        );

        return $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['token'],
        ]);
    }
}
