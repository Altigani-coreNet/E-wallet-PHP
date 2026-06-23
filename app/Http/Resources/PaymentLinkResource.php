<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentLinkResource extends JsonResource
{
    /**
     * Symbol for checkout UI: prefer AuthService snapshot; avoid model accessor defaulting to "$" when code is not USD.
     */
    protected function displayCurrencySymbol(): string
    {
        $o = $this->currency_object;
        if (is_array($o)) {
            $sym = $o['symbol'] ?? $o['currency_symbol'] ?? '';
            if ($sym !== '' && $sym !== null) {
                return (string) $sym;
            }
        }

        $code = strtoupper((string) ($this->currency_code ?? ''));
        if ($code === 'USD') {
            return '$';
        }

        return $code !== '' ? $code : '$';
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Build public UUID-based link for sharing
        $publicLink = null;
        if ($this->uuid) {
            $baseUrl = rtrim(config('app.front_end_url', env('FRONT_END_URL', 'http://localhost:5173')), '/');
            $publicLink = $baseUrl . '/payments/' . $this->uuid;
        }

        $row = [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'merchant_id' => $this->merchant_id,
            'customer_id' => $this->customer_id,
            'amount' => $this->amount,
            // Denormalized fields for public checkout (Payment app reads these; relation is not loaded)
            'currency_code' => $this->currency_code,
            'currency_symbol' => $this->displayCurrencySymbol(),
            'currency_object' => $this->currency_object,
            'currency' => $this->currency_object,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method_types' => $this->payment_method_types,
            'short_uuid' => $this->short_uuid,
            'link' => $this->link, // Stripe gateway URL (internal use)
            'public_link' => $publicLink, // UUID-based public link for sharing
            'scheduled_date' => $this->scheduled_date,
            'expired_date' => $this->expired_date,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'merchant' => $this->whenLoaded('merchant', function () {
                $m = $this->merchant;
                if (!$m) {
                    return null;
                }

                return [
                    'id' => $m->id,
                    'uuid' => $m->id,
                    'name' => $m->name,
                    'business_name' => $m->business_name,
                    'email' => $m->email,
                    'logo' => $m->logo,
                    'logo_url' => $m->logo_url,
                ];
            }),
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'email' => $this->customer->email,
                    'phone' => $this->customer->phone,
                ];
            }),
        ];

        if ($this->relationLoaded('merchant') && $this->merchant) {
            $m = $this->merchant;
            $displayName = $m->business_name ?: $m->name;
            $row['merchant_name'] = $displayName;
            $row['merchant_uuid'] = $m->id;
            $row['merchant_logo_url'] = $m->logo_url;

            $categoryLabel = $m->business_type_display_name ?? '';
            if ($categoryLabel !== '' && $categoryLabel !== 'N/A') {
                $row['merchant_category'] = $categoryLabel;
            }
        }

        return $row;
    }
}
