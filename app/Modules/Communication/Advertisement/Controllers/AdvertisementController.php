<?php

namespace App\Modules\Communication\Advertisement\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Support\AdvertisementPublicListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    public function getByCountry(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $countryId = $user->country_id ?? null;
            $query = Advertisement::query();

            if ($countryId) {
                $query->where('country_id', $countryId);
            }

            $advertisements = $query
                ->visibleInPublicApiListing()
                ->get()
                ->map(fn ($ad) => AdvertisementPublicListing::apiPayload($ad));

            return response()->json([
                'success' => true,
                'data' => $advertisements,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch advertisements',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
