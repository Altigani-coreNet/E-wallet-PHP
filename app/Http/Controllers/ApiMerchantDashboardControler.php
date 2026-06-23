<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Repositories\MerchantRepository;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ApiMerchantDashboardControler extends Controller
{
    public function __construct(public MerchantRepository $merchantRepository)
    {
    }
    
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $merchant = null;
            
            // If user has a merchant relationship, get the merchant data
            if ($user && method_exists($user, 'merchant')) {
                $merchant = $user->merchant;
            }
            
            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found',
                    'data' => null
                ], 404);
            }
            
            // Get comprehensive dashboard data from repository
            $dashboardData = $this->merchantRepository->getDashboardDataForApi($merchant->id);
            
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
    public function getDailyStatisticsApi(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $merchant = $user->merchant;
            
            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found',
                    'data' => null
                ], 404);
            }

            $date = $request->get('date', Carbon::today()->format('Y-m-d'));
            $statistics = $this->merchantRepository->getDailyStatistics($merchant->id, $date);
            
            $transactionsCharts = [
                'all' => $this->merchantRepository->generateHourlyTransactionChartWithStatus($merchant->id),
                'voided' => $this->merchantRepository->generateHourlyTransactionChartWithStatus($merchant->id, 'voided'),
                'refunded' => $this->merchantRepository->generateHourlyTransactionChartWithStatus($merchant->id, 'refunded'),
                'failed' => $this->merchantRepository->generateHourlyTransactionChartWithStatus($merchant->id, 'failed'),
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
    public function getWeeklyStatisticsApi(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
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
            
            $statistics = $this->merchantRepository->getWeeklyStatistics($merchant->id, $weekStart, $weekEnd);
            
            $transactionsCharts = [
                'all' => $this->merchantRepository->generateWeeklyTransactionChartWithStatus($merchant->id, 12),
                'voided' => $this->merchantRepository->generateWeeklyTransactionChartWithStatus($merchant->id, 12, 'voided'),
                'failed' => $this->merchantRepository->generateWeeklyTransactionChartWithStatus($merchant->id, 12, 'failed'),
                'refunded' => $this->merchantRepository->generateWeeklyTransactionChartWithStatus($merchant->id, 12, 'refunded'),
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
    public function getMonthlyStatisticsApi(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $merchant = $user->merchant;
            
            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found',
                    'data' => null
                ], 404);
            }

            $month = $request->get('month', Carbon::now()->format('Y-m'));
            $statistics = $this->merchantRepository->getMonthlyStatistics($merchant->id, $month);
            
            $transactionsCharts = [
                'all' => $this->merchantRepository->generateMonthlyTransactionChartWithStatus($merchant->id),
                'voided' => $this->merchantRepository->generateMonthlyTransactionChartWithStatus($merchant->id, 'voided'),
                'refunded' => $this->merchantRepository->generateMonthlyTransactionChartWithStatus($merchant->id, 'refunded'),
                  'failed' => $this->merchantRepository->generateMonthlyTransactionChartWithStatus($merchant->id, 'failed'),
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
    public function getTransactionSummaryApi(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
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
            
            $summary = $this->merchantRepository->getComprehensiveTransactionSummary($merchant->id, $startDate, $endDate);
            
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
    public function getCustomStatisticsApi(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
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

            // Get custom statistics from repository
            $statistics = $this->merchantRepository->getCustomStatistics($merchant->id, $startDateTime, $endDateTime);
            $statistics['line_charts'] = [
                'all' => $this->merchantRepository->generateCustomTransactionChartWithStatus($merchant->id, $startDateTime, $endDateTime),
                'voided' => $this->merchantRepository->generateCustomTransactionChartWithStatus($merchant->id, $startDateTime, $endDateTime, 'voided'),
                'refunded' => $this->merchantRepository->generateCustomTransactionChartWithStatus($merchant->id, $startDateTime, $endDateTime, 'refunded'),
                'failed' => $this->merchantRepository->generateCustomTransactionChartWithStatus($merchant->id, $startDateTime, $endDateTime, 'failed'),
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
