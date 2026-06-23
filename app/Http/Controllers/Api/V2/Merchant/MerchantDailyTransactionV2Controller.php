<?php

namespace App\Http\Controllers\Api\V2\Merchant;

use App\Http\Controllers\Controller;
use App\Repositories\MerchantRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MerchantDailyTransactionV2Controller extends Controller
{
    protected $merchantRepository;

    public function __construct(MerchantRepository $merchantRepository)
    {
        $this->merchantRepository = $merchantRepository;
    }

    /**
     * Get daily transaction data for the authenticated merchant
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
            $days = $request->get('days', 20); // Default to 20 days, can be customized
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $dailyData = $this->merchantRepository->generateDailyTransactionChartDataV2($merchantId, $days);
            
            return response()->json([
                'success' => true,
                'data' => $dailyData,
                'message' => 'Daily transaction data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving daily transaction data: ' . $e->getMessage()
            ], 500);
        }
    }
}

