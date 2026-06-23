<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Batch;
use App\Models\Terminal;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PosDashboardController extends Controller
{
    /**
     * Get complete POS dashboard data with all statistics
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }

            // Get date range parameters
            $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
            $merchantId = $request->get('merchant_id');
            $terminalId = $request->get('terminal_id');

            // Build base query
            $query = Transaction::query();
            
            // Apply filters
            if ($merchantId) {
                $query->where('merchant_id', $merchantId);
            }
            if ($terminalId) {
                $query->where('terminal_id', $terminalId);
            }
            
            // Apply date range
            $query->whereBetween('transaction_datetime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

            // Get all dashboard data
            $dashboardData = [
                'summary_cards' => $this->getSummaryCards($query),
                'transaction_status_breakdown' => $this->getTransactionStatusBreakdown($query),
                'payment_method_breakdown' => $this->getPaymentMethodBreakdown($query),
                'terminal_performance' => $this->getTerminalPerformance($query),
                'merchant_performance' => $this->getMerchantPerformance($query),
                'batch_information' => $this->getBatchInformation($query),
                'recent_transactions' => $this->getRecentTransactions($query),
                'pending_transactions' => $this->getPendingTransactions($query),
                'hourly_activity' => $this->getHourlyActivity($query),
                'daily_trends' => $this->getDailyTrends($query),
                'daily_with_hours' => $this->getDailyWithHours($query),
                'weekly_with_weekdays' => $this->getWeeklyWithWeekdays($query),
                'monthly_with_full_month' => $this->getMonthlyWithFullMonth($query),
                'top_terminals' => $this->getTopTerminals($query),
                'top_merchants' => $this->getTopMerchants($query),
                'refund_void_summary' => $this->getRefundVoidSummary($query),
                'currency_breakdown' => $this->getCurrencyBreakdown($query),
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'merchant_id' => $merchantId,
                    'terminal_id' => $terminalId
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $dashboardData,
                'message' => 'POS Dashboard data retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary cards for dashboard
     */
    private function getSummaryCards($query)
    {
        $clone = clone $query;
        
        $stats = $clone->selectRaw('
            COUNT(*) as total_transactions,
            SUM(amount) as total_amount,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as approved_amount,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = "pending" THEN amount ELSE 0 END) as pending_amount,
            SUM(CASE WHEN status = "declined" THEN 1 ELSE 0 END) as declined_count,
            SUM(CASE WHEN status = "declined" THEN amount ELSE 0 END) as declined_amount,
            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count,
            SUM(CASE WHEN status = "failed" THEN amount ELSE 0 END) as failed_amount,
            SUM(CASE WHEN status = "refunded" THEN 1 ELSE 0 END) as refunded_count,
            SUM(CASE WHEN status = "refunded" THEN amount ELSE 0 END) as refunded_amount,
            SUM(CASE WHEN status = "voided" THEN 1 ELSE 0 END) as voided_count,
            SUM(CASE WHEN status = "voided" THEN amount ELSE 0 END) as voided_amount,
            AVG(amount) as average_amount,
            MIN(amount) as min_amount,
            MAX(amount) as max_amount
        ')->first();

        return [
            'total_transactions' => [
                'count' => (int) $stats->total_transactions,
                'amount' => (float) $stats->total_amount,
                'label' => 'Total Transactions'
            ],
            'approved_transactions' => [
                'count' => (int) $stats->approved_count,
                'amount' => (float) $stats->approved_amount,
                'label' => 'Approved Transactions'
            ],
            'pending_transactions' => [
                'count' => (int) $stats->pending_count,
                'amount' => (float) $stats->pending_amount,
                'label' => 'Pending Transactions'
            ],
            'declined_transactions' => [
                'count' => (int) $stats->declined_count,
                'amount' => (float) $stats->declined_amount,
                'label' => 'Declined Transactions'
            ],
            'failed_transactions' => [
                'count' => (int) $stats->failed_count,
                'amount' => (float) $stats->failed_amount,
                'label' => 'Failed Transactions'
            ],
            'refunded_transactions' => [
                'count' => (int) $stats->refunded_count,
                'amount' => (float) $stats->refunded_amount,
                'label' => 'Refunded Transactions'
            ],
            'voided_transactions' => [
                'count' => (int) $stats->voided_count,
                'amount' => (float) $stats->voided_amount,
                'label' => 'Voided Transactions'
            ],
            'average_transaction' => [
                'amount' => (float) $stats->average_amount,
                'label' => 'Average Transaction'
            ],
            'min_transaction' => [
                'amount' => (float) $stats->min_amount,
                'label' => 'Minimum Transaction'
            ],
            'max_transaction' => [
                'amount' => (float) $stats->max_amount,
                'label' => 'Maximum Transaction'
            ]
        ];
    }

    /**
     * Get transaction status breakdown
     */
    private function getTransactionStatusBreakdown($query)
    {
        $clone = clone $query;
        
        $statusBreakdown = $clone->selectRaw('
            status,
            COUNT(*) as count,
            SUM(amount) as total_amount,
            AVG(amount) as average_amount
        ')
        ->groupBy('status')
        ->orderBy('count', 'desc')
        ->get()
        ->map(function ($item) {
            return [
                'status' => $item->status,
                'count' => (int) $item->count,
                'total_amount' => (float) $item->total_amount,
                'average_amount' => (float) $item->average_amount,
                'percentage' => 0 // Will be calculated below
            ];
        });

        // Calculate percentages
        $totalCount = $statusBreakdown->sum('count');
        if ($totalCount > 0) {
            $statusBreakdown = $statusBreakdown->map(function ($item) use ($totalCount) {
                $item['percentage'] = round(($item['count'] / $totalCount) * 100, 2);
                return $item;
            });
        }

        return $statusBreakdown;
    }

    /**
     * Get payment method breakdown
     */
    private function getPaymentMethodBreakdown($query)
    {
        $clone = clone $query;
        
        return $clone->selectRaw('
            payment_type,
            COUNT(*) as count,
            SUM(amount) as total_amount,
            AVG(amount) as average_amount,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as approved_amount
        ')
        ->whereNotNull('payment_type')
        ->groupBy('payment_type')
        ->orderBy('count', 'desc')
        ->get()
        ->map(function ($item) {
            return [
                'payment_type' => $item->payment_type,
                'count' => (int) $item->count,
                'total_amount' => (float) $item->total_amount,
                'average_amount' => (float) $item->average_amount,
                'approved_count' => (int) $item->approved_count,
                'approved_amount' => (float) $item->approved_amount,
                'success_rate' => $item->count > 0 ? round(($item->approved_count / $item->count) * 100, 2) : 0
            ];
        });
    }

    /**
     * Get terminal performance
     */
    private function getTerminalPerformance($query)
    {
        $clone = clone $query;
        
        return $clone->selectRaw('
            terminal_id,
            COUNT(*) as transaction_count,
            SUM(amount) as total_amount,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as approved_amount,
            SUM(CASE WHEN status = "declined" THEN 1 ELSE 0 END) as declined_count,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count,
            AVG(amount) as average_amount
        ')
        ->whereNotNull('terminal_id')
        ->groupBy('terminal_id')
        ->orderBy('transaction_count', 'desc')
        ->limit(20)
        ->get()
        ->map(function ($item) {
            return [
                'terminal_id' => $item->terminal_id,
                'terminal_name' => $item->terminal_id ?? 'Unknown',
                'transaction_count' => (int) $item->transaction_count,
                'total_amount' => (float) $item->total_amount,
                'approved_count' => (int) $item->approved_count,
                'approved_amount' => (float) $item->approved_amount,
                'declined_count' => (int) $item->declined_count,
                'pending_count' => (int) $item->pending_count,
                'average_amount' => (float) $item->average_amount,
                'success_rate' => $item->transaction_count > 0 ? 
                    round(($item->approved_count / $item->transaction_count) * 100, 2) : 0
            ];
        });
    }

    /**
     * Get merchant performance
     */
    private function getMerchantPerformance($query)
    {
        $clone = clone $query;
        
        return $clone->selectRaw('
            merchant_id,
            COUNT(*) as transaction_count,
            SUM(amount) as total_amount,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as approved_amount,
            AVG(amount) as average_amount
        ')
        ->whereNotNull('merchant_id')
        ->groupBy('merchant_id')
        ->with(['merchant'])
        ->orderBy('transaction_count', 'desc')
        ->limit(20)
        ->get()
        ->map(function ($item) {
            return [
                'merchant_id' => $item->merchant_id,
                'merchant_name' => $item->merchant ? $item->merchant->name : 'Unknown',
                'transaction_count' => (int) $item->transaction_count,
                'total_amount' => (float) $item->total_amount,
                'approved_count' => (int) $item->approved_count,
                'approved_amount' => (float) $item->approved_amount,
                'average_amount' => (float) $item->average_amount,
                'success_rate' => $item->transaction_count > 0 ? 
                    round(($item->approved_count / $item->transaction_count) * 100, 2) : 0
            ];
        });
    }

    /**
     * Get batch information
     */
    private function getBatchInformation($query)
    {
        $clone = clone $query;
        
        $batchStats = $clone->selectRaw('
            COUNT(DISTINCT batch_id) as total_batches,
            SUM(CASE WHEN batch_id IS NOT NULL THEN 1 ELSE 0 END) as transactions_in_batches,
            SUM(CASE WHEN batch_id IS NULL THEN 1 ELSE 0 END) as transactions_not_in_batches
        ')->first();

        // Get recent batches
        $recentBatches = Batch::whereIn('id', function($subQuery) use ($query) {
                $subQuery->select('batch_id')
                    ->from('transactions')
                    ->whereNotNull('batch_id');
            })
            ->with(['merchant'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($batch) {
                return [
                    'id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'status' => $batch->status,
                    'total_amount' => (float) $batch->total_amount,
                    'transaction_count' => (int) $batch->transaction_count,
                    'created_at' => $batch->created_at,
                    'settled_at' => $batch->settled_at,
                    'merchant' => $batch->merchant ? [
                        'id' => $batch->merchant->id,
                        'name' => $batch->merchant->name
                    ] : null
                ];
            });

        return [
            'summary' => [
                'total_batches' => (int) $batchStats->total_batches,
                'transactions_in_batches' => (int) $batchStats->transactions_in_batches,
                'transactions_not_in_batches' => (int) $batchStats->transactions_not_in_batches
            ],
            'recent_batches' => $recentBatches
        ];
    }

    /**
     * Get recent transactions
     */
    private function getRecentTransactions($query)
    {
        $clone = clone $query;
        
        return $clone->with(['merchant', 'user'])
            ->orderBy('transaction_datetime', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => (float) $transaction->amount,
                    'currency_id' => $transaction->currency_id,
                    'status' => $transaction->status,
                    'transaction_datetime' => $transaction->transaction_datetime,
                    'payment_method' => $transaction->payment_type,
                    'terminal_id' => $transaction->terminal_id,
                    'merchant' => $transaction->merchant ? [
                        'id' => $transaction->merchant->id,
                        'name' => $transaction->merchant->name
                    ] : null,
                    'user' => $transaction->user ? [
                        'id' => $transaction->user->id,
                        'name' => $transaction->user->name
                    ] : null
                ];
            });
    }

    /**
     * Get pending transactions
     */
    private function getPendingTransactions($query)
    {
        $clone = clone $query;
        
        return $clone->where('status', 'pending')
            ->with(['merchant', 'user'])
            ->orderBy('transaction_datetime', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => (float) $transaction->amount,
                    'currency_id' => $transaction->currency_id,
                    'status' => $transaction->status,
                    'transaction_datetime' => $transaction->transaction_datetime,
                    'terminal_id' => $transaction->terminal_id,
                    'merchant' => $transaction->merchant ? [
                        'id' => $transaction->merchant->id,
                        'name' => $transaction->merchant->name
                    ] : null,
                    'user' => $transaction->user ? [
                        'id' => $transaction->user->id,
                        'name' => $transaction->user->name
                    ] : null
                ];
            });
    }

    /**
     * Get hourly activity for the last 7 days
     */
    private function getHourlyActivity($query)
    {
        $clone = clone $query;
        
        return $clone->where('transaction_datetime', '>=', Carbon::now()->subDays(7))
            ->selectRaw('
                HOUR(transaction_datetime) as hour,
                COUNT(*) as transaction_count,
                SUM(amount) as total_amount,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as approved_amount
            ')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(function ($item) {
                return [
                    'hour' => (int) $item->hour,
                    'transaction_count' => (int) $item->transaction_count,
                    'total_amount' => (float) $item->total_amount,
                    'approved_count' => (int) $item->approved_count,
                    'approved_amount' => (float) $item->approved_amount
                ];
            });
    }

    /**
     * Get daily trends
     */
    private function getDailyTrends($query)
    {
        $clone = clone $query;
        
        return $clone->selectRaw('
            DATE(transaction_datetime) as date,
            COUNT(*) as transaction_count,
            SUM(amount) as total_amount,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as approved_amount,
            SUM(CASE WHEN status = "declined" THEN 1 ELSE 0 END) as declined_count,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count
        ')
        ->groupBy('date')
        ->orderBy('date')
        ->get()
        ->map(function ($item) {
            return [
                'date' => $item->date,
                'transaction_count' => (int) $item->transaction_count,
                'total_amount' => (float) $item->total_amount,
                'approved_count' => (int) $item->approved_count,
                'approved_amount' => (float) $item->approved_amount,
                'declined_count' => (int) $item->declined_count,
                'pending_count' => (int) $item->pending_count
            ];
        });
    }

    /**
     * Get daily data with hourly breakdown and chart data
     */
    private function getDailyWithHours($query)
    {
        $clone = clone $query;
        
        $dailyData = $clone->selectRaw('
            DATE(transaction_datetime) as date,
            HOUR(transaction_datetime) as hour,
            COUNT(*) as transaction_count,
            SUM(amount) as total_amount,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as approved_amount,
            SUM(CASE WHEN status = "declined" THEN 1 ELSE 0 END) as declined_count,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count
        ')
        ->groupBy('date', 'hour')
        ->orderBy('date')
        ->orderBy('hour')
        ->get()
        ->groupBy('date')
        ->map(function ($dayData) {
            // Prepare chart data for the day
            $chartData = $dayData->sortBy('hour')->map(function ($item) {
                return [
                    'hour' => (int) $item->hour,
                    'hour_label' => sprintf('%02d:00', $item->hour),
                    'transaction_count' => (int) $item->transaction_count,
                    'total_amount' => (float) $item->total_amount,
                    'approved_count' => (int) $item->approved_count,
                    'approved_amount' => (float) $item->approved_amount,
                    'declined_count' => (int) $item->declined_count,
                    'pending_count' => (int) $item->pending_count,
                    'failed_count' => (int) $item->failed_count
                ];
            })->values();
            
            // Calculate daily totals for summary
            $dailyTotals = $dayData->reduce(function ($carry, $item) {
                $carry['transaction_count'] += $item->transaction_count;
                $carry['total_amount'] += $item->total_amount;
                $carry['approved_count'] += $item->approved_count;
                $carry['approved_amount'] += $item->approved_amount;
                $carry['declined_count'] += $item->declined_count;
                $carry['pending_count'] += $item->pending_count;
                $carry['failed_count'] += $item->failed_count;
                return $carry;
            }, [
                'transaction_count' => 0,
                'total_amount' => 0,
                'approved_count' => 0,
                'approved_amount' => 0,
                'declined_count' => 0,
                'pending_count' => 0,
                'failed_count' => 0
            ]);
            
            // Prepare chart labels and datasets
            $chartLabels = $chartData->pluck('hour_label')->toArray();
            $chartDatasets = [
                [
                    'label' => 'Transaction Count',
                    'data' => $chartData->pluck('transaction_count')->toArray(),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 2,
                    'type' => 'bar'
                ],
                [
                    'label' => 'Total Amount',
                    'data' => $chartData->pluck('total_amount')->toArray(),
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 2,
                    'type' => 'line',
                    'yAxisID' => 'y1'
                ],
                [
                    'label' => 'Approved Count',
                    'data' => $chartData->pluck('approved_count')->toArray(),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => 'rgba(34, 197, 94, 1)',
                    'borderWidth' => 2,
                    'type' => 'bar'
                ]
            ];
            
            return [
                'date' => $dayData->first()->date,
                'summary' => [
                    'total_transactions' => (int) $dailyTotals['transaction_count'],
                    'total_amount' => (float) $dailyTotals['total_amount'],
                    'approved_transactions' => (int) $dailyTotals['approved_count'],
                    'approved_amount' => (float) $dailyTotals['approved_amount'],
                    'declined_transactions' => (int) $dailyTotals['declined_count'],
                    'pending_transactions' => (int) $dailyTotals['pending_count'],
                    'failed_transactions' => (int) $dailyTotals['failed_count'],
                    'success_rate' => $dailyTotals['transaction_count'] > 0 ? 
                        round(($dailyTotals['approved_count'] / $dailyTotals['transaction_count']) * 100, 2) : 0
                ],
                'hourly_data' => $chartData,
                'chart_data' => [
                    'labels' => $chartLabels,
                    'datasets' => $chartDatasets,
                    'options' => [
                        'responsive' => true,
                        'scales' => [
                            'y' => [
                                'type' => 'linear',
                                'display' => true,
                                'position' => 'left',
                                'title' => [
                                    'display' => true,
                                    'text' => 'Transaction Count'
                                ]
                            ],
                            'y1' => [
                                'type' => 'linear',
                                'display' => true,
                                'position' => 'right',
                                'title' => [
                                    'display' => true,
                                    'text' => 'Amount'
                                ],
                                'grid' => [
                                    'drawOnChartArea' => false
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        });
        
        return $dailyData;
    }

    /**
     * Get weekly data with weekday breakdown and chart data
     */
    private function getWeeklyWithWeekdays($query)
    {
        $clone = clone $query;
        
        $weeklyData = $clone->selectRaw('
            YEARWEEK(transaction_datetime, 1) as year_week,
            YEAR(transaction_datetime) as year,
            WEEK(transaction_datetime, 1) as week_number,
            WEEKDAY(transaction_datetime) as weekday_number,
            DAYNAME(transaction_datetime) as weekday_name,
            COUNT(*) as transaction_count,
            SUM(amount) as total_amount,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as approved_amount,
            SUM(CASE WHEN status = "declined" THEN 1 ELSE 0 END) as declined_count,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count,
            SUM(CASE WHEN status = "refunded" THEN 1 ELSE 0 END) as refunded_count,
            SUM(CASE WHEN status = "voided" THEN 1 ELSE 0 END) as voided_count
        ')
        ->groupBy('year_week', 'weekday_number')
        ->orderBy('year_week')
        ->orderBy('weekday_number')
        ->get()
        ->groupBy('year_week')
        ->map(function ($weekData) {
            $weekInfo = $weekData->first();
            
            // Prepare chart data for the week
            $chartData = $weekData->sortBy('weekday_number')->map(function ($item) {
                return [
                    'weekday_number' => (int) $item->weekday_number,
                    'weekday_name' => $item->weekday_name,
                    'transaction_count' => (int) $item->transaction_count,
                    'total_amount' => (float) $item->total_amount,
                    'approved_count' => (int) $item->approved_count,
                    'approved_amount' => (float) $item->approved_amount,
                    'declined_count' => (int) $item->declined_count,
                    'pending_count' => (int) $item->pending_count,
                    'failed_count' => (int) $item->failed_count,
                    'refunded_count' => (int) $item->refunded_count,
                    'voided_count' => (int) $item->voided_count
                ];
            })->values();
            
            // Calculate weekly totals for summary
            $weeklyTotals = $weekData->reduce(function ($carry, $item) {
                $carry['transaction_count'] += $item->transaction_count;
                $carry['total_amount'] += $item->total_amount;
                $carry['approved_count'] += $item->approved_count;
                $carry['approved_amount'] += $item->approved_amount;
                $carry['declined_count'] += $item->declined_count;
                $carry['pending_count'] += $item->pending_count;
                $carry['failed_count'] += $item->failed_count;
                $carry['refunded_count'] += $item->refunded_count;
                $carry['voided_count'] += $item->voided_count;
                return $carry;
            }, [
                'transaction_count' => 0,
                'total_amount' => 0,
                'approved_count' => 0,
                'approved_amount' => 0,
                'declined_count' => 0,
                'pending_count' => 0,
                'failed_count' => 0,
                'refunded_count' => 0,
                'voided_count' => 0
            ]);
            
            // Prepare chart labels and datasets
            $chartLabels = $chartData->pluck('weekday_name')->toArray();
            $chartDatasets = [
                [
                    'label' => 'Transaction Count',
                    'data' => $chartData->pluck('transaction_count')->toArray(),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 2
                ],
                [
                    'label' => 'Total Amount',
                    'data' => $chartData->pluck('total_amount')->toArray(),
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 2,
                    'yAxisID' => 'y1'
                ],
                [
                    'label' => 'Approved Count',
                    'data' => $chartData->pluck('approved_count')->toArray(),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => 'rgba(34, 197, 94, 1)',
                    'borderWidth' => 2
                ]
            ];
            
            return [
                'year' => (int) $weekInfo->year,
                'week_number' => (int) $weekInfo->week_number,
                'year_week' => $weekInfo->year_week,
                'week_start' => Carbon::now()->setISODate($weekInfo->year, $weekInfo->week_number)->startOfWeek()->format('Y-m-d'),
                'week_end' => Carbon::now()->setISODate($weekInfo->year, $weekInfo->week_number)->endOfWeek()->format('Y-m-d'),
                'summary' => [
                    'total_transactions' => (int) $weeklyTotals['transaction_count'],
                    'total_amount' => (float) $weeklyTotals['total_amount'],
                    'approved_transactions' => (int) $weeklyTotals['approved_count'],
                    'approved_amount' => (float) $weeklyTotals['approved_amount'],
                    'declined_transactions' => (int) $weeklyTotals['declined_count'],
                    'pending_transactions' => (int) $weeklyTotals['pending_count'],
                    'failed_transactions' => (int) $weeklyTotals['failed_count'],
                    'refunded_transactions' => (int) $weeklyTotals['refunded_count'],
                    'voided_transactions' => (int) $weeklyTotals['voided_count'],
                    'success_rate' => $weeklyTotals['transaction_count'] > 0 ? 
                        round(($weeklyTotals['approved_count'] / $weeklyTotals['transaction_count']) * 100, 2) : 0
                ],
                'weekdays' => $chartData,
                'chart_data' => [
                    'labels' => $chartLabels,
                    'datasets' => $chartDatasets,
                    'options' => [
                        'responsive' => true,
                        'scales' => [
                            'y' => [
                                'type' => 'linear',
                                'display' => true,
                                'position' => 'left',
                                'title' => [
                                    'display' => true,
                                    'text' => 'Transaction Count'
                                ]
                            ],
                            'y1' => [
                                'type' => 'linear',
                                'display' => true,
                                'position' => 'right',
                                'title' => [
                                    'display' => true,
                                    'text' => 'Amount'
                                ],
                                'grid' => [
                                    'drawOnChartArea' => false
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        });
        
        return $weeklyData;
    }

    /**
     * Get monthly data with full month breakdown and chart data
     */
    private function getMonthlyWithFullMonth($query)
    {
        $clone = clone $query;
        
        $monthlyData = $clone->selectRaw('
            YEAR(transaction_datetime) as year,
            MONTH(transaction_datetime) as month_number,
            MONTHNAME(transaction_datetime) as month_name,
            DATE_FORMAT(transaction_datetime, "%Y-%m") as year_month,
            COUNT(*) as transaction_count,
            SUM(amount) as total_amount,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as approved_amount,
            SUM(CASE WHEN status = "declined" THEN 1 ELSE 0 END) as declined_count,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count,
            SUM(CASE WHEN status = "refunded" THEN 1 ELSE 0 END) as refunded_count,
            SUM(CASE WHEN status = "voided" THEN 1 ELSE 0 END) as voided_count,
            AVG(amount) as average_amount,
            MIN(amount) as min_amount,
            MAX(amount) as max_amount
        ')
        ->groupBy('year', 'month_number')
        ->orderBy('year')
        ->orderBy('month_number')
        ->get();
        
        // Prepare chart data
        $chartLabels = $monthlyData->pluck('month_name')->toArray();
        $chartDatasets = [
            [
                'label' => 'Transaction Count',
                'data' => $monthlyData->pluck('transaction_count')->toArray(),
                'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                'borderColor' => 'rgba(54, 162, 235, 1)',
                'borderWidth' => 2,
                'type' => 'bar'
            ],
            [
                'label' => 'Total Amount',
                'data' => $monthlyData->pluck('total_amount')->toArray(),
                'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                'borderColor' => 'rgba(75, 192, 192, 1)',
                'borderWidth' => 2,
                'type' => 'line',
                'yAxisID' => 'y1'
            ],
            [
                'label' => 'Approved Count',
                'data' => $monthlyData->pluck('approved_count')->toArray(),
                'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                'borderColor' => 'rgba(34, 197, 94, 1)',
                'borderWidth' => 2,
                'type' => 'bar'
            ]
        ];
        
        // Calculate overall totals
        $overallTotals = $monthlyData->reduce(function ($carry, $item) {
            $carry['transaction_count'] += $item->transaction_count;
            $carry['total_amount'] += $item->total_amount;
            $carry['approved_count'] += $item->approved_count;
            $carry['approved_amount'] += $item->approved_amount;
            $carry['declined_count'] += $item->declined_count;
            $carry['pending_count'] += $item->pending_count;
            $carry['failed_count'] += $item->failed_count;
            $carry['refunded_count'] += $item->refunded_count;
            $carry['voided_count'] += $item->voided_count;
            return $carry;
        }, [
            'transaction_count' => 0,
            'total_amount' => 0,
            'approved_count' => 0,
            'approved_amount' => 0,
            'declined_count' => 0,
            'pending_count' => 0,
            'failed_count' => 0,
            'refunded_count' => 0,
            'voided_count' => 0
        ]);
        
        return [
            'overall_summary' => [
                'total_transactions' => (int) $overallTotals['transaction_count'],
                'total_amount' => (float) $overallTotals['total_amount'],
                'approved_transactions' => (int) $overallTotals['approved_count'],
                'approved_amount' => (float) $overallTotals['approved_amount'],
                'declined_transactions' => (int) $overallTotals['declined_count'],
                'pending_transactions' => (int) $overallTotals['pending_count'],
                'failed_transactions' => (int) $overallTotals['failed_count'],
                'refunded_transactions' => (int) $overallTotals['refunded_count'],
                'voided_transactions' => (int) $overallTotals['voided_count'],
                'success_rate' => $overallTotals['transaction_count'] > 0 ? 
                    round(($overallTotals['approved_count'] / $overallTotals['transaction_count']) * 100, 2) : 0
            ],
            'monthly_data' => $monthlyData->map(function ($item) {
                return [
                    'year' => (int) $item->year,
                    'month_number' => (int) $item->month_number,
                    'month_name' => $item->month_name,
                    'year_month' => $item->year_month,
                    'transaction_count' => (int) $item->transaction_count,
                    'total_amount' => (float) $item->total_amount,
                    'approved_count' => (int) $item->approved_count,
                    'approved_amount' => (float) $item->approved_amount,
                    'declined_count' => (int) $item->declined_count,
                    'pending_count' => (int) $item->pending_count,
                    'failed_count' => (int) $item->failed_count,
                    'refunded_count' => (int) $item->refunded_count,
                    'voided_count' => (int) $item->voided_count,
                    'average_amount' => (float) $item->average_amount,
                    'min_amount' => (float) $item->min_amount,
                    'max_amount' => (float) $item->max_amount,
                    'success_rate' => $item->transaction_count > 0 ? 
                        round(($item->approved_count / $item->transaction_count) * 100, 2) : 0
                ];
            }),
            'chart_data' => [
                'labels' => $chartLabels,
                'datasets' => $chartDatasets,
                'options' => [
                    'responsive' => true,
                    'scales' => [
                        'y' => [
                            'type' => 'linear',
                            'display' => true,
                            'position' => 'left',
                            'title' => [
                                'display' => true,
                                'text' => 'Transaction Count'
                            ]
                        ],
                        'y1' => [
                            'type' => 'linear',
                            'display' => true,
                            'position' => 'right',
                            'title' => [
                                'display' => true,
                                'text' => 'Amount'
                            ],
                            'grid' => [
                                'drawOnChartArea' => false
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get top performing terminals
     */
    private function getTopTerminals($query)
    {
        $clone = clone $query;
        
        return $clone->selectRaw('
            terminal_id,
            COUNT(*) as transaction_count,
            SUM(amount) as total_amount,
            SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as approved_amount
        ')
        ->whereNotNull('terminal_id')
        ->groupBy('terminal_id')
        ->orderBy('total_amount', 'desc')
        ->limit(10)
        ->get()
        ->map(function ($item) {
            return [
                'terminal_id' => $item->terminal_id,
                'terminal_name' => $item->terminal_id ?? 'Unknown',
                'transaction_count' => (int) $item->transaction_count,
                'total_amount' => (float) $item->total_amount,
                'approved_amount' => (float) $item->approved_amount
            ];
        });
    }

    /**
     * Get top performing merchants
     */
    private function getTopMerchants($query)
    {
        $clone = clone $query;
        
        return $clone->selectRaw('
            merchant_id,
            COUNT(*) as transaction_count,
            SUM(amount) as total_amount,
            SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END) as approved_amount
        ')
        ->whereNotNull('merchant_id')
        ->groupBy('merchant_id')
        ->with(['merchant'])
        ->orderBy('total_amount', 'desc')
        ->limit(10)
        ->get()
        ->map(function ($item) {
            return [
                'merchant_id' => $item->merchant_id,
                'merchant_name' => $item->merchant ? $item->merchant->name : 'Unknown',
                'transaction_count' => (int) $item->transaction_count,
                'total_amount' => (float) $item->total_amount,
                'approved_amount' => (float) $item->approved_amount
            ];
        });
    }

    /**
     * Get refund and void summary
     */
    private function getRefundVoidSummary($query)
    {
        $clone = clone $query;
        
        $stats = $clone->selectRaw('
            SUM(CASE WHEN status = "refunded" THEN 1 ELSE 0 END) as refunded_count,
            SUM(CASE WHEN status = "refunded" THEN amount ELSE 0 END) as refunded_amount,
            SUM(CASE WHEN status = "voided" THEN 1 ELSE 0 END) as voided_count,
            SUM(CASE WHEN status = "voided" THEN amount ELSE 0 END) as voided_amount,
            SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_count,
            SUM(CASE WHEN status = "cancelled" THEN amount ELSE 0 END) as cancelled_amount
        ')->first();

        return [
            'refunded' => [
                'count' => (int) $stats->refunded_count,
                'amount' => (float) $stats->refunded_amount
            ],
            'voided' => [
                'count' => (int) $stats->voided_count,
                'amount' => (float) $stats->voided_amount
            ],
            'cancelled' => [
                'count' => (int) $stats->cancelled_count,
                'amount' => (float) $stats->cancelled_amount
            ]
        ];
    }

    /**
     * Get currency breakdown
     */
    private function getCurrencyBreakdown($query)
    {
        $clone = clone $query;
        
        return $clone->join('currencies', 'transactions.currency_id', '=', 'currencies.id')
            ->selectRaw('
                currencies.currency_code as currency,
                COUNT(*) as count,
                SUM(transactions.amount) as total_amount,
                AVG(transactions.amount) as average_amount
            ')
            ->groupBy('currencies.currency_code')
            ->orderBy('total_amount', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'currency' => $item->currency,
                    'count' => (int) $item->count,
                'total_amount' => (float) $item->total_amount,
                'average_amount' => (float) $item->average_amount
            ];
        });
    }

    /**
     * Get real-time dashboard updates
     */
    public function realTimeUpdates(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }

            // Get real-time statistics for the last hour
            $lastHour = Carbon::now()->subHour();
            
            $realTimeStats = Transaction::where('transaction_datetime', '>=', $lastHour)
                ->selectRaw('
                    COUNT(*) as total_transactions,
                    SUM(amount) as total_amount,
                    SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = "declined" THEN 1 ELSE 0 END) as declined_count
                ')->first();

            // Get recent transactions in the last hour
            $recentTransactions = Transaction::where('transaction_datetime', '>=', $lastHour)
                ->with(['merchant'])
                ->orderBy('transaction_datetime', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'transaction_id' => $transaction->transaction_id,
                        'amount' => (float) $transaction->amount,
                        'status' => $transaction->status,
                        'transaction_datetime' => $transaction->transaction_datetime,
                        'terminal_id' => $transaction->terminal_id ?? 'Unknown',
                        'merchant' => $transaction->merchant ? $transaction->merchant->name : 'Unknown'
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'last_hour_stats' => [
                        'total_transactions' => (int) $realTimeStats->total_transactions,
                        'total_amount' => (float) $realTimeStats->total_amount,
                        'approved_count' => (int) $realTimeStats->approved_count,
                        'pending_count' => (int) $realTimeStats->pending_count,
                        'declined_count' => (int) $realTimeStats->declined_count
                    ],
                    'recent_transactions' => $recentTransactions,
                    'last_updated' => Carbon::now()->toISOString()
                ],
                'message' => 'Real-time updates retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving real-time updates: ' . $e->getMessage()
            ], 500);
        }
    }
}
