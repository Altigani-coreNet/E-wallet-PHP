<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\MerchantDashboardRepository;
use App\Repositories\MerchantRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class WeeklyTransactionApiController extends Controller
{

    public function __construct(public MerchantRepository $merchantRepository)
    {
        // $this->merchantRepository = $merchantRepository;
    }

    /**
     * Get weekly transaction data for the authenticated merchant
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
            $weeks = $request->get('weeks', 12); // Default to 12 weeks, can be customized
            
            $weeklyData = $this->merchantRepository->generateWeeklyTransactionChartData($merchantId, $weeks);
            
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

    /**
     * Get weekly transaction data for a specific merchant (admin only)
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

            $weeks = $request->get('weeks', 12);
            $weeklyData = $this->merchantRepository->generateWeeklyTransactionChartData($merchantId, $weeks);
            
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
