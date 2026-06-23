<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="PaymentByLinkResource",
 *     title="Payment By Link Resource",
 *     description="Payment link resource schema"
 * )
 */
class PaymentByLinkResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /**
             * @OA\Property(property="id", type="integer", example=1, description="Payment Link ID")
             */
            'id' => $this->id,

            /**
             * @OA\Property(property="merchant_id", type="integer", example=10, description="Merchant ID")
             */
            'merchant_id' => $this->merchant_id,

            /**
             * @OA\Property(property="status", type="string", example="active", description="Status of the payment link")
             */
            'status' => $this->status,

            /**
             * @OA\Property(property="link", type="string", example="https://pay.example.com/abc123", description="Payment link URL")
             */
            'link' => $this->link,

            /**
             * @OA\Property(property="payment_sdk", type="string", example="stripe", description="Payment SDK used")
             */
            'payment_sdk' => $this->payment_sdk,

            /**
             * @OA\Property(property="amount", type="number", format="float", example=100.50, description="Payment amount")
             */
            'amount' => $this->amount,

            /**
             * @OA\Property(property="currency_code", type="string", example="USD", description="Currency code")
             */
            'currency_code' => optional($this->currency)->currency_code ?? null,
            /**
             * @OA\Property(property="currency_id", type="integer", example=1, description="Currency ID")
             */
            'currency_id' => $this->currency_id,

            /**
             * @OA\Property(property="payment_method_types", type="array", @OA\Items(type="string"), example={"card", "bank_transfer"}, description="Allowed payment method types")
             */
            'payment_method_types' => $this->payment_method_types,

            /**
             * @OA\Property(property="scheduled_date", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z", description="Scheduled payment date")
             */
            'scheduled_date' => $this->scheduled_date,

            /**
             * @OA\Property(property="customer_id", type="integer", example=5, description="Customer ID")
             */
            'customer_id' => $this->customer_id,

            /**
             * @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z", description="Creation timestamp")
             */
            'created_at' => $this->created_at,

            /**
             * @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z", description="Last update timestamp")
             */
            'updated_at' => $this->updated_at,
        ];
    }
}
