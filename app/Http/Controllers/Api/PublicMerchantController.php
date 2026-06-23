<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use Illuminate\Http\JsonResponse;

/**
 * Read-only merchant snapshot for public payment/checkout flows (no auth).
 */
class PublicMerchantController extends Controller
{
    public function show(string $uuid): JsonResponse
    {
        $merchant = Merchant::withoutGlobalScopes()
            ->whereKey($uuid)
            ->first();

        if (!$merchant) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant not found',
            ], 404);
        }

        $businessTypeLabel = $merchant->business_type_display_name ?? null;
        if ($businessTypeLabel === 'N/A') {
            $businessTypeLabel = null;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $merchant->id,
                'uuid' => $merchant->id,
                'name' => $merchant->name,
                'business_name' => $merchant->business_name,
                'owner_name' => $merchant->owner_name,
                'email' => $merchant->email,
                'phone' => $merchant->phone,
                'address' => $merchant->address,
                'logo_url' => $merchant->logo_url,
                'business_type' => $businessTypeLabel,
                'merchant_code' => $merchant->merchant_code,
                'created_at' => $merchant->created_at,
                // For public “Activation status” UI (maps to Activated / Pending / …)
                'status' => $merchant->status,
            ],
        ]);
    }
}
