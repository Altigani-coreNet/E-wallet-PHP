<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductServiceForm;
use App\Models\ServiceTransaction;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;

class ServiceTransactionService
{
    /**
     * Execute service-side logic and register service transaction details.
     */
    public function processAfterPos(Transaction $transaction, array $payload): ServiceTransaction
    {
        $productId = $payload['product_id'] ?? null;
        $servicePayload = $payload['service_payload'] ?? [];

        $record = ServiceTransaction::create([
            'transaction_id' => $transaction->id,
            'merchant_id' => $transaction->merchant_id,
            'partner_id' => $payload['partner_id'] ?? $transaction->partner_id,
            'service_id' => $payload['service_id'] ?? $transaction->service_id,
            'product_id' => $productId,
            'status' => 'pending',
            'request_payload' => is_array($servicePayload) ? $servicePayload : [],
        ]);

        // Service logic runs only for successful/captured POS transactions.
        if (strtolower((string) $transaction->status) !== 'captured') {
            $record->update([
                'status' => 'skipped',
                'error_message' => 'POS transaction is not captured; service flow skipped.',
            ]);
            return $record->fresh();
        }

        $form = $this->resolveForm($payload['service_id'] ?? null, $productId);
        $serviceUrl = $form?->form_url;

        if (! $serviceUrl) {
            $record->update([
                'status' => 'skipped',
                'error_message' => 'No service form_url configured for this request.',
            ]);
            return $record->fresh();
        }

        $outboundPayload = [
            'transaction_id' => $transaction->transaction_id,
            'merchant_id' => $transaction->merchant_id,
            'terminal_id' => $transaction->terminal_id,
            'service_id' => $payload['service_id'] ?? $transaction->service_id,
            'product_id' => $productId,
            'amount' => $transaction->amount,
            'currency_id' => $transaction->currency_id,
            'transaction_status' => $transaction->status,
            'service_payload' => is_array($servicePayload) ? $servicePayload : [],
        ];

        try {
            $response = Http::timeout(30)->acceptJson()->post($serviceUrl, $outboundPayload);

            $record->update([
                'service_url' => $serviceUrl,
                'service_response' => [
                    'status_code' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ],
                'status' => $response->successful() ? 'completed' : 'failed',
                'error_message' => $response->successful() ? null : 'Service endpoint returned non-success status.',
            ]);
        } catch (\Throwable $e) {
            $record->update([
                'service_url' => $serviceUrl,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $record->fresh();
    }

    private function resolveForm(?string $serviceId, ?string $productId): ?ProductServiceForm
    {
        if (! $productId || ! $serviceId) {
            return null;
        }

        $product = Product::query()
            ->where('id', $productId)
            ->where('service_id', $serviceId)
            ->with('serviceForms')
            ->first();

        return $product?->serviceForms?->sortBy('id')->first();
    }
}

