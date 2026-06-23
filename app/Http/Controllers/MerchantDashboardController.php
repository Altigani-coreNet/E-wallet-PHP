<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Terminal;
use App\Models\User;
use App\Models\Branch;
use App\Models\Transaction;
use App\Repositories\MerchantRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MerchantDashboardController extends Controller
{
    protected $merchantRepository;

    public function __construct(MerchantRepository $merchantRepository)
    {
        $this->merchantRepository = $merchantRepository;
    }

    public function index(Request $request)
    {
        // Get the authenticated user's merchant information
        $user = Auth::user();
        $merchant = null;
        
        // If user has a merchant relationship, get the merchant data
        if ($user && method_exists($user, 'merchant')) {
            $merchant = $user->merchant;
        }

        // Get filters from request
        $datetimeFrom = $request->get('datetime_from');
        $datetimeTo = $request->get('datetime_to');
        $transactionStatus = $request->get('transaction_status');
        
        
        // Get statistics for the dashboard using repository
        $statistics = $this->merchantRepository->getDashboardStatistics($merchant);
        
        // Get transaction statistics if merchant exists
        if ($merchant) {
            // Get individual transaction statistics from merchant repository with filters
            $dailyStats = $this->merchantRepository->getDailyTransactionStats($merchant->id, 30, $datetimeFrom, $datetimeTo, $transactionStatus);
            $weeklyStats = $this->merchantRepository->getWeeklyTransactionStats($merchant->id, 12, $datetimeFrom, $datetimeTo, $transactionStatus);
            $monthlyStats = $this->merchantRepository->getMonthlyTransactionStats($merchant->id, 12, $datetimeFrom, $datetimeTo, $transactionStatus);
            $transactionSummary = $this->merchantRepository->getTransactionSummary($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            
            $statistics = array_merge($statistics, [
                'dailyStats' => $dailyStats,
                'weeklyStats' => $weeklyStats,
                'monthlyStats' => $monthlyStats,
                'transactionSummary' => $transactionSummary,
            ]);
            
            // Get transaction chart data for the last 30 days with filters
            $transactionChartData = $this->merchantRepository->generateTransactionChartData($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            $statistics['transactionChartData'] = $transactionChartData;
            
            // Get today's summary for the header with filters
            $todaySummary = $this->merchantRepository->getTransactionSummary($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            $statistics['todayStats'] = [
                'count' => $todaySummary['today']['count'],
                'amount' => $todaySummary['today']['amount']
            ];
            
            // Add transaction summary for the dashboard view
            $statistics['transactionSummary'] = $todaySummary;

            // Add simple counts for dashboard cards with filters
            $statusStats = $this->merchantRepository->getTransactionStatisticsByStatus($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            $statistics['totalTransactions'] = $statusStats['totalTransactions'] ?? 0;
            $statistics['totalSaleTransactions'] = $statusStats['saleTransactions'] ?? 0;
            $statistics['totalFailedTransactions'] = $statusStats['failedTransactions'] ?? 0;
            
            // Get terminal data by status using repository
            $terminalData = $this->merchantRepository->getTerminalDataByStatus($merchant->id);
            $statistics = array_merge($statistics, $terminalData);

            // Latest 10 transactions with filters
            $latestTransactionsQuery = Transaction::with(['user'])
                ->where('merchant_id', $merchant->id);
            
            // Apply filters if provided
            if ($datetimeFrom) {
                $latestTransactionsQuery->where('created_at', '>=', $datetimeFrom);
            }
            if ($datetimeTo) {
                $latestTransactionsQuery->where('created_at', '<=', $datetimeTo);
            }
            if ($transactionStatus) {
                $latestTransactionsQuery->where('status', $transactionStatus);
            }
            
            $latestTransactions = $latestTransactionsQuery
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            $statistics['latestTransactions'] = $latestTransactions;
        }
        
        return view('merchant.dashboard', $statistics);
    }

    /**
     * Return latest transactions rows partial for the dashboard with a limit
     */
    public function latestTransactions(Request $request)
    {
        $user = Auth::user();
        if (!$user || !method_exists($user, 'merchant') || !$user->merchant) {
            abort(403);
        }

        $merchantId = $user->merchant->id;
        $limit = (int) $request->get('limit', 10);
        if (!in_array($limit, [10, 20, 50, 100], true)) {
            $limit = 10;
        }

        // Get filters from request
        $datetimeFrom = $request->get('datetime_from');
        $datetimeTo = $request->get('datetime_to');
        $transactionStatus = $request->get('transaction_status');

        $latestTransactionsQuery = Transaction::with(['user'])
            ->where('merchant_id', $merchantId);
        
        // Apply filters if provided
        if ($datetimeFrom) {
            $latestTransactionsQuery->where('created_at', '>=', $datetimeFrom);
        }
        if ($datetimeTo) {
            $latestTransactionsQuery->where('created_at', '<=', $datetimeTo);
        }
        if ($transactionStatus) {
            $latestTransactionsQuery->where('status', $transactionStatus);
        }
        
        $latestTransactions = $latestTransactionsQuery
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return view('merchant.partials.latest-transactions-rows', compact('latestTransactions'));
    }

    /**
     * API endpoint for getting dashboard statistics
     */
    public function getDashboardStatisticsApi(Request $request): JsonResponse
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
            
            // Add transaction chart data
            $transactionChartData = $this->merchantRepository->generateTransactionChartData($merchant->id);
            $dashboardData['transactionChartData'] = $transactionChartData;
            
            // Add today's summary
            $todaySummary = $this->merchantRepository->getTransactionSummary($merchant->id);
            $dashboardData['todayStats'] = [
                'count' => $todaySummary['today']['count'],
                'amount' => $todaySummary['today']['amount']
            ];
            
            // Add transaction summary
            $dashboardData['transactionSummary'] = $todaySummary;
            
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
     * Export dashboard data to Excel
     */
    public function exportDashboard(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user || !method_exists($user, 'merchant') || !$user->merchant) {
                abort(403, 'Unauthorized access to export dashboard data.');
            }

            $merchant = $user->merchant;
            
            // Get filters from request
            $datetimeFrom = $request->get('datetime_from');
            $datetimeTo = $request->get('datetime_to');
            $transactionStatus = $request->get('transaction_status');
            
            // Get all dashboard data
            $statistics = $this->merchantRepository->getDashboardStatistics($merchant);
            
            // Get transaction statistics with filters
            $dailyStats = $this->merchantRepository->getDailyTransactionStats($merchant->id, 30, $datetimeFrom, $datetimeTo, $transactionStatus);
            $weeklyStats = $this->merchantRepository->getWeeklyTransactionStats($merchant->id, 12, $datetimeFrom, $datetimeTo, $transactionStatus);
            $monthlyStats = $this->merchantRepository->getMonthlyTransactionStats($merchant->id, 12, $datetimeFrom, $datetimeTo, $transactionStatus);
            $transactionSummary = $this->merchantRepository->getTransactionSummary($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            
            // Get transaction chart data
            $transactionChartData = $this->merchantRepository->generateTransactionChartData($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            
            // Get status statistics
            $statusStats = $this->merchantRepository->getTransactionStatisticsByStatus($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            
            // Get terminal data
            $terminalData = $this->merchantRepository->getTerminalDataByStatus($merchant->id);
            
            // Get latest transactions
            $latestTransactionsQuery = Transaction::with(['user'])
                ->where('merchant_id', $merchant->id);
            
            if ($datetimeFrom) {
                $latestTransactionsQuery->where('created_at', '>=', $datetimeFrom);
            }
            if ($datetimeTo) {
                $latestTransactionsQuery->where('created_at', '<=', $datetimeTo);
            }
            if ($transactionStatus) {
                $latestTransactionsQuery->where('status', $transactionStatus);
            }
            
            $latestTransactions = $latestTransactionsQuery
                ->orderBy('created_at', 'desc')
                ->limit(100) // Export more transactions for Excel
                ->get();

            // Generate filename with date range
            $filename = 'dashboard_export_' . $merchant->name . '_' . date('Y-m-d_H-i-s') . '.csv';
            $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];

            $callback = function() use ($merchant, $statistics, $dailyStats, $weeklyStats, $monthlyStats, $transactionSummary, $transactionChartData, $statusStats, $terminalData, $latestTransactions, $datetimeFrom, $datetimeTo, $transactionStatus) {
                $file = fopen('php://output', 'w');
                
                // Add BOM for proper UTF-8 encoding in Excel
                fwrite($file, "\xEF\xBB\xBF");
                
                // Dashboard Overview Section
                fputcsv($file, ['DASHBOARD OVERVIEW']);
                fputcsv($file, ['Merchant Name', $merchant->name]);
                fputcsv($file, ['Export Date', now()->format('Y-m-d H:i:s')]);
                fputcsv($file, ['Date Range From', $datetimeFrom ?: 'All Time']);
                fputcsv($file, ['Date Range To', $datetimeTo ?: 'All Time']);
                fputcsv($file, ['Transaction Status Filter', $transactionStatus ?: 'All Statuses']);
                fputcsv($file, []); // Empty row
                
                // Transaction Statistics Section
                fputcsv($file, ['TRANSACTION STATISTICS']);
                fputcsv($file, ['Total Transactions', $statusStats['totalTransactions'] ?? 0]);
                fputcsv($file, ['Sale Transactions', $statusStats['saleTransactions'] ?? 0]);
                fputcsv($file, ['Failed Transactions', $statusStats['failedTransactions'] ?? 0]);
                fputcsv($file, ['Success Rate', $statusStats['totalTransactions'] > 0 ? round((($statusStats['saleTransactions'] ?? 0) / $statusStats['totalTransactions']) * 100, 2) . '%' : '0%']);
                fputcsv($file, []); // Empty row
                
                // Today's Summary Section
                fputcsv($file, ['TODAY\'S SUMMARY']);
                fputcsv($file, ['Today Transactions', $transactionSummary['today']['count'] ?? 0]);
                fputcsv($file, ['Today Amount', '$' . number_format($transactionSummary['today']['amount'] ?? 0, 2)]);
                fputcsv($file, []); // Empty row
                
                // Terminal Statistics Section
                fputcsv($file, ['TERMINAL STATISTICS']);
                fputcsv($file, ['Total Terminals', $terminalData['totalTerminals'] ?? 0]);
                fputcsv($file, ['Active Terminals', $terminalData['activeTerminals'] ?? 0]);
                fputcsv($file, ['Inactive Terminals', $terminalData['inactiveTerminals'] ?? 0]);
                fputcsv($file, []); // Empty row
                
                // Daily Statistics Section
                fputcsv($file, ['DAILY TRANSACTION STATISTICS (Last 30 Days)']);
                fputcsv($file, ['Date', 'Transaction Count', 'Total Amount']);
                if (isset($dailyStats['labels']) && is_array($dailyStats['labels'])) {
                    for ($i = 0; $i < count($dailyStats['labels']); $i++) {
                        fputcsv($file, [
                            $dailyStats['labels'][$i] ?? '',
                            $dailyStats['counts'][$i] ?? 0,
                            '$' . number_format($dailyStats['amounts'][$i] ?? 0, 2)
                        ]);
                    }
                }
                fputcsv($file, []); // Empty row
                
                // Weekly Statistics Section
                fputcsv($file, ['WEEKLY TRANSACTION STATISTICS (Last 12 Weeks)']);
                fputcsv($file, ['Week', 'Transaction Count', 'Total Amount']);
                if (isset($weeklyStats['labels']) && is_array($weeklyStats['labels'])) {
                    for ($i = 0; $i < count($weeklyStats['labels']); $i++) {
                        fputcsv($file, [
                            $weeklyStats['labels'][$i] ?? '',
                            $weeklyStats['counts'][$i] ?? 0,
                            '$' . number_format($weeklyStats['amounts'][$i] ?? 0, 2)
                        ]);
                    }
                }
                fputcsv($file, []); // Empty row
                
                // Monthly Statistics Section
                fputcsv($file, ['MONTHLY TRANSACTION STATISTICS (Last 12 Months)']);
                fputcsv($file, ['Month', 'Transaction Count', 'Total Amount']);
                if (isset($monthlyStats['labels']) && is_array($monthlyStats['labels'])) {
                    for ($i = 0; $i < count($monthlyStats['labels']); $i++) {
                        fputcsv($file, [
                            $monthlyStats['labels'][$i] ?? '',
                            $monthlyStats['counts'][$i] ?? 0,
                            '$' . number_format($monthlyStats['amounts'][$i] ?? 0, 2)
                        ]);
                    }
                }
                fputcsv($file, []); // Empty row
                
                // Transaction Chart Data Section
                fputcsv($file, ['TRANSACTION CHART DATA']);
                fputcsv($file, ['Date', 'Transaction Count', 'Amount']);
                if (isset($transactionChartData['labels']) && is_array($transactionChartData['labels'])) {
                    for ($i = 0; $i < count($transactionChartData['labels']); $i++) {
                        fputcsv($file, [
                            $transactionChartData['labels'][$i] ?? '',
                            $transactionChartData['counts'][$i] ?? 0,
                            '$' . number_format($transactionChartData['amounts'][$i] ?? 0, 2)
                        ]);
                    }
                }
                fputcsv($file, []); // Empty row
                
                // Latest Transactions Section
                fputcsv($file, ['LATEST TRANSACTIONS']);
                fputcsv($file, ['Transaction ID', 'Terminal', 'Amount', 'Status', 'Date', 'User', 'RRN', 'Auth Code']);
                foreach ($latestTransactions as $transaction) {
                    fputcsv($file, [
                        $transaction->transaction_id,
                        $transaction->terminal_id ?? 'N/A',
                        '$' . number_format($transaction->amount, 2),
                        $transaction->status,
                        $transaction->created_at->format('Y-m-d H:i:s'),
                        $transaction->user->name ?? 'N/A',
                        $transaction->rrn ?? 'N/A',
                        $transaction->auth_code ?? 'N/A'
                    ]);
                }
                
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export dashboard data: ' . $e->getMessage());
        }
    }

    /**
     * Get only daily transaction statistics (API)
     */
    public function getDailyStatisticsApi(Request $request): JsonResponse
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
            $days = $request->get('days', 30);
            
            $dailyStats = $this->merchantRepository->getDailyTransactionStats($merchantId, $days);
            
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
            
            if (!$user || !method_exists($user, 'merchant') || !$user->merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found or unauthorized'
                ], 401);
            }

            $merchantId = $user->merchant->id;
            $weeks = $request->get('weeks', 12);
            
            $weeklyStats = $this->merchantRepository->getWeeklyTransactionStats($merchantId, $weeks);
            
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
            
            if (!$user || !method_exists($user, 'merchant') || !$user->merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found or unauthorized'
                ], 401);
            }

            $merchantId = $user->merchant->id;
            $months = $request->get('months', 12);
            
            $monthlyStats = $this->merchantRepository->getMonthlyTransactionStats($merchantId, $months);
            
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
            
            if (!$user || !method_exists($user, 'merchant') || !$user->merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found or unauthorized'
                ], 401);
            }

            $merchantId = $user->merchant->id;
            
            // dd($merchantId);
            $summary = $this->merchantRepository->getTransactionSummary($merchantId);
            
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
            // Check if user is admin or has permission to view other merchants
            $user = Auth::user();
            
            if (!$user || !$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
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

    /**
     * Get comprehensive dashboard data for React component (API)
     * Returns all dashboard data in a single request with filter support
     */
    public function getDashboardDataApi(Request $request): JsonResponse
    {
        try {
            // Get the authenticated user's merchant information
            $user = Auth::guard('external')->user();
            // dd($user);
            $merchant = null;
            
            // If user has a merchant relationship, get the merchant data
            if ($user && $user->merchant) {
                $merchant = $user->merchant;
            }

            // dd($merchant);
            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found'
                ], 404);
            }

            // Get filters from request
            $datetimeFrom = $request->get('datetime_from');
            $datetimeTo = $request->get('datetime_to');
            $transactionStatus = $request->get('transaction_status');
            $limit = $request->get('limit', 10);
            
            // Get statistics for the dashboard using repository
            $statistics = $this->merchantRepository->getDashboardStatistics($merchant->id);
            
            // dd($statistics);
            // Get transaction statistics if merchant exists
            // Get individual transaction statistics from merchant repository with filters
            $dailyStats = $this->merchantRepository->getDailyTransactionStats($merchant->id, 30, $datetimeFrom, $datetimeTo, $transactionStatus);
            $weeklyStats = $this->merchantRepository->getWeeklyTransactionStats($merchant->id, 12, $datetimeFrom, $datetimeTo, $transactionStatus);
            $monthlyStats = $this->merchantRepository->getMonthlyTransactionStats($merchant->id, 12, $datetimeFrom, $datetimeTo, $transactionStatus);
            $transactionSummary = $this->merchantRepository->getTransactionSummary($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            
            // Get transaction chart data for the last 30 days with filters
            $transactionChartData = $this->merchantRepository->generateTransactionChartData($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            
            // Get today's summary for the header with filters
            $todaySummary = $this->merchantRepository->getTransactionSummary($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            $todayStats = [
                'count' => $todaySummary['today']['count'] ?? 0,
                'amount' => $todaySummary['today']['amount'] ?? 0
            ];

            // Add simple counts for dashboard cards with filters
            $statusStats = $this->merchantRepository->getTransactionStatisticsByStatus($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            
            // Get terminal data by status using repository
            $terminalData = $this->merchantRepository->getTerminalDataByStatus($merchant->id);

            // Latest transactions with filters
            $latestTransactionsQuery = Transaction::with(['user'])
                ->where('merchant_id', $merchant->id);
            
            // Apply filters if provided
            if ($datetimeFrom) {
                $latestTransactionsQuery->where('created_at', '>=', $datetimeFrom);
            }
            if ($datetimeTo) {
                $latestTransactionsQuery->where('created_at', '<=', $datetimeTo);
            }
            if ($transactionStatus) {
                $latestTransactionsQuery->where('status', $transactionStatus);
            }
            
            $latestTransactions = $latestTransactionsQuery
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            // Build comprehensive response
            $dashboardData = [
                'totalTransactions' => $statusStats['totalTransactions'] ?? 0,
                'totalSaleTransactions' => $statusStats['saleTransactions'] ?? 0,
                'totalFailedTransactions' => $statusStats['failedTransactions'] ?? 0,
                'todayStats' => $todayStats,
                'dailyStats' => $dailyStats,
                'weeklyStats' => $weeklyStats,
                'monthlyStats' => $monthlyStats,
                'transactionSummary' => $transactionSummary,
                'transactionChartData' => $transactionChartData,
                'terminalData' => $terminalData,
                'latestTransactions' => $latestTransactions,
                'appliedFilters' => [
                    'datetime_from' => $datetimeFrom,
                    'datetime_to' => $datetimeTo,
                    'transaction_status' => $transactionStatus,
                    'limit' => $limit
                ]
            ];
            
            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => $dashboardData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard data: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
} 