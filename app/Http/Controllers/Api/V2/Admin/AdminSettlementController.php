<?php

namespace App\Http\Controllers\Api\V2\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettlementService;
use App\Models\Settlement;
use App\Repositories\SettlementRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminSettlementController extends Controller
{
    use ApiResponse;

    protected $settlementService;
    protected $settlementRepository;

    public function __construct(SettlementService $settlementService, SettlementRepository $settlementRepository)
    {
        $this->settlementService = $settlementService;
        $this->settlementRepository = $settlementRepository;
    }

    /**
     * Get all settlements with pagination, search, and filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Settlement::query()->with(['merchant', 'batch', 'currency']);

            // Search
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('settlement_id', 'like', "%{$search}%")
                      ->orWhereHas('merchant', function ($merchantQuery) use ($search) {
                          $merchantQuery->where('name', 'like', "%{$search}%")
                                      ->orWhere('business_name', 'like', "%{$search}%");
                      });
                });
            }

            // Status filter
            if ($status = $request->input('status')) {
                $query->where('status', $status);
            }

            // Merchant filter
            if ($merchantId = $request->input('merchant_id')) {
                $query->where('merchant_id', $merchantId);
            }

            // Country filter (via merchant's country)
            if ($countryId = $request->input('country_id')) {
                $query->whereHas('merchant', function ($q) use ($countryId) {
                    $q->where('country_id', $countryId);
                });
            }

            // Date range filter
            if ($dateFrom = $request->input('date_from') || $request->input('from_date')) {
                $query->whereDate('settlement_date', '>=', $dateFrom);
            }
            if ($dateTo = $request->input('date_to') || $request->input('to_date')) {
                $query->whereDate('settlement_date', '<=', $dateTo);
            }

            // Pagination
            $perPage = $request->input('per_page', 15);
            $settlements = $query->latest('created_at')->paginate($perPage);

            return $this->SuccessMessage($settlements);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch settlements: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get settlement details
     */
    public function show(int $id): JsonResponse
    {
        try {
            $settlement = Settlement::with([
                'merchant', 
                'batch.transactions',
                'currency'
            ])->findOrFail($id);

            return $this->SuccessMessage($settlement);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch settlement: ' . $e->getMessage(), null, 404);
        }
    }

    /**
     * Get settlement statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $query = Settlement::query();

            // Apply merchant filter if provided
            if ($merchantId = $request->input('merchant_id')) {
                $query->where('merchant_id', $merchantId);
            }

            // Apply date filters if provided
            if ($dateFrom = $request->input('date_from')) {
                $query->whereDate('settlement_date', '>=', $dateFrom);
            }
            if ($dateTo = $request->input('date_to')) {
                $query->whereDate('settlement_date', '<=', $dateTo);
            }

            $stats = [
                'total' => (clone $query)->count(),
                'settled' => (clone $query)->where('status', 'settled')->count(),
                'pending' => (clone $query)->where('status', 'pending')->count(),
                'failed' => (clone $query)->where('status', 'failed')->count(),
                'total_amount' => (clone $query)->where('status', 'settled')->sum('total_amount'),
                'pending_amount' => (clone $query)->where('status', 'pending')->sum('total_amount'),
            ];

            // Format amounts
            $stats['total_amount'] = number_format($stats['total_amount'], 2, '.', '');
            $stats['pending_amount'] = number_format($stats['pending_amount'], 2, '.', '');

            return $this->SuccessMessage($stats);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch statistics: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export settlements
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $query = Settlement::query()->with(['merchant', 'batch', 'currency']);

            // Apply same filters as index
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('settlement_id', 'like', "%{$search}%")
                      ->orWhereHas('merchant', function ($merchantQuery) use ($search) {
                          $merchantQuery->where('name', 'like', "%{$search}%")
                                      ->orWhere('business_name', 'like', "%{$search}%");
                      });
                });
            }

            if ($status = $request->input('status')) {
                $query->where('status', $status);
            }

            if ($merchantId = $request->input('merchant_id')) {
                $query->where('merchant_id', $merchantId);
            }

            if ($dateFrom = $request->input('date_from')) {
                $query->whereDate('settlement_date', '>=', $dateFrom);
            }
            if ($dateTo = $request->input('date_to')) {
                $query->whereDate('settlement_date', '<=', $dateTo);
            }

            $settlements = $query->latest('created_at')->get();

            $exportData = $settlements->map(function ($settlement) {
                return [
                    'Settlement ID' => $settlement->settlement_id ?? 'N/A',
                    'Merchant' => $settlement->merchant->business_name ?? $settlement->merchant->name ?? 'N/A',
                    'Batch Number' => $settlement->batch->batch_number ?? 'N/A',
                    'Status' => ucfirst($settlement->status ?? 'pending'),
                    'Currency' => $settlement->currency->currency_code ?? 'USD',
                    'Total Amount' => number_format($settlement->total_amount, 2),
                    'Settlement Date' => $settlement->settlement_date ? Carbon::parse($settlement->settlement_date)->format('Y-m-d') : 'N/A',
                    'Created At' => $settlement->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return $this->SuccessMessage([
                'data' => $exportData,
                'filename' => 'admin_settlements_export_' . date('Y-m-d_H-i-s') . '.csv'
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to export settlements: ' . $e->getMessage(), null, 500);
        }
    }
}

