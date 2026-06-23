<?php

namespace App\Http\Controllers\Api\V2\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Repositories\MerchantRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MerchantDashboardV2Controller extends Controller
{
    protected $merchantRepository;

    public function __construct(MerchantRepository $merchantRepository)
    {
        $this->merchantRepository = $merchantRepository;
    }

    /**
     * Get comprehensive dashboard data for React component (API)
     * Returns all dashboard data in a single request with filter support
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        try {
            // Get the authenticated user's merchant information
            $user = Auth::guard('external')->user();
            $merchant = null;
            
            // If user has a merchant relationship, get the merchant data
            if ($user && $user->merchant) {
                $merchant = $user->merchant;
            }

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
            
            // Get statistics for the dashboard using repository V2 (no terminals/users)
            $statistics = $this->merchantRepository->getDashboardStatisticsV2($merchant->id);
            
            // Get individual transaction statistics from merchant repository with filters
            $dailyStats = $this->merchantRepository->getDailyTransactionStatsV2($merchant->id, 30, $datetimeFrom, $datetimeTo, $transactionStatus);
            $weeklyStats = $this->merchantRepository->getWeeklyTransactionStatsV2($merchant->id, 12, $datetimeFrom, $datetimeTo, $transactionStatus);
            $monthlyStats = $this->merchantRepository->getMonthlyTransactionStatsV2($merchant->id, 12, $datetimeFrom, $datetimeTo, $transactionStatus);
            $transactionSummary = $this->merchantRepository->getTransactionSummaryV2($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            
            // Get transaction chart data for the last 30 days with filters
            $transactionChartData = $this->merchantRepository->generateTransactionChartDataV2($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            
            // Get today's summary for the header with filters
            $todaySummary = $this->merchantRepository->getTransactionSummaryV2($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            $todayStats = [
                'count' => $todaySummary['today']['count'] ?? 0,
                'amount' => $todaySummary['today']['amount'] ?? 0
            ];

            // Add simple counts for dashboard cards with filters
            $statusStats = $this->merchantRepository->getTransactionStatisticsByStatusV2($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            
            // Get terminal data by status using repository V2 (returns 0s)
            $terminalData = $this->merchantRepository->getTerminalDataByStatusV2($merchant->id);

            // Latest transactions with filters (no user relation - moved to AuthService)
            $latestTransactionsQuery = Transaction::query()
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
                ->with(['merchant', 'partner', 'country', 'terminal'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(fn (Transaction $transaction) => $this->formatLatestTransaction($transaction))
                ->values();

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

    /**
     * Export dashboard data to CSV
     */
    public function export(Request $request)
    {
        try {
            $user = Auth::guard('external')->user();
            
            if (!$user || !$user->merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to export dashboard data.'
                ], 403);
            }

            $merchant = $user->merchant;
            
            // Get filters from request
            $datetimeFrom = $request->get('datetime_from');
            $datetimeTo = $request->get('datetime_to');
            $transactionStatus = $request->get('transaction_status');
            
            // Get all dashboard data using V2 methods
            $statistics = $this->merchantRepository->getDashboardStatisticsV2($merchant->id);
            
