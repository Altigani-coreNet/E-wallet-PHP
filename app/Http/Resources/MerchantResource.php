<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="MerchantResource",
 *     title="Merchant Resource",
 *     description="Merchant resource schema"
 * )
 */

class MerchantResource extends JsonResource
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
             * @OA\Property(property="id", type="integer", example=1, description="Merchant ID")
             */
            'id' => $this->id,
            

            /**
             * @OA\Property(property="name", type="string", example="ABC Company", description="Merchant name")
             */
            'name' => $this->name,
            
            'currency' => $this->merchantCurrency ? new CurrencyResource($this->merchantCurrency) : null,

            /**
             * @OA\Property(property="owner_name", type="string", example="John Smith", description="Owner name")
             */
            'owner_name' => $this->owner_name,
            
            /**
             * @OA\Property(property="email", type="string", format="email", example="contact@abc.com", description="Merchant email")
             */
            'email' => $this->email,
            
            /**
             * @OA\Property(property="phone", type="string", example="+971501234567", description="Merchant phone")
             */
            'phone' => $this->phone,
            
            /**
             * @OA\Property(property="address", type="string", example="Dubai, UAE", description="Merchant address")
             */
            'address' => $this->address,
            
            /**
             * @OA\Property(property="business_type", type="string", example="retail", description="Business type value")
             */
            'business_type' => $this->business_type?->value,
            
            /**
             * @OA\Property(property="business_type_display", type="string", example="Retail", description="Business type display name")
             */
            'business_type_display' => $this->business_type?->name,
            
            /**
             * @OA\Property(property="is_active", type="boolean", example=true, description="Whether the merchant is active")
             */
            'is_active' => $this->is_active,
            
            /**
             * @OA\Property(property="logo", type="string", example="http://localhost:8000/assets/media/logos/merchant-logo.png", description="Merchant logo URL")
             */
            'logo' => $this->logo_url,
            
            /**
             * @OA\Property(property="merchant_code", type="string", example="MERCH12345678", description="Unique merchant code")
             */
            'merchant_code' => $this->merchant_code,
            
            /**
             * @OA\Property(property="user_id", type="integer", nullable=true, example=1, description="Associated user ID")
             */
            'user_id' => $this->user_id,
            
            /**
             * @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z", description="Merchant creation timestamp")
             */
            'created_at' => $this->created_at,
            
            'status' => $this->status,

            'completetion_percentage' => $this->calculateProfileCompletion(),

        ];
    }
} 