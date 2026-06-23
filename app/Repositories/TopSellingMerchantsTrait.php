<?php

namespace App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait TopSellingMerchantsTrait
{
    /**
     * Get top selling merchants based on transaction count and amount
     */
    public function getTopSellingMerchants($limit = 10, $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        $topMerchants = \App\Models\Transaction::select(
                'merchant_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->whereIn('status', ['approved', 'pending', 'captured'])
            ->where('created_at', '>=', $startDate)
            ->groupBy('merchant_id')
            ->orderBy('transaction_count', 'desc')
            ->limit($limit)
            ->with('merchant:id,name,business_type') // Eager load merchant details
            ->get();

        $labels = [];
        $counts = [];
        $amounts = [];

        foreach ($topMerchants as $merchant) {
            if ($merchant->merchant) { // Check if merchant exists
                $labels[] = $merchant->merchant->name;
                $counts[] = $merchant->transaction_count;
                $amounts[] = (float) $merchant->total_amount;
            }
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'amounts' => $amounts,
            'merchants' => $topMerchants
        ];
    }
}
