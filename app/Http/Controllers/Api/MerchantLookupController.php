<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MerchantLookupController extends Controller
{
    public function testMerchantIds(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 8);
        $limit = $limit > 0 ? $limit : 8;

        $merchants = Merchant::query()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'business_type']);

        $ids = $merchants->pluck('id')->values()->all();
        $merchantsWithType = $merchants->map(fn ($m) => [
            'id' => $m->id,
            'business_type' => $m->business_type?->value ?? $m->business_type,
        ])->values()->all();

        return response()->json([
            'ids' => $ids,
            'merchants' => $merchantsWithType,
        ]);
    }
}
