<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Terminal;
use App\Models\User;
use App\Models\Branch;
use App\Models\Transaction;
use App\Models\Merchant;
use App\Repositories\MerchantRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    protected $merchantRepository;

    public function __construct(MerchantRepository $merchantRepository)
    {
        $this->merchantRepository = $merchantRepository;
    }

    public function index()
    {
        // Get the authenticated admin user
        $user = Auth::guard('admin')->user();
        // Get comprehensive statistics for the admin dashboard
        $statistics = $this->merchantRepository->getAdminDashboardStatistics();
        
        // Get transaction statistics
        $transactionStats = $this->merchantRepository->getAdminTransactionStatistics();
        $statistics = array_merge($statistics, $transactionStats);
        
        // Get transaction chart data for the last 30 days
        $transactionChartData = $this->merchantRepository->getAdminTransactionChartData();
        $statistics['transactionChartData'] = $transactionChartData;
        
        // Get today's summary for the header
        // $todaySummary = $this->merchantRepository->getAdminTransactionSummary();
        // $statistics['todayStats'] = [
        //     'count' => $todaySummary['today']['count'] ?? 0,
        //     'amount' => $todaySummary['today']['amount'] ?? 0
        // ];
        
        // Add transaction summary for the dashboard view
        // $statistics['transactionSummary'] = $todaySummary;
        
        // Get terminal data by status
        $terminalData = $this->merchantRepository->getAdminTerminalDataByStatus();
        $statistics = array_merge($statistics, $terminalData);
        
        // Get daily, weekly, and monthly statistics
        $periodStats = $this->merchantRepository->getAdminPeriodStatistics();
        $statistics = array_merge($statistics, $periodStats);
        
        // Get latest transactions by status
        $latestTransactions = $this->merchantRepository->getAdminLatestTransactionsByStatus();
        $statistics = array_merge($statistics, $latestTransactions);
        
        // Ensure all required variables are available for the view
        $statistics['dailyStats'] = $periodStats['dailyStats'] ?? [];
        $statistics['weeklyStats'] = $periodStats['weeklyStats'] ?? [];
        $statistics['monthlyStats'] = $periodStats['monthlyStats'] ?? [];
        
        return view('admin.dashboard', $statistics);
    }
    
    // Remove all private methods since they're now in the repository
    // private function getDashboardStatistics() - REMOVED
    // private function getTransactionStatistics() - REMOVED
    // private function getLatestTransactionsByStatus() - REMOVED
    // private function getTransactionSummary() - REMOVED
    // private function getPeriodStatistics() - REMOVED
    // private function getDailyTransactionStats() - REMOVED
    // private function getWeeklyTransactionStats() - REMOVED
    // private function getMonthlyTransactionStats() - REMOVED
    // private function getTransactionChartData() - REMOVED
    // private function getTerminalDataByStatus() - REMOVED

    // ==================== API METHODS ====================

    /**
     * Get comprehensive transaction statistics for admin (API)
     */
    public function getTransactionStatisticsApi(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }

            // Get customizable parameters
            $dailyDays = $request->get('daily_days', 30);
            $weeklyWeeks = $request->get('weekly_weeks', 12);
            $monthlyMonths = $request->get('monthly_months', 12);
            
            $statistics = [
                'dailyStats' => $this->merchantRepository->getAdminDailyTransactionStats($dailyDays),
                'weeklyStats' => $this->merchantRepository->getAdminWeeklyTransactionStats($weeklyWeeks),
                'monthlyStats' => $this->merchantRepository->getAdminMonthlyTransactionStats($monthlyMonths),
                'transactionSummary' => $this->merchantRepository->getAdminTransactionSummary(),
            ];
            
            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Transaction statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving transaction statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get only daily transaction statistics (API)
     */
    public function getDailyStatisticsApi(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }

            $days = $request->get('days', 30);
            $dailyStats = $this->merchantRepository->getAdminDailyTransactionStats($days);
            
            return response()->json([
                'success' => true,
                'data' => $dailyStats,
                'message' => 'Daily transaction statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving daily transaction statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get only weekly transaction statistics (API)
     */
    public function getWeeklyStatisticsApi(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }

            $weeks = $request->get('weeks', 12);
            $weeklyStats = $this->merchantRepository->getAdminWeeklyTransactionStats($weeks);
            
            return response()->json([
                'success' => true,
                'data' => $weeklyStats,
                'message' => 'Weekly transaction statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving weekly transaction statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get only monthly transaction statistics (API)
     */
    public function getMonthlyStatisticsApi(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }

            $months = $request->get('months', 12);
            $monthlyStats = $this->merchantRepository->getAdminMonthlyTransactionStats($months);
            
            return response()->json([
                'success' => true,
                'data' => $monthlyStats,
                'message' => 'Monthly transaction statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving monthly transaction statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get only transaction summary (API)
     */
    public function getTransactionSummaryApi(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }

            $summary = $this->merchantRepository->getAdminTransactionSummary();
            
            return response()->json([
                'success' => true,
                'data' => $summary,
                'message' => 'Transaction summary retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving transaction summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction statistics for a specific merchant (admin only - API)
     */
    public function getMerchantStatisticsApi(Request $request, int $merchantId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }

            // Get customizable parameters
            $dailyDays = $request->get('daily_days', 30);
            $weeklyWeeks = $request->get('weekly_weeks', 12);
            $monthlyMonths = $request->get('monthly_months', 12);
            
            $statistics = [
                'dailyStats' => $this->merchantRepository->getDailyTransactionStats($merchantId, $dailyDays),
                'weeklyStats' => $this->merchantRepository->getWeeklyTransactionStats($merchantId, $weeklyWeeks),
                'monthlyStats' => $this->merchantRepository->getMonthlyTransactionStats($merchantId, $monthlyMonths),
                'transactionSummary' => $this->merchantRepository->getTransactionSummary($merchantId),
            ];
            
            return response()->json([
                'success' => true,
                'data' => $statistics,
                'message' => 'Transaction statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving transaction statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
