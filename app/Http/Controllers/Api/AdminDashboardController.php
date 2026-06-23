<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Merchant, User, Terminal, Transaction};
use App\Models\Partner;
use App\Models\Service;
use App\Models\Country;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends Controller
{
    use ApiResponse;

    private array $saleStatuses = ['APPROVED', 'PENDING', 'CAPTURED'];
    private array $refundStatuses = ['REFUNDED'];
    private array $voidStatuses = ['VOIDED'];

    /**
     * Get admin dashboard data with statistics, charts, and latest transactions
     * Aggregates data from AuthService, Pos, and local SoftPos
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $this->getFilterParams($request);
            $token = $request->bearerToken();
            $transactionQuery = $this->createTransactionQuery($filters);

            $authServiceUrl = env('AUTH_SERVICE_URL', 'http://193.123.83.134:92');

            $merchantStats = $this->fetchFromService($authServiceUrl . '/api/v2/admin/merchants/statistics', $token);
            $userStats = $this->fetchFromService($authServiceUrl . '/api/v2/admin/users/statistics', $token);
            $terminalStats = $this->fetchFromService($authServiceUrl . '/api/v2/admin/terminals/statistics', $token);

            $dailyChart = $this->buildChartData($filters, 'daily');
            $weeklyChart = $this->buildChartData($filters, 'weekly');
            $monthlyChart = $this->buildChartData($filters, 'monthly');
            $todayStats = $this->buildTodayStats($filters);

            $statistics = $this->buildStatisticsPayload($transactionQuery, $merchantStats, $userStats, $terminalStats, $todayStats);
            $terminalStatus = $this->buildTerminalStatusPayload($terminalStats, $token, $filters['limit']);
            $latestTransactions = $this->buildLatestTransactionsPayload($transactionQuery, $filters['limit']);
            $topMerchants = $this->getTopSellingMerchants();

            $data = [
                'statistics' => $statistics,
                'transactionChartData' => [
                    'daily' => $dailyChart,
                    'weekly' => $weeklyChart,
                    'monthly' => $monthlyChart,
                ],
                'todayStats' => $todayStats,
                'topMerchants' => $topMerchants,
                'terminalStatus' => $terminalStatus,
                'latestTransactions' => $latestTransactions,
            ];

            return $this->SuccessMessage($data);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to load dashboard data: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * V3 admin dashboard payload tailored for Payment admin UI.
     * Returns products/partners/transactions counts, approved amount revenue,
     * 2-line chart (success/failed), top partners, and top countries.
     */
    public function v3Dashboard(Request $request): JsonResponse
    {
        try {
            $filters = $this->getFilterParams($request);

            $baseQuery = $this->createTransactionQuery($filters);

            $approvedStatuses = ['APPROVED', 'CAPTURED'];
            $failedStatuses = ['FAILED', 'DECLINED', 'CANCELLED', 'EXPIRED', 'REVERSED'];

            $partnerCount = Partner::query()->where(function ($query) {
                $query
                    ->where('is_active', true)
                    ->orWhereIn('status', ['approved', 'active']);
            })->count();

            $productCount = Service::query()->where(function ($query) {
                $query
                    ->where('is_active', true)
                    ->orWhere('status', 'active');
            })->count();

            $transactionsCount = (clone $baseQuery)->count();

            $approvedAmount = (clone $baseQuery)
                ->whereIn(DB::raw('UPPER(status)'), $approvedStatuses)
                ->sum('amount');

            $monthlyChart = $this->buildMonthlySuccessFailedChart($filters, $approvedStatuses, $failedStatuses);
            $topPartners = $this->buildTopPartners($filters, $approvedStatuses);
            $topCountries = $this->buildTopCountries($filters, $approvedStatuses);

            return $this->SuccessMessage([
                'statistics' => [
                    'totalProducts' => $productCount,
                    'partnerCount' => $partnerCount,
                    'totalTransactions' => $transactionsCount,
                    'activeTransactions' => $transactionsCount,
                    'totalApprovedAmount' => (float) $approvedAmount,
                    'totalApprovedTransactionsAmount' => (float) $approvedAmount,
                    'newTransactionsThisMonth' => $this->countCurrentMonthTransactions($baseQuery),
                    'monthlyRevenue' => (float) $this->sumCurrentMonthApprovedAmount($baseQuery, $approvedStatuses),
                ],
                // Keep both top-level and nested keys for compatibility with frontend variants.
                'chart' => $monthlyChart,
                'partners' => $topPartners,
                'countries' => $topCountries,
                'subscriptionData' => [
                    'statistics' => [
                        'totalProducts' => $productCount,
                        'partnerCount' => $partnerCount,
                        'totalTransactions' => $transactionsCount,
                        'activeTransactions' => $transactionsCount,
                        'totalApprovedAmount' => (float) $approvedAmount,
                        'totalApprovedTransactionsAmount' => (float) $approvedAmount,
                        'newTransactionsThisMonth' => $this->countCurrentMonthTransactions($baseQuery),
                        'monthlyRevenue' => (float) $this->sumCurrentMonthApprovedAmount($baseQuery, $approvedStatuses),
                    ],
                    'chart' => $monthlyChart,
                    'partners' => $topPartners,
                    'countries' => $topCountries,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to load v3 dashboard data: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Return only the chart data payload for the admin dashboard.
     */
    public function charts(Request $request): JsonResponse
    {
        try {
            $filters = $this->getFilterParams($request);
            $period = strtolower($request->input('period', 'daily'));

            if (!in_array($period, ['daily', 'weekly', 'monthly'], true)) {
                $period = 'daily';
            }

            $chart = $this->buildChartData($filters, $period);
            $todayStats = $this->buildTodayStats($filters);

            return $this->SuccessMessage([
                'chart' => $chart,
                'todayStats' => $todayStats,
            ]);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to load dashboard charts: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Return the latest transactions broken down by type for the admin dashboard widget.
     */
    public function latestTransactions(Request $request): JsonResponse
    {
        try {
            $filters = $this->getFilterParams($request);
            $transactionQuery = $this->createTransactionQuery($filters);
            $token = $request->bearerToken();

            $payload = $this->buildLatestTransactionsPayload($transactionQuery, $filters['limit'], $token);

            return $this->SuccessMessage($payload);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to load latest transactions: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export dashboard data to CSV
     */
    public function export(Request $request)
    {
        try {
            // Get filter parameters
            $datetimeFrom = $request->input('datetime_from');
            $datetimeTo = $request->input('datetime_to');
            $transactionStatus = $request->input('transaction_status');
            $limit = (int) $request->input('limit', 1000);

            // Build export data query
            $transactionQuery = Transaction::query();
            
            if ($datetimeFrom) {
                $transactionQuery->where('created_at', '>=', Carbon::parse($datetimeFrom));
            }
            
            if ($datetimeTo) {
                $transactionQuery->where('created_at', '<=', Carbon::parse($datetimeTo));
            }
            
            if ($transactionStatus) {
                $transactionQuery->where('status', strtoupper($transactionStatus));
            }

            $transactions = $transactionQuery->with(['merchant', 'terminal', 'user'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            $filename = 'admin_dashboard_export_' . date('Y-m-d_H-i-s') . '.csv';

            return response()->streamDownload(function () use ($transactions) {
                $file = fopen('php://output', 'w');
                
                // Add BOM for proper UTF-8 encoding in Excel
                fwrite($file, "\xEF\xBB\xBF");
                
                // Write CSV header
                fputcsv($file, [
                    'Transaction ID',
                    'Merchant Name',
                    'Terminal ID',
                    'Amount',
                    'Status',
                    'Type',
                    'Transaction Date',
                    'RRN',
                    'Auth Code',
                    'Invoice No'
                ]);
                
                // Write transaction data
                foreach ($transactions as $transaction) {
                    fputcsv($file, [
                        $transaction->id,
                        $transaction->merchant->business_name ?? 'N/A',
                        $transaction->terminal_id ?? 'N/A',
                        number_format($transaction->amount ?? 0, 2),
                        $transaction->status ?? 'N/A',
                        $transaction->transaction_type ?? 'N/A',
                        $transaction->created_at ? $transaction->created_at->format('Y-m-d H:i:s') : 'N/A',
                        $transaction->rrn ?? 'N/A',
                        $transaction->auth_code ?? 'N/A',
                        $transaction->invoice_no ?? 'N/A',
                    ]);
                }
                
                fclose($file);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard export error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to export dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Normalize dashboard filter parameters.
     */
    private function getFilterParams(Request $request): array
    {
        return [
            'datetime_from' => $request->input('datetime_from'),
            'datetime_to' => $request->input('datetime_to'),
            'transaction_status' => $request->input('transaction_status'),
            'limit' => (int) max((int) $request->input('limit', 10), 1),
        ];
    }

    /**
     * Build the base transaction query with applied filters.
     */
    private function createTransactionQuery(array $filters)
    {
        $query = Transaction::query();

        if (!empty($filters['datetime_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['datetime_from']));
        }

        if (!empty($filters['datetime_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['datetime_to']));
        }

        if (!empty($filters['transaction_status'])) {
            $query->where('status', strtoupper($filters['transaction_status']));
        }

        return $query;
    }

    /**
     * Build the chart payload for the requested period with zero-filled labels.
     */
    private function buildChartData(array $filters, string $period): array
    {
        $period = strtolower($period);

        return match ($period) {
            'weekly' => $this->buildWeeklyChartData($filters),
            'monthly' => $this->buildMonthlyChartData($filters),
            default => $this->buildDailyChartData($filters),
        };
    }

    private function buildMonthlySuccessFailedChart(array $filters, array $approvedStatuses, array $failedStatuses): array
    {
        if (!empty($filters['datetime_from']) && !empty($filters['datetime_to'])) {
            $start = Carbon::parse($filters['datetime_from'])->startOfMonth();
            $end = Carbon::parse($filters['datetime_to'])->endOfMonth();
        } else {
            $start = Carbon::now()->subMonths(11)->startOfMonth();
            $end = Carbon::now()->endOfMonth();
        }

        $labels = [];
        $monthKeys = [];

        for ($i = 0; $i < 12; $i++) {
            $month = $start->copy()->addMonths($i);
            if ($month->greaterThan($end)) {
                break;
            }
            $key = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $monthKeys[$key] = count($labels) - 1;
        }

        if (empty($labels)) {
            $labels = array_map(
                fn ($offset) => Carbon::now()->subMonths(11 - $offset)->format('M Y'),
                range(0, 11)
            );
            foreach ($labels as $idx => $label) {
                $monthKeys[Carbon::now()->subMonths(11 - $idx)->format('Y-m')] = $idx;
            }
        }

        $successSeries = array_fill(0, count($labels), 0);
        $failedSeries = array_fill(0, count($labels), 0);

        $rows = $this->createTransactionQuery($filters)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as bucket, UPPER(status) as status, COUNT(*) as aggregate_count')
            ->groupBy('bucket', 'status')
            ->orderBy('bucket')
            ->get();

        foreach ($rows as $row) {
            if (!isset($monthKeys[$row->bucket])) {
                continue;
            }

            $index = $monthKeys[$row->bucket];
            $status = strtoupper((string) $row->status);

            if (in_array($status, $approvedStatuses, true)) {
                $successSeries[$index] += (int) $row->aggregate_count;
                continue;
            }

            if (in_array($status, $failedStatuses, true)) {
                $failedSeries[$index] += (int) $row->aggregate_count;
            }
        }

        return array_map(function ($label, $index) use ($successSeries, $failedSeries) {
            return [
                'month' => $label,
                'successTransactions' => $successSeries[$index] ?? 0,
                'failedTransactions' => $failedSeries[$index] ?? 0,
            ];
        }, $labels, array_keys($labels));
    }

    private function buildTopPartners(array $filters, array $approvedStatuses, int $limit = 10): array
    {
        $rows = $this->createTransactionQuery($filters)
            ->whereNotNull('partner_id')
            ->selectRaw('partner_id, COUNT(*) as transaction_count, SUM(CASE WHEN UPPER(status) IN ("APPROVED","CAPTURED") THEN amount ELSE 0 END) as revenue')
            ->groupBy('partner_id')
            ->orderByDesc('transaction_count')
            ->limit($limit)
            ->with('partner')
            ->get();

        return $rows->map(function ($row) {
            $partner = $row->partner;

            return [
                'id' => $row->partner_id,
                'name' => $partner?->name
                    ?? $partner?->business_name
                    ?? 'Unknown',
                'partner_name' => $partner?->name
                    ?? $partner?->business_name
                    ?? null,
                'transactionCount' => (int) ($row->transaction_count ?? 0),
                'revenue' => (float) ($row->revenue ?? 0),
                'status' => $partner && isset($partner->status) ? $partner->status : 'active',
            ];
        })->toArray();
    }

    private function buildTopCountries(array $filters, array $approvedStatuses, int $limit = 10): array
    {
        $rows = $this->createTransactionQuery($filters)
            ->whereNotNull('country_id')
            ->selectRaw('country_id, COUNT(*) as transaction_count, SUM(CASE WHEN UPPER(status) IN ("APPROVED","CAPTURED") THEN amount ELSE 0 END) as revenue')
            ->groupBy('country_id')
            ->orderByDesc('transaction_count')
            ->limit($limit)
            ->get();

        $countryIds = $rows->pluck('country_id')->filter()->unique()->values()->all();
        $countries = Country::query()->whereIn('id', $countryIds)->get()->keyBy('id');

        return $rows->map(function ($row) use ($countries) {
            $country = $countries->get($row->country_id);
            $name = 'Unknown';

            if ($country) {
                if (is_array($country->name)) {
                    $name = $country->name['en'] ?? reset($country->name) ?: 'Unknown';
                } else {
                    $name = (string) ($country->name ?? $country->short_name ?? 'Unknown');
                }
            }

            return [
                'id' => $row->country_id,
                'name' => $name,
                'short_name' => $country->short_name ?? null,
                'transaction_count' => (int) ($row->transaction_count ?? 0),
                'revenue' => (float) ($row->revenue ?? 0),
            ];
        })->toArray();
    }

    private function countCurrentMonthTransactions($baseQuery): int
    {
        return (clone $baseQuery)
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->count();
    }

    private function sumCurrentMonthApprovedAmount($baseQuery, array $approvedStatuses): float
    {
        return (float) (clone $baseQuery)
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->whereIn(DB::raw('UPPER(status)'), $approvedStatuses)
            ->sum('amount');
    }

    /**
     * Build daily (hourly) chart data for the dashboard.
     */
    private function buildDailyChartData(array $filters): array
    {
        $referenceEnd = !empty($filters['datetime_to'])
            ? Carbon::parse($filters['datetime_to'])->endOfHour()
            : Carbon::now()->endOfHour();

        $start = $referenceEnd->copy()->subHours(23);
        $end = $referenceEnd->copy();

        $labels = [];
        $hourKeys = [];

        for ($i = 0; $i < 24; $i++) {
            $hourMoment = $start->copy()->addHours($i);
            $labels[] = $hourMoment->format('HH:00');
            $hourKeys[$hourMoment->format('Y-m-d H')] = $i;
        }

        $seriesMap = [
            'Approved' => array_fill(0, count($labels), 0),
            'Voided' => array_fill(0, count($labels), 0),
            'Refunded' => array_fill(0, count($labels), 0),
        ];

        $rows = $this->createTransactionQuery($filters)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m-%d %H") as bucket, UPPER(status) as status, COUNT(*) as aggregate_count')
            ->groupBy('bucket', 'status')
            ->get();

        foreach ($rows as $row) {
            if (!isset($hourKeys[$row->bucket])) {
                continue;
            }

            $seriesKey = $this->resolveSeriesKey($row->status, null);
            if (!$seriesKey) {
                continue;
            }

            $index = $hourKeys[$row->bucket];
            $seriesMap[$seriesKey][$index] += (int) $row->aggregate_count;
        }

        return [
            'period' => 'daily',
            'labels' => $labels,
            'series' => [
                ['name' => 'Approved', 'data' => array_values($seriesMap['Approved'])],
                ['name' => 'Voided', 'data' => array_values($seriesMap['Voided'])],
                ['name' => 'Refunded', 'data' => array_values($seriesMap['Refunded'])],
            ],
        ];
    }

    /**
     * Build weekly (daily buckets) chart data for the dashboard.
     */
    private function buildWeeklyChartData(array $filters): array
    {
        if (!empty($filters['datetime_from']) && !empty($filters['datetime_to'])) {
            $start = Carbon::parse($filters['datetime_from'])->startOfDay();
            $end = Carbon::parse($filters['datetime_to'])->endOfDay();
        } else {
            $start = Carbon::now()->subDays(6)->startOfDay();
            $end = Carbon::now()->endOfDay();
        }

        $days = $start->diffInDays($end) + 1;
        $maxDays = 31;

        if ($days > $maxDays) {
            $start = $end->copy()->subDays($maxDays - 1)->startOfDay();
            $days = $maxDays;
        }

        $labels = [];
        $dayKeys = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('M d');
            $dayKeys[$key] = $i;
        }

        $seriesMap = [
            'Approved' => array_fill(0, count($labels), 0),
            'Voided' => array_fill(0, count($labels), 0),
            'Refunded' => array_fill(0, count($labels), 0),
        ];

        $rows = $this->createTransactionQuery($filters)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as bucket, UPPER(status) as status, COUNT(*) as aggregate_count')
            ->groupBy('bucket', 'status')
            ->orderBy('bucket')
            ->get();

        foreach ($rows as $row) {
            if (!isset($dayKeys[$row->bucket])) {
                continue;
            }

            $seriesKey = $this->resolveSeriesKey($row->status, null);
            if (!$seriesKey) {
                continue;
            }

            $index = $dayKeys[$row->bucket];
            $seriesMap[$seriesKey][$index] += (int) $row->aggregate_count;
        }

        return [
            'period' => 'weekly',
            'labels' => $labels,
            'series' => [
                ['name' => 'Approved', 'data' => array_values($seriesMap['Approved'])],
                ['name' => 'Voided', 'data' => array_values($seriesMap['Voided'])],
                ['name' => 'Refunded', 'data' => array_values($seriesMap['Refunded'])],
            ],
        ];
    }

    /**
     * Build monthly (per month) chart data for the dashboard.
     */
    private function buildMonthlyChartData(array $filters): array
    {
        if (!empty($filters['datetime_from']) && !empty($filters['datetime_to'])) {
            $start = Carbon::parse($filters['datetime_from'])->startOfMonth();
            $end = Carbon::parse($filters['datetime_to'])->endOfMonth();
        } else {
            $start = Carbon::now()->subMonths(11)->startOfMonth();
            $end = Carbon::now()->endOfMonth();
        }

        $monthsCount = $start->diffInMonths($end) + 1;
        $maxMonths = 12;

        if ($monthsCount > $maxMonths) {
            $start = $end->copy()->subMonths($maxMonths - 1)->startOfMonth();
            $monthsCount = $maxMonths;
        }

        $labels = [];
        $monthKeys = [];

        for ($i = 0; $i < $monthsCount; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $monthKeys[$key] = $i;
        }

        $seriesMap = [
            'Approved' => array_fill(0, count($labels), 0),
            'Voided' => array_fill(0, count($labels), 0),
            'Refunded' => array_fill(0, count($labels), 0),
        ];

        $rows = $this->createTransactionQuery($filters)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as bucket, UPPER(status) as status, COUNT(*) as aggregate_count')
            ->groupBy('bucket', 'status')
            ->orderBy('bucket')
            ->get();

        foreach ($rows as $row) {
            if (!isset($monthKeys[$row->bucket])) {
                continue;
            }

            $seriesKey = $this->resolveSeriesKey($row->status, null);
            if (!$seriesKey) {
                continue;
            }

            $index = $monthKeys[$row->bucket];
            $seriesMap[$seriesKey][$index] += (int) $row->aggregate_count;
        }

        return [
            'period' => 'monthly',
            'labels' => $labels,
            'series' => [
                ['name' => 'Approved', 'data' => array_values($seriesMap['Approved'])],
                ['name' => 'Voided', 'data' => array_values($seriesMap['Voided'])],
                ['name' => 'Refunded', 'data' => array_values($seriesMap['Refunded'])],
            ],
        ];
    }

    /**
     * Build today's statistics for the chart subtitle.
     */
    private function buildTodayStats(array $filters): array
    {
        $query = $this->createTransactionQuery($filters)
            ->whereDate('created_at', Carbon::today())
            ->whereIn('status', $this->saleStatuses);

        return [
            'count' => (clone $query)->count(),
            'amount' => (clone $query)->sum('amount'),
        ];
    }

    /**
     * Resolve the chart series key based on transaction status and type.
     */
    private function resolveSeriesKey(string $status, ?string $type): ?string
    {
        $status = strtoupper($status);
        $type = strtoupper($type);

        if (in_array($status, $this->saleStatuses, true) || $type === 'SALE') {
            return 'Approved';
        }

        if (in_array($status, $this->voidStatuses, true) || $type === 'VOID') {
            return 'Voided';
        }

        if (in_array($status, $this->refundStatuses, true) || $type === 'REFUND') {
            return 'Refunded';
        }

        return null;
    }

    /**
     * Build the statistics payload combining cross-service responses with transaction data.
     */
    private function buildStatisticsPayload($transactionQuery, array $merchantStats, array $userStats, array $terminalStats, array $todayStats): array
    {
        $totalTransactions = (clone $transactionQuery)->count();
        $totalAmount = (clone $transactionQuery)->whereIn('status', $this->saleStatuses)->sum('amount');

        return [
            'totalMerchants' => $merchantStats['total_merchants'] ?? 0,
            'totalUsers' => $userStats['total_users'] ?? 0,
            'totalTerminals' => $terminalStats['total_terminals'] ?? 0,
            'totalTransactions' => $totalTransactions,
            'totalAmount' => number_format($totalAmount, 2),
            'todayTransactions' => $todayStats['count'] ?? 0,
            'todayAmount' => number_format($todayStats['amount'] ?? 0, 2),
        ];
    }

    /**
     * Build the latest transactions payload grouped by transaction type.
     */
    private function buildLatestTransactionsPayload($transactionQuery, int $limit, ?string $token = null): array
    {
        $baseQuery = (clone $transactionQuery)->latest();

        $latestSales = (clone $baseQuery)
            ->with(['merchant', 'partner', 'country'])
            ->where(function ($query) {
                $query->whereIn('status', $this->saleStatuses)
                    ->orWhereRaw('UPPER(transaction_type) = ?', ['SALE']);
            })
            ->limit($limit)
            ->get();

        $latestRefunds = (clone $baseQuery)
            ->with(['merchant', 'partner', 'country'])
            ->where(function ($query) {
                $query->whereIn('status', $this->refundStatuses)
                    ->orWhereRaw('UPPER(transaction_type) = ?', ['REFUND']);
            })
            ->limit($limit)
            ->get();

        $latestVoids = (clone $baseQuery)
            ->with(['merchant', 'partner', 'country'])
            ->where(function ($query) {
                $query->whereIn('status', $this->voidStatuses)
                    ->orWhereRaw('UPPER(transaction_type) = ?', ['VOID']);
            })
            ->limit($limit)
            ->get();

        $allTransactions = collect()
            ->merge($latestSales)
            ->merge($latestRefunds)
            ->merge($latestVoids);

        $terminalDetails = [];

        if ($token) {
            $terminalDetails = $this->fetchTerminalDetails(
                $this->extractTerminalIds($allTransactions),
                $token
            );
        }

        return [
            'sales' => $this->transformTransactionsWithTerminalDetails($latestSales, $terminalDetails),
            'refunds' => $this->transformTransactionsWithTerminalDetails($latestRefunds, $terminalDetails),
            'voids' => $this->transformTransactionsWithTerminalDetails($latestVoids, $terminalDetails),
        ];
    }

    /**
     * Build the terminal status payload, leveraging the AuthService dashboard endpoint when available.
     */
    private function buildTerminalStatusPayload(array $terminalStats, ?string $token, int $limit): array
    {
        $authServiceUrl = env('AUTH_SERVICE_URL', 'http://193.123.83.134:92');
        $dashboardEndpoint = rtrim($authServiceUrl, '/') . '/api/v2/admin/dashboard/terminals?limit=' . $limit;

        $terminalStatusResponse = $this->fetchFromService($dashboardEndpoint, $token);
        if (!empty($terminalStatusResponse['terminalStatus'])) {
            return $terminalStatusResponse['terminalStatus'];
        }

        // Fallback to legacy terminal listing if dashboard endpoint is unavailable
        $legacyEndpoint = rtrim($authServiceUrl, '/') . '/api/v2/admin/terminals?per_page=' . max($limit, 100);
        $terminalsResponse = $this->fetchFromService($legacyEndpoint, $token);
        $allTerminals = collect($terminalsResponse['data'] ?? []);

        return [
            'onlineCount' => $terminalStats['online_terminals'] ?? 0,
            'offlineCount' => $terminalStats['offline_terminals'] ?? 0,
            'testingCount' => $terminalStats['testing_terminals'] ?? 0,
            'onlineTerminals' => $allTerminals->filter(fn ($terminal) => strtolower($terminal['status'] ?? '') === 'online')->take($limit)->values(),
            'offlineTerminals' => $allTerminals->filter(fn ($terminal) => strtolower($terminal['status'] ?? '') === 'offline')->take($limit)->values(),
            'testingTerminals' => $allTerminals->filter(fn ($terminal) => strtolower($terminal['status'] ?? '') === 'testing')->take($limit)->values(),
        ];
    }

    /**
     * Get top selling merchants
     */
    private function getTopSellingMerchants(int $limit = 5): array
    {
        $topMerchants = Transaction::where('status', 'approved')
            ->select('merchant_id', DB::raw('SUM(amount) as total_sales'), DB::raw('COUNT(*) as transaction_count'))
            ->groupBy('merchant_id')
            ->orderByDesc('total_sales')
            ->limit($limit)
            ->with('merchant')
            ->get();

        return $topMerchants->map(function ($item) {
            return [
                'merchant_id' => $item->merchant_id,
                'merchant_name' => $item->merchant->business_name ?? 'N/A',
                'total_sales' => number_format($item->total_sales, 2),
                'transaction_count' => $item->transaction_count,
            ];
        })->toArray();
    }

    /**
     * Helper method to fetch data from other services
     */
    private function fetchFromService(string $url, ?string $token): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->timeout(10)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Failed to fetch from service: ' . $url . ' - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Extract unique terminal IDs from a collection of transactions
     */
    private function extractTerminalIds($transactions): array
    {
        return $transactions
            ->map(function ($transaction) {
                return $transaction->terminal_id
                    ?? $transaction->terminal?->id
                    ?? $transaction->terminal?->terminal_id
                    ?? null;
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Fetch terminal details from AuthService for given terminal IDs
     */
    private function fetchTerminalDetails(array $terminalIds, ?string $token): array
    {
        if (empty($terminalIds) || !$token) {
            return [];
        }

        $authServiceUrl = env('AUTH_SERVICE_URL', 'http://193.123.83.134:92');
        $terminalDetails = [];

        foreach ($terminalIds as $terminalId) {
            try {
                $endpoint = rtrim($authServiceUrl, '/') . '/api/v2/admin/terminals/' . $terminalId;
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])->timeout(5)->get($endpoint);

                if ($response->successful()) {
                    $data = $response->json();
                    $terminalData = $data['data'] ?? $data;
                    if (!empty($terminalData)) {
                        $terminalDetails[$terminalId] = $terminalData;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch terminal details for ID ' . $terminalId . ': ' . $e->getMessage());
                // Continue with other terminals
            }
        }

        return $terminalDetails;
    }

    /**
     * Transform transactions and enrich with terminal details
     */
    private function transformTransactionsWithTerminalDetails($transactions, array $terminalDetails): array
    {
        return $transactions->map(function ($transaction) use ($terminalDetails) {
            $terminalId = $transaction->terminal_id
                ?? $transaction->terminal?->id
                ?? $transaction->terminal?->terminal_id
                ?? null;

            $terminalData = $terminalId && isset($terminalDetails[$terminalId])
                ? $terminalDetails[$terminalId]
                : null;

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

            $terminalName = $terminalData['name']
                ?? $terminalData['terminal_name']
                ?? $terminalData['model_name']
                ?? $terminalData['model']
                ?? $terminalData['serial_no']
                ?? $terminalData['serial_number']
                ?? $transaction->terminal?->name
                ?? null;

            return [
                'id' => $transaction->id,
                'transaction_id' => $transaction->transaction_id ?? $transaction->id,
                'amount' => (float) ($transaction->amount ?? 0),
                'status' => $transaction->status ?? 'UNKNOWN',
                'transaction_type' => $transaction->transaction_type ?? 'SALE',
                'created_at' => $transaction->created_at?->toDateTimeString(),
                'merchant' => $transaction->merchant ? [
                    'id' => $transaction->merchant->id,
                    'business_name' => $merchantName ?? 'N/A',
                ] : null,
                'merchant_name' => $merchantName,
                'partner_name' => $partnerName,
                'country_name' => $countryName,
                'terminal_id' => $terminalId,
                'terminal_name' => $terminalName,
                'terminal' => $terminalData ? [
                    'id' => $terminalData['id'] ?? $terminalId,
                    'terminal_id' => $terminalData['terminal_id'] ?? $terminalId,
                    'name' => $terminalName,
                    'model' => $terminalData['model'] ?? $terminalData['model_name'] ?? null,
                    'model_name' => $terminalData['model_name'] ?? $terminalData['model'] ?? null,
                    'serial_no' => $terminalData['serial_no'] ?? null,
                    'branch_name' => $terminalData['branch_name'] ?? $terminalData['branch']?->name ?? null,
                ] : ($transaction->terminal ? [
                    'id' => $transaction->terminal->id ?? $terminalId,
                    'terminal_id' => $transaction->terminal->terminal_id ?? $terminalId,
                    'name' => $terminalName,
                ] : null),
                'rrn' => $transaction->rrn ?? null,
                'auth_code' => $transaction->auth_code ?? null,
                'invoice_no' => $transaction->invoice_no ?? null,
            ];
        })->toArray();
    }
}

