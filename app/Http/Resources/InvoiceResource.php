<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $data = is_array($this->resource) ? $this->resource : [];

        return [
            'id' => $data['id'] ?? null,
            'transaction_id' => $data['transaction_id'] ?? null,
            'reference_no' => $data['reference_no'] ?? null,
            'receipt_number' => $data['receipt_number'] ?? null,
            'date' => $data['date'] ?? null,
            'time' => $data['time'] ?? null,
            'created_at' => $data['created_at'] ?? null,
            'payment_status' => $data['payment_status'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
            'payment_type' => $data['payment_type'] ?? null,
            'transaction_type' => $data['transaction_type'] ?? null,
            'total_qty' => $data['total_qty'] ?? 1,
            'currency_symbol' => $data['currency_symbol'] ?? '$',
            'currency_object' => $data['currency_object'] ?? null,
            'amount' => $data['amount'] ?? 0,
            'grand_total' => $data['grand_total'] ?? $data['amount'] ?? 0,
            'customer' => $data['customer'] ?? null,
            'shop' => $data['shop'] ?? null,
            'products' => $data['products'] ?? [],
            'card_number' => $data['card_number'] ?? null,
            'expiry' => $data['expiry'] ?? null,
            'merchant_id' => $data['merchant_id'] ?? null,
            'terminal_id' => $data['terminal_id'] ?? null,
            'ref_number' => $data['ref_number'] ?? null,
            'invoice_url' => $data['invoice_url'] ?? null,
            'powered_by' => $data['powered_by'] ?? 'CoreNet Technologies',
            'footer' => $data['footer'] ?? [],
            'meta' => $data['meta'] ?? [],
        ];
    }
}
