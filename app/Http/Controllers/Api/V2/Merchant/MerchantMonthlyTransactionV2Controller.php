<?php

namespace App\Http\Controllers\Api\V2\Merchant;

use App\Http\Controllers\Controller;
use App\Repositories\MerchantRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MerchantMonthlyTransactionV2Controller extends Controller
{
    public function __construct(public MerchantRepository $merchantRepository)
    {
    }

    /**
     * Get monthly transaction data for the authenticated merchant
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
            $months = $request->get('months', 12); // Default to 12 months, can be customized
            
            $monthlyData = $this->merchantRepository->generateMonthlyTransactionChartDataV2($merchantId, $months);
            
            return response()->json([
                'success' => true,
                'data' => $monthlyData,
                'message' => 'Monthly transaction data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving monthly transaction data: ' . $e->getMessage()
            ], 500);
        }
    }
}

