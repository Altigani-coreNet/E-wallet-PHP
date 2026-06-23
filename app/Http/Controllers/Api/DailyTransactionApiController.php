<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\MerchantDashboardRepository;
use App\Repositories\MerchantRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DailyTransactionApiController extends Controller
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
            $user = Auth::user();
            
            if (!$user || !method_exists($user, 'merchant') || !$user->merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found or unauthorized'
                ], 401);
            }

            $merchantId = $user->merchant->id;
            $days = $request->get('days', 20); // Default to 30 days, can be customized
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $dailyData = $this->merchantRepository->generateDailyTransactionChartData($merchantId, $days);
            
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

    /**
     * Get daily transaction data for a specific merchant (admin only)
     */
    public function show(Request $request, int $merchantId): JsonResponse
    {
        try {
            // Check if user is admin or has permission to view other merchants
            $user = Auth::user();
            
            if (!$user || !$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $days = $request->get('days', 30);
            $dailyData = $this->merchantDashboardRepository->generateDailyTransactionChartData($merchantId, $days);
            
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
