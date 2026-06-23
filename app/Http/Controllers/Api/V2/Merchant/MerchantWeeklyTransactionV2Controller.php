<?php

namespace App\Http\Controllers\Api\V2\Merchant;

use App\Http\Controllers\Controller;
use App\Repositories\MerchantRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MerchantWeeklyTransactionV2Controller extends Controller
{
    public function __construct(public MerchantRepository $merchantRepository)
    {
    }

    /**
     * Get weekly transaction data for the authenticated merchant
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('external')->user();
            
            if (!$user || !$user->merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found or unauthorized'
                ], 401);
            }

            $merchantId = $user->merchant->id;
            $weeks = $request->get('weeks', 12); // Default to 12 weeks, can be customized
            
            $weeklyData = $this->merchantRepository->generateWeeklyTransactionChartDataV2($merchantId, $weeks);
            
            return response()->json([
                'success' => true,
                'data' => $weeklyData,
                'message' => 'Weekly transaction data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving weekly transaction data: ' . $e->getMessage()
            ], 500);
        }
    }
}

