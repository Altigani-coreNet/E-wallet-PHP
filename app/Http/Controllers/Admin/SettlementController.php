<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Models\Batch;
use App\Repositories\SettlementRepository;
use App\Services\SettlementService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SettlementController extends Controller
{
    protected $settlementRepository;
    protected $settlementService;

    public function __construct(SettlementRepository $settlementRepository, SettlementService $settlementService)
    {
        $this->settlementRepository = $settlementRepository;
        $this->settlementService = $settlementService;
    }

    /**
     * Display a listing of settlements.
     */
    public function index(Request $request): View
    {
        $perPage = $request->get('per_page', 15);
        $merchantId = $request->get('merchant_id');
        $status = $request->get('status');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = Settlement::withCountry()->with(['batch', 'merchant']);

        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $settlements = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return view('admin.settlements.index', compact('settlements'));
    }

    /**
     * Get settlements data for DataTables.
     */
    public function data(Request $request)
    {
        $query = Settlement::with(['batch', 'merchant', 'country', 'currency']);

        // Handle search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('settlement_number', 'like', "%{$search}%")
                  ->orWhereHas('batch', function($batchQuery) use ($search) {
                      $batchQuery->where('batch_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('merchant', function($merchantQuery) use ($search) {
                      $merchantQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        if ($request->has('country_id') && !empty($request->country_id)) {
            $query->where('country_id', $request->country_id);
        }

        if ($request->has('from_date') && !empty($request->from_date)) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && !empty($request->to_date)) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        return \Yajra\DataTables\DataTables::of($query)
            ->addColumn('record_select', fn($settlement) => view('admin.settlements.data_table.record_select', compact('settlement'))->render())
            ->addColumn('batch_number', fn($settlement) => view('admin.settlements.data_table.batch_number', compact('settlement'))->render())
            ->addColumn('merchant_name', fn($settlement) => view('admin.settlements.data_table.merchant_name', compact('settlement'))->render())
            ->addColumn('actions', fn($settlement) => view('admin.settlements.data_table.actions', compact('settlement'))->render())
            ->editColumn('settlement_number', fn($settlement) => '<a href="' . route('admin.settlements.show', $settlement) . '" class="text-dark fw-bold text-hover-primary fs-6">' . $settlement->settlement_number . '</a>')
            ->editColumn('status', fn($settlement) => $settlement->getStatusWithSpan())
            ->editColumn('total_amount', fn($settlement) => ($settlement->currency ? $settlement->currency->currency_code : 'USD') . ' ' . number_format($settlement->total_amount, 2))
            ->editColumn('transaction_count', fn($settlement) => $settlement->transaction_count)
            ->editColumn('created_at', fn($settlement) => $settlement->created_at->format('M d, Y'))
            ->editColumn('country', fn($settlement) => $settlement->country ? $settlement->country->name : 'N/A')
            ->rawColumns(['record_select', 'batch_number', 'merchant_name', 'actions', 'status', 'settlement_number'])
            ->toJson();
    }



    /**
     * Display the specified settlement.
     */
    public function show(Settlement $settlement): View
    {
        $settlement->load(['batch', 'merchant', 'currency']);
        
        return view('admin.settlements.show', compact('settlement'));
    }

    /**
     * Show settlements for a specific batch.
     */
    public function byBatch(Batch $batch): View
    {
        $settlements = $this->settlementService->getSettlementsByBatch($batch->id);
        
        return view('admin.settlements.by_batch', compact('batch', 'settlements'));
    }

    /**
     * Process settlement for a batch.
     */
    public function processSettlement(Batch $batch)
    {
        try {
            $settlement = $this->settlementService->processSettlement($batch);
            
            return redirect()->back()->with('success', 'Settlement processed successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to process settlement: ' . $e->getMessage());
        }
    }

    /**
     * Mark settlement as settled.
     */
    public function markAsSettled(Settlement $settlement)
    {
        try {
            $this->settlementService->markAsSettled($settlement);
            
            return redirect()->back()->with('success', 'Settlement marked as settled!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update settlement: ' . $e->getMessage());
        }
    }

    /**
     * Mark settlement as failed.
     */
    public function markAsFailed(Settlement $settlement)
    {
        try {
            $this->settlementService->markAsFailed($settlement);
            
            return redirect()->back()->with('success', 'Settlement marked as failed!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update settlement: ' . $e->getMessage());
        }
    }

    /**
     * Export settlements as CSV with current filters
     */
    public function export(Request $request)
    {
        $filters = $request->only(['search', 'status', 'country_id', 'from_date', 'to_date']);

        $query = Settlement::with(['batch', 'merchant', 'country', 'currency']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('settlement_number', 'like', "%{$search}%")
                  ->orWhereHas('batch', function ($batchQuery) use ($search) {
                      $batchQuery->where('batch_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('merchant', function ($merchantQuery) use ($search) {
                      $merchantQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['country_id'])) {
            $query->where('country_id', $filters['country_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $filename = 'settlements_' . date('Y-m-d_H-i-s') . '.csv';

        // Optional logging for audit trail
        Log::info('Settlement export requested', [
            'filters' => $filters,
            'user_id' => Auth::id() ?? 'guest'
        ]);

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // CSV header
            fputcsv($handle, [
                'ID',
                'Settlement Number',
                'Batch Number',
                'Merchant',
                'Status',
                'Total Amount',
                'Transaction Count',
                'Country',
                'Created At',
                'Settled At',
            ]);

            $query->orderBy('created_at', 'desc')->chunk(1000, function ($settlements) use ($handle) {
                foreach ($settlements as $settlement) {
                    fputcsv($handle, [
                        $settlement->id,
                        $settlement->settlement_number,
                        optional($settlement->batch)->batch_number,
                        optional($settlement->merchant)->name,
                        $settlement->status,
                        $settlement->total_amount,
                        $settlement->transaction_count,
                        optional($settlement->country)->name,
                        optional($settlement->created_at)?->format('Y-m-d H:i:s'),
                        optional($settlement->settled_at)?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
