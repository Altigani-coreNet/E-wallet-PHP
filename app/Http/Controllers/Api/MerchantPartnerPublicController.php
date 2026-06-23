<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantPartnerSnapshotResource;
use App\Models\Partner;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MerchantPartnerPublicController extends Controller
{
    use ApiResponse;

    /**
     * Merchant API: read-only partner profile (approved / active only).
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $partner = Partner::query()
            ->withoutGlobalScopes()
            ->where('id', $id)
            ->where('is_active', true)
            ->where('status', 'approved')
            ->with(['country:id,name'])
            ->first();

        if (! $partner) {
            return $this->ErrorMessage(
                'Partner not found or not available.',
                'MERCHANT_PARTNER_NOT_FOUND',
                404
            );
        }

        return $this->SuccessMessage([
            'partner' => new MerchantPartnerSnapshotResource($partner),
        ]);
    }
}