            // Get transaction statistics with filters using V2 methods
            $dailyStats = $this->merchantRepository->getDailyTransactionStatsV2($merchant->id, 30, $datetimeFrom, $datetimeTo, $transactionStatus);
            $weeklyStats = $this->merchantRepository->getWeeklyTransactionStatsV2($merchant->id, 12, $datetimeFrom, $datetimeTo, $transactionStatus);
            $monthlyStats = $this->merchantRepository->getMonthlyTransactionStatsV2($merchant->id, 12, $datetimeFrom, $datetimeTo, $transactionStatus);
            $transactionSummary = $this->merchantRepository->getTransactionSummaryV2($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            
            // Get transaction chart data
            $transactionChartData = $this->merchantRepository->generateTransactionChartDataV2($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            
            // Get status statistics
            $statusStats = $this->merchantRepository->getTransactionStatisticsByStatusV2($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            
            // Get terminal data (V2 returns 0s but we'll include it for consistency)
            $terminalData = $this->merchantRepository->getTerminalDataByStatusV2($merchant->id);
            
            // Get latest transactions (no user relation in V2)
            $latestTransactionsQuery = Transaction::query()
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
            $filename = 'dashboard_export_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $merchant->name) . '_' . date('Y-m-d_H-i-s') . '.csv';

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
                fputcsv($file, ['Transaction ID', 'Terminal', 'Amount', 'Status', 'Date', 'RRN', 'Auth Code']);
                foreach ($latestTransactions as $transaction) {
                    fputcsv($file, [
                        $transaction->transaction_id,
                        $transaction->terminal_id ?? 'N/A',
                        '$' . number_format($transaction->amount, 2),
                        $transaction->status,
                        $transaction->created_at->format('Y-m-d H:i:s'),
                        $transaction->rrn ?? 'N/A',
                        $transaction->auth_code ?? 'N/A'
                    ]);
                }
                
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics only
     * Returns statistics data for the dashboard cards
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('external')->user();
            $merchant = null;
            
            if ($user && $user->merchant) {
                $merchant = $user->merchant;
            }

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

            // Get statistics for the dashboard using repository V2
            $statistics = $this->merchantRepository->getDashboardStatisticsV2($merchant->id);
            
            // Add simple counts for dashboard cards with filters
            $statusStats = $this->merchantRepository->getTransactionStatisticsByStatusV2($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            
            // Get terminal data by status using repository V2
            $terminalData = $this->merchantRepository->getTerminalDataByStatusV2($merchant->id);

            // Build statistics response
            $statisticsData = [
                'totalTransactions' => $statusStats['totalTransactions'] ?? 0,
                'totalSaleTransactions' => $statusStats['saleTransactions'] ?? 0,
                'totalFailedTransactions' => $statusStats['failedTransactions'] ?? 0,
                'terminalData' => $terminalData,
            ];
            
            return response()->json([
                'success' => true,
                'message' => 'Dashboard statistics retrieved successfully',
                'data' => $statisticsData
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
     * Get dashboard charts data only
     * Returns chart data for transaction analytics
     */
    public function getCharts(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('external')->user();
            $merchant = null;
            
            if ($user && $user->merchant) {
                $merchant = $user->merchant;
            }

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

            // Get transaction chart data with filters (returns daily, weekly, monthly)
            $transactionChartData = $this->merchantRepository->generateTransactionChartDataV2($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            
            // Get today's summary for the header with filters
            $todaySummary = $this->merchantRepository->getTransactionSummaryV2($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);
            $todayStats = [
                'count' => $todaySummary['today']['count'] ?? 0,
                'amount' => $todaySummary['today']['amount'] ?? 0
            ];

            // Get individual transaction statistics with filters
            $dailyStats = $this->merchantRepository->getDailyTransactionStatsV2($merchant->id, 30, $datetimeFrom, $datetimeTo, $transactionStatus);
            $weeklyStats = $this->merchantRepository->getWeeklyTransactionStatsV2($merchant->id, 12, $datetimeFrom, $datetimeTo, $transactionStatus);
            $monthlyStats = $this->merchantRepository->getMonthlyTransactionStatsV2($merchant->id, 12, $datetimeFrom, $datetimeTo, $transactionStatus);
            $transactionSummary = $this->merchantRepository->getTransactionSummaryV2($merchant->id, $datetimeFrom, $datetimeTo, $transactionStatus);

            // Extract hourly chart data as default (frontend expects labels and series at root level)
            $hourlyChartData = $transactionChartData['hourly'] ?? [
                'labels' => [],
                'series' => []
            ];

            // Build charts response - return hourly chart data in the format frontend expects
            $chartsData = [
                'labels' => $hourlyChartData['labels'] ?? [],
                'series' => $hourlyChartData['series'] ?? [],
                'todayStats' => $todayStats,
                'dailyStats' => $dailyStats,
                'weeklyStats' => $weeklyStats,
                'monthlyStats' => $monthlyStats,
                'transactionSummary' => $transactionSummary,
                'transactionChartData' => $transactionChartData, // Keep full structure for future use
            ];
            
            return response()->json([
                'success' => true,
                'message' => 'Dashboard charts data retrieved successfully',
                'data' => $chartsData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard charts: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get latest transactions only
     * Returns latest transactions list
     */
    public function getLatestTransactions(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('external')->user();
            $merchant = null;
            
            if ($user && $user->merchant) {
                $merchant = $user->merchant;
            }

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

            // Latest transactions with filters (no user relation - moved to AuthService)
            $latestTransactionsQuery = Transaction::query()
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
                ->with(['merchant', 'partner', 'country'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(fn (Transaction $transaction) => $this->formatLatestTransaction($transaction))
                ->values();

            // Build latest transactions response
            $latestTransactionsData = [
                'latestTransactions' => $latestTransactions,
            ];
            
            return response()->json([
                'success' => true,
                'message' => 'Latest transactions retrieved successfully',
                'data' => $latestTransactionsData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving latest transactions: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    private function formatLatestTransaction(Transaction $transaction): array
    {
        $merchantName = $transaction->merchant?->business_name
            ?? $transaction->merchant?->name
            ?? $transaction->merchant?->merchant_name
            ?? null;

        $partnerName = $transaction->partner?->name
            ?? $transaction->partner_name
            ?? null;

        $countryName = null;
        if ($transaction->country) {
            if (is_array($transaction->country->name)) {
                $countryName = $transaction->country->name['en'] ?? reset($transaction->country->name) ?: null;
            } else {
                $countryName = $transaction->country->name ?? $transaction->country->short_name ?? null;
            }
        }

        return [
            'id' => $transaction->id,
            'transaction_id' => $transaction->transaction_id,
            'amount' => (float) ($transaction->amount ?? 0),
            'status' => $transaction->status,
            'created_at' => optional($transaction->created_at)->toDateTimeString(),
            'merchant_id' => $transaction->merchant_id,
            'merchant_name' => $merchantName,
            'partner_id' => $transaction->partner_id,
            'partner_name' => $partnerName,
            'country_id' => $transaction->country_id,
            'country_name' => $countryName,
            'terminal_id' => $transaction->terminal_id,
            'terminal_name' => $transaction->terminal?->name
                ?? $transaction->terminal?->terminal_name
                ?? null,
            'terminal_serial_number' => $transaction->terminal?->terminal_id
                ?? $transaction->terminal?->serial_number
                ?? null,
            'rrn' => $transaction->rrn,
            'auth_code' => $transaction->auth_code,
            'invoice_no' => $transaction->invoice_no,
            'terminal' => $transaction->terminal ? [
                'id' => $transaction->terminal->id ?? $transaction->terminal_id,
                'name' => $transaction->terminal->name ?? $transaction->terminal->terminal_name ?? null,
                'serial_number' => $transaction->terminal->terminal_id ?? $transaction->terminal->serial_number ?? null,
            ] : null,
            'merchant' => $transaction->merchant ? [
                'id' => $transaction->merchant->id,
                'business_name' => $merchantName,
            ] : null,
        ];
    }
}

