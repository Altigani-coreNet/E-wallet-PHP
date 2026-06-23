<?php

namespace App\View\Components;

use App\Models\Transaction;
use Illuminate\View\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionChart extends Component
{
    public $chartId;
    public $title;
    public $subtitle;
    public $chartData;
    public $merchantId;

    public function __construct($chartId = null, $title = 'Transaction Overview', $subtitle = null, $merchantId = null)
    {
        $this->chartId = $chartId ?? uniqid('transaction_chart_');
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->merchantId = $merchantId;
        $this->chartData = $this->prepareChartData();
    }

    private function prepareChartData()
    {
        $data = [
            'daily' => $this->getDailyData(),
            'weekly' => $this->getWeeklyData(),
            'monthly' => $this->getMonthlyData(),
        ];

        return $data;
    }

    private function getDailyData()
    {
        // Daily view: group today's transactions per hour (00-23)
        $today = Carbon::today();

        // Single query to get all transaction types grouped by hour
        $query = Transaction::withCountry()
            ->selectRaw('
                HOUR(created_at) as hour,
                SUM(CASE WHEN status IN ("captured", "pending", "approved") THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status = "voided" THEN 1 ELSE 0 END) as voided_count,
                SUM(CASE WHEN status = "refunded" THEN 1 ELSE 0 END) as refunded_count
            ')
            ->whereDate('created_at', $today);

        // Add merchant filter if merchant ID is provided
        if ($this->merchantId) {
            $query->where('merchant_id', $this->merchantId);
        }

        $transactionData = $query->groupBy('hour')->get();

        // Prepare hour range 0..23
        $hours = range(0, 23);
        $labels = array_map(function($h) { return sprintf('%02d:00', $h); }, $hours);

        // Initialize data arrays
        $approvedCounts = array_fill_keys($hours, 0);
        $voidedCounts = array_fill_keys($hours, 0);
        $refundedCounts = array_fill_keys($hours, 0);

        // Fill in actual counts from single query result
        foreach ($transactionData as $row) {
            $hour = (int)$row->hour;
            $approvedCounts[$hour] = (int)$row->approved_count;
            $voidedCounts[$hour] = (int)$row->voided_count;
            $refundedCounts[$hour] = (int)$row->refunded_count;
        }

        return [
            'labels' => $labels,
            'series' => [
                [
                    'name' => 'Approved',
                    'data' => array_values($approvedCounts)
                ],
                [
                    'name' => 'Voided',
                    'data' => array_values($voidedCounts)
                ],
                [
                    'name' => 'Refunded',
                    'data' => array_values($refundedCounts)
                ]
            ]
        ];
    }

    private function getWeeklyData()
    {
        // Weekly view: last 7 days grouped by day
        $startDate = Carbon::now()->subDays(6)->startOfDay();

        // Single query to get all transaction types grouped by date
        $query = Transaction::withCountry()
        ->withCountry()
            ->selectRaw('
                DATE(created_at) as date,
                SUM(CASE WHEN status IN ("captured", "pending", "approved") THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status = "voided" THEN 1 ELSE 0 END) as voided_count,
                SUM(CASE WHEN status = "refunded" THEN 1 ELSE 0 END) as refunded_count
            ')
            ->where('created_at', '>=', $startDate);

        // Add merchant filter if merchant ID is provided
        if ($this->merchantId) {
            $query->where('merchant_id', $this->merchantId);
        }

        $transactionData = $query->groupBy('date')->get();

        // Prepare day range (7 days)
        $dates = [];
        $labels = [];
        $currentDate = clone $startDate;
        while ($currentDate <= Carbon::today()) {
            $key = $currentDate->format('Y-m-d');
            $dates[] = $key;
            $labels[] = $currentDate->format('M d');
            $currentDate->addDay();
        }

        // Initialize data arrays
        $approvedCounts = array_fill_keys($dates, 0);
        $voidedCounts = array_fill_keys($dates, 0);
        $refundedCounts = array_fill_keys($dates, 0);

        // Fill in actual counts from single query result
        foreach ($transactionData as $row) {
            $approvedCounts[$row->date] = (int)$row->approved_count;
            $voidedCounts[$row->date] = (int)$row->voided_count;
            $refundedCounts[$row->date] = (int)$row->refunded_count;
        }

        return [
            'labels' => $labels,
            'series' => [
                [
                    'name' => 'Approved',
                    'data' => array_values($approvedCounts)
                ],
                [
                    'name' => 'Voided',
                    'data' => array_values($voidedCounts)
                ],
                [
                    'name' => 'Refunded',
                    'data' => array_values($refundedCounts)
                ]
            ]
        ];
    }

    private function getMonthlyData()
    {
        // Monthly view: last 30 days grouped by day
        $startDate = Carbon::now()->subDays(29)->startOfDay();

        // Single query to get all transaction types grouped by date
        $query = Transaction::withCountry()
            ->selectRaw('
                DATE(created_at) as date,
                SUM(CASE WHEN status IN ("captured", "pending", "approved") THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status = "voided" THEN 1 ELSE 0 END) as voided_count,
                SUM(CASE WHEN status = "refunded" THEN 1 ELSE 0 END) as refunded_count
            ')
            ->where('created_at', '>=', $startDate);

        // Add merchant filter if merchant ID is provided
        if ($this->merchantId) {
            $query->where('merchant_id', $this->merchantId);
        }

        $transactionData = $query->groupBy('date')->get();

        // Prepare day range (30 days)
        $dates = [];
        $labels = [];
        $currentDate = clone $startDate;
        while ($currentDate <= Carbon::today()) {
            $key = $currentDate->format('Y-m-d');
            $dates[] = $key;
            $labels[] = $currentDate->format('M d');
            $currentDate->addDay();
        }

        // Initialize data arrays
        $approvedCounts = array_fill_keys($dates, 0);
        $voidedCounts = array_fill_keys($dates, 0);
        $refundedCounts = array_fill_keys($dates, 0);

        // Fill in actual counts from single query result
        foreach ($transactionData as $row) {
            $approvedCounts[$row->date] = (int)$row->approved_count;
            $voidedCounts[$row->date] = (int)$row->voided_count;
            $refundedCounts[$row->date] = (int)$row->refunded_count;
        }

        return [
            'labels' => $labels,
            'series' => [
                [
                    'name' => 'Approved',
                    'data' => array_values($approvedCounts)
                ],
                [
                    'name' => 'Voided',
                    'data' => array_values($voidedCounts)
                ],
                [
                    'name' => 'Refunded',
                    'data' => array_values($refundedCounts)
                ]
            ]
        ];
    }

    public function render()
    {
        return view('components.transaction-chart');
    }
}