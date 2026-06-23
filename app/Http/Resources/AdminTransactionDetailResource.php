<?php

namespace App\Http\Resources;

use App\Models\Currency;
use App\Support\LocaleString;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin transaction detail: whitelisted transaction fields + nested merchant, partner, service, payment method.
 */
class AdminTransactionDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currency = $this->resource->relationLoaded('currency')
            ? $this->resource->getRelation('currency')
            : null;
        $user = $this->resource->relationLoaded('user')
            ? $this->resource->getRelation('user')
            : null;

        $paymentMethod = $this->resource->relationLoaded('paymentMethod')
            ? $this->resource->getRelation('paymentMethod')
            : null;

        $paymentMethodPayload = null;
        if ($paymentMethod) {
            $paymentMethodPayload = [
                'card_type' => $paymentMethod->card_type,
                'cardholder_name' => $paymentMethod->cardholder_name,
                'payment_channel' => $paymentMethod->payment_channel,
                'entry_mode' => $paymentMethod->entry_mode,
            ];
        }

        $data = [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'amount' => $this->amount,
            'original_amount' => $this->original_amount,
            'refundable_amount' => $this->refundable_amount,
            'status' => $this->status,
            'state' => $this->state,
            'transaction_type' => $this->transaction_type,
            'payment_type' => $this->payment_type,
            'method' => $this->method ?? $paymentMethod?->card_type,
            'created_at' => $this->created_at,
            'timestamp' => $this->timestamp,
            'currency_symbol' => LocaleString::one($currency?->symbol) ?? $this->currency_symbol,
            'currency_id' => $this->currency_id,
            'merchant_id' => $this->merchant_id,
            'partner_id' => $this->partner_id,
            'service_id' => $this->service_id,
            'service_category_id' => $this->service_category_id,
            'service_category_name' => $this->service_category_name,
            'service_name' => $this->service_name,
            'partner_name' => $this->partner_name,
            'terminal_id' => $this->terminal_id,
            'user_id' => $this->user_id,
            'rrn' => $this->rrn,
            'batch_no' => $this->batch_no,
            'trace_no' => $this->trace_no,
            'auth_code' => $this->auth_code,
            'sdk' => $this->sdk,
            'sdk_id' => $this->sdk_id,
            'invoice_no' => $this->invoice_no,
            'mid' => $this->mid,
            'tid' => $this->tid,
            'atc' => $this->atc,
            'tvr' => $this->tvr,
            'tsi' => $this->tsi,
            'app_name' => $this->app_name,
            'card_number' => $this->card_number,
            'expiry' => $this->expiry,
            'decline_reason' => $this->decline_reason,
            'error_message' => $this->error_message,
            'transaction_encrypted_id' => $this->resource->transaction_encrypted_id ?? null,
            'invoice_url' => $this->resource->invoice_url ?? null,
            'payment_method' => $paymentMethodPayload,
            'paymentMethod' => $paymentMethodPayload,
        ];

        if ($this->resource->relationLoaded('merchant') && $this->resource->merchant) {
            $data['merchant'] = (new MerchantMerchantSnapshotResource($this->resource->merchant))->resolve();
        }

        if ($this->resource->relationLoaded('partner') && $this->resource->partner) {
            $data['partner'] = (new MerchantPartnerSnapshotResource($this->resource->partner))->resolve();
        }

        if ($this->resource->relationLoaded('service') && $this->resource->service) {
            $data['service'] = (new ServiceResource($this->resource->service))->resolve();
        }

        if ($this->resource->relationLoaded('serviceCategory') && $this->resource->serviceCategory) {
            $data['service_category'] = (new ServiceCategoryResource($this->resource->serviceCategory))->resolve();
        }

        if ($this->resource->relationLoaded('country') && $this->resource->getRelation('country')) {
            $data['country'] = (new CountryResource($this->resource->getRelation('country')))->resolve();
        }

        unset($data['currency']);
        if ($currency instanceof Currency) {
            $data['currency'] = (new CurrencyResource($currency))->resolve();
        }

        if ($user) {
            $data['user'] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];
        }

        if ($this->resource->relationLoaded('batch') && $this->resource->batch) {
            $data['batch'] = [
                'id' => $this->resource->batch->id,
                'batch_number' => $this->resource->batch->batch_number,
                'status' => $this->resource->batch->status,
            ];
        }

        return $data;
    }
}
