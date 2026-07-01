<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductServiceForm;
use App\Models\Service;
use App\Models\Wallet;
use App\Models\WalletBillPayment;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class WalletBillPaymentService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly PartnerApiClient $partnerApiClient,
    ) {
    }

    /**
     * @param  array<string, mixed>  $servicePayload
     * @return array{
     *     amount: float,
     *     fee: float,
     *     total_debited: float,
     *     wallet: Wallet,
     *     bill_payment: WalletBillPayment,
     *     transaction: WalletTransaction|null
     * }
     */
    public function process(
        Customer $customer,
        Wallet $wallet,
        Service $service,
        ?Product $product,
        float $amount,
        array $servicePayload = [],
        ?string $description = null,
        ?string $idempotencyKey = null,
    ): array {
        $fee = $this->walletService->billPaymentFee();
        $amount = round($amount, 2);
        $totalDebited = round($amount + $fee, 2);

        $partner = $service->partner()->first();
        if (! $partner) {
            throw new InvalidArgumentException('Service partner is not configured.');
        }

        if (! $partner->account_id) {
            throw new InvalidArgumentException('Bill payment is not available for this provider.');
        }

        $payableAccount = $partner->chartOfAccount;
        if (! $payableAccount) {
            throw new InvalidArgumentException('Bill payment is not available for this provider.');
        }

        $this->walletService->assertSufficientBalance(
            $wallet,
            $totalDebited,
            'Insufficient wallet balance for this bill payment.'
        );

        $form = $this->resolveForm($service, $product);
        $serviceUrl = $this->resolvePartnerServiceUrl($form, $product);
        if (
            ! app()->isProduction()
            && (
                ! empty($servicePayload['_mock_partner_fail'])
                || str_contains((string) ($description ?? ''), '[MOCK_PARTNER_FAIL]')
            )
        ) {
            $serviceUrl = 'https://bill-mock.test/fail';
        }
        if (! $serviceUrl) {
            throw new InvalidArgumentException('No partner service URL configured for this request.');
        }

        $outboundServicePayload = $servicePayload;
        unset($outboundServicePayload['_mock_partner_fail']);

        $billPayment = WalletBillPayment::query()->create([
            'wallet_id' => $wallet->id,
            'customer_id' => $customer->id,
            'partner_id' => $partner->id,
            'service_id' => $service->id,
            'product_id' => $product?->id,
            'amount' => $amount,
            'fee' => $fee,
            'total_debited' => $totalDebited,
            'status' => WalletBillPayment::STATUS_PENDING,
            'service_payload' => $servicePayload,
            'idempotency_key' => $idempotencyKey,
        ]);

        $outboundPayload = [
            'transaction_id' => 'WBP-'.$billPayment->id,
            'merchant_id' => null,
            'terminal_id' => null,
            'service_id' => $service->id,
            'product_id' => $product?->id,
            'amount' => $amount,
            'currency_id' => $wallet->currency_id,
            'transaction_status' => 'wallet_confirmed',
            'service_payload' => $outboundServicePayload,
        ];

        $response = $this->partnerApiClient->post($serviceUrl, $outboundPayload);

        if (! $response->successful) {
            $billPayment->update([
                'status' => WalletBillPayment::STATUS_FAILED,
                'service_url' => $serviceUrl,
                'partner_response' => [
                    'status_code' => $response->statusCode,
                    'body' => $response->body,
                ],
                'error_message' => $response->errorMessage ?? 'Partner API failed.',
            ]);

            throw new RuntimeException($response->errorMessage ?? 'Partner API failed.');
        }

        $partnerReference = is_array($response->body)
            ? ($response->body['reference'] ?? $response->body['transaction_id'] ?? null)
            : null;

        return DB::transaction(function () use (
            $wallet,
            $payableAccount,
            $amount,
            $fee,
            $description,
            $billPayment,
            $serviceUrl,
            $response,
            $partnerReference,
        ) {
            $this->walletService->billPay(
                $wallet,
                $payableAccount,
                $amount,
                $fee,
                $description,
                (string) $billPayment->id,
            );

            $billPayment->update([
                'status' => WalletBillPayment::STATUS_COMPLETED,
                'service_url' => $serviceUrl,
                'partner_response' => [
                    'status_code' => $response->statusCode,
                    'body' => $response->body,
                ],
                'partner_reference' => $partnerReference,
                'ledger_reference' => LedgerService::REF_BILL_PAYMENT,
                'ledger_reference_id' => (string) $billPayment->id,
            ]);

            $wallet->refresh();

            $transaction = WalletTransaction::query()
                ->where('wallet_id', $wallet->id)
                ->where('type', 'bill_payment')
                ->latest('id')
                ->first();

            return [
                'amount' => $amount,
                'fee' => $fee,
                'total_debited' => round($amount + $fee, 2),
                'wallet' => $wallet,
                'bill_payment' => $billPayment->fresh(),
                'transaction' => $transaction,
            ];
        });
    }

    private function resolveForm(Service $service, ?Product $product): ?ProductServiceForm
    {
        if ($product) {
            return ProductServiceForm::query()
                ->where('product_id', $product->id)
                ->orderBy('id')
                ->first();
        }

        $firstProduct = Product::query()
            ->where('service_id', $service->id)
            ->orderBy('id')
            ->first();

        if (! $firstProduct) {
            return null;
        }

        return ProductServiceForm::query()
            ->where('product_id', $firstProduct->id)
            ->orderBy('id')
            ->first();
    }

    private function resolvePartnerServiceUrl(?ProductServiceForm $form, ?Product $product): ?string
    {
        $candidates = [
            $form?->form_url,
            $product?->service_url,
            $product?->prepay_url,
        ];

        foreach ($candidates as $url) {
            if (is_string($url) && trim($url) !== '') {
                return trim($url);
            }
        }

        if (app()->environment('local')) {
            return 'https://bill-mock.test/pay';
        }

        return null;
    }
}
