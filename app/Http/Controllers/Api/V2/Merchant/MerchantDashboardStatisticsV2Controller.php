<?php

namespace App\Http\Controllers\Api\V2\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Repositories\MerchantRepository;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MerchantDashboardStatisticsV2Controller extends Controller
{
    public function __construct(public MerchantRepository $merchantRepository)
    {
    }
    
    /**
     * Get comprehensive dashboard statistics
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('external')->user();
            $merchant = null;
            
            // If user has a merchant relationship, get the merchant data
            if ($user && $user->merchant) {
                $merchant = $user->merchant;
            }
            
            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found',
                    'data' => null
                ], 404);
            }
            
            // Get comprehensive dashboard data from repository V2 (no terminals/users)
            $dashboardData = $this->merchantRepository->getDashboardDataForApiV2($merchant->id);
            
            return response()->json([
                'success' => true,
                'message' => 'Dashboard statistics retrieved successfully',
                'data' => $dashboardData
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard statistics: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get daily statistics for the merchant
     */
    public function getDailyStatistics(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('external')->user();
            $merchant = $user->merchant;
            
            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found',
                    'data' => null
                ], 404);
            }

            $date = $request->get('date', Carbon::today()->format('Y-m-d'));
            $statistics = $this->merchantRepository->getDailyStatisticsV2($merchant->id, $date);
            
            $transactionsCharts = [
                'all' => $this->merchantRepository->generateHourlyTransactionChartWithStatusV2($merchant->id),
                'voided' => $this->merchantRepository->generateHourlyTransactionChartWithStatusV2($merchant->id, 'voided'),
                'refunded' => $this->merchantRepository->generateHourlyTransactionChartWithStatusV2($merchant->id, 'refunded'),
                'failed' => $this->merchantRepository->generateHourlyTransactionChartWithStatusV2($merchant->id, 'failed'),
            ];

            $statistics['line_charts'] = $transactionsCharts;

            return response()->json([
                'success' => true,
                'message' => 'Daily statistics retrieved successfully',
                'data' => $statistics
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving daily statistics: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get weekly statistics for the merchant
     */
    public function getWeeklyStatistics(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('external')->user();
            $merchant = $user->merchant;
            
            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found',
                    'data' => null
                ], 404);
            }

            $weekStart = $request->get('week_start', Carbon::now()->startOfWeek()->format('Y-m-d'));
            $weekEnd = $request->get('week_end', Carbon::now()->endOfWeek()->format('Y-m-d'));
            
            $statistics = $this->merchantRepository->getWeeklyStatisticsV2($merchant->id, $weekStart, $weekEnd);
            
            $transactionsCharts = [
                'all' => $this->merchantRepository->generateWeeklyTransactionChartWithStatusV2($merchant->id, 12),
                'voided' => $this->merchantRepository->generateWeeklyTransactionChartWithStatusV2($merchant->id, 12, 'voided'),
                'failed' => $this->merchantRepository->generateWeeklyTransactionChartWithStatusV2($merchant->id, 12, 'failed'),
                'refunded' => $this->merchantRepository->generateWeeklyTransactionChartWithStatusV2($merchant->id, 12, 'refunded'),
            ];

            $statistics['line_charts'] = $transactionsCharts;

            return response()->json([
                'success' => true,
                'message' => 'Weekly statistics retrieved successfully',
                'data' => $statistics
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving weekly statistics: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get monthly statistics for the merchant
     */
    public function getMonthlyStatistics(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('external')->user();
            $merchant = $user->merchant;
            
            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found',
                    'data' => null
                ], 404);
            }

            $month = $request->get('month', Carbon::now()->format('Y-m'));
            $statistics = $this->merchantRepository->getMonthlyStatisticsV2($merchant->id, $month);
            
            $transactionsCharts = [
                'all' => $this->merchantRepository->generateMonthlyTransactionChartWithStatusV2($merchant->id),
                'voided' => $this->merchantRepository->generateMonthlyTransactionChartWithStatusV2($merchant->id, 'voided'),
                'refunded' => $this->merchantRepository->generateMonthlyTransactionChartWithStatusV2($merchant->id, 'refunded'),
                'failed' => $this->merchantRepository->generateMonthlyTransactionChartWithStatusV2($merchant->id, 'failed'),
            ];

            $statistics['line_charts'] = $transactionsCharts;
            
            return response()->json([
                'success' => true,
                'message' => 'Monthly statistics retrieved successfully',
                'data' => $statistics
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving monthly statistics: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get comprehensive transaction summary for the merchant
     */
    public function getTransactionSummary(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('external')->user();
            $merchant = $user->merchant;
            
            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found',
                    'data' => null
                ], 404);
            }

            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d H:i:s'));
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->endOfDay()->format('Y-m-d H:i:s'));
            
            $summary = $this->merchantRepository->getComprehensiveTransactionSummaryV2($merchant->id, $startDate, $endDate);
            
            return response()->json([
                'success' => true,
                'message' => 'Transaction summary retrieved successfully',
                'data' => $summary
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving transaction summary: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get custom statistics for the merchant with specific date and time range
     */
    public function getCustomStatistics(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('external')->user();
            $merchant = $user->merchant;
            
            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found',
                    'data' => null
                ], 404);
            }

            // Validate required parameters with datetime format
            $request->validate([
                'start_date' => 'required|date_format:Y-m-d H:i:s',
                'end_date' => 'required|date_format:Y-m-d H:i:s|after_or_equal:start_date'
            ]);

            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            // Parse datetime strings directly
            $startDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $startDate);
            $endDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $endDate);

            // Get custom statistics from repository V2
            $statistics = $this->merchantRepository->getCustomStatisticsV2($merchant->id, $startDateTime, $endDateTime);
            $statistics['line_charts'] = [
                'all' => $this->merchantRepository->generateCustomTransactionChartWithStatusV2($merchant->id, $startDateTime, $endDateTime),
                'voided' => $this->merchantRepository->generateCustomTransactionChartWithStatusV2($merchant->id, $startDateTime, $endDateTime, 'voided'),
                'refunded' => $this->merchantRepository->generateCustomTransactionChartWithStatusV2($merchant->id, $startDateTime, $endDateTime, 'refunded'),
                'failed' => $this->merchantRepository->generateCustomTransactionChartWithStatusV2($merchant->id, $startDateTime, $endDateTime, 'failed'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Custom statistics retrieved successfully',
                'data' => $statistics
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving custom statistics: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}

