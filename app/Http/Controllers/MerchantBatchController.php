<?php

namespace App\Http\Controllers;

use App\Http\Resources\MerchantBatchDetailResource;
use App\Http\Resources\MerchantBatchResource;
use App\Models\Batch;
use App\Models\Merchant;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class MerchantBatchController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $merchant = Auth::user()->merchant;
        
        if (!$merchant) {
            abort(403, 'Merchant not found');
        }

        $statistics = $this->getStatistics($merchant->id);
        
        $has_toolbar = true;
        return view('merchant.batches.index', compact('statistics', 'merchant',  'has_toolbar'));
    }

    public function show(Request $request, Batch $batch)
    {
        $merchant = Auth::guard('external')->user()->merchant;
        
        if (!$merchant || $batch->merchant_id != $merchant->id) {
            abort(403, 'Unauthorized access to batch');
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            $batch->load([
                'merchant:id,name,business_name',
                'transactions',
            ]);

            return response()->json([
                'success' => true,
                'data' => new MerchantBatchDetailResource($batch),
            ]);
        }

        $batch->load(['transactions']);

        $has_toolbar = true;
        return view('merchant.batches.show', compact('batch', 'merchant', 'has_toolbar'));
    }

    public function data(Request $request)
    {
        $merchant = Auth::guard('external')->user()->merchant;
        
        if (!$merchant) {
            return response()->json(['error' => 'Merchant not found'], 403);
        }

        $query = Batch::where('merchant_id', $merchant->id);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Date filters
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        // Handle sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        // Validate sort column (whitelist allowed columns)
        $allowedSortColumns = ['batch_number', 'status', 'total_amount', 'transaction_count', 'created_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }
        
        // Validate sort order
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';
        
        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) $request->input('per_page', 25);
        $batches = $query->paginate($perPage);

        return response()->json([
            'data' => MerchantBatchResource::collection($batches->items()),
            'total' => $batches->total(),
            'per_page' => $batches->perPage(),
            'current_page' => $batches->currentPage(),
            'last_page' => $batches->lastPage(),
        ]);
    }

    public function statistics(Request $request)
    {
        $merchant = Auth::guard('external')->user()->merchant;
        
        if (!$merchant) {
            return response()->json(['error' => 'Merchant not found'], 403);
        }

        $merchantId = $merchant->id;
        
        $total = Batch::where('merchant_id', $merchantId)->count();
        $settled = Batch::where('merchant_id', $merchantId)
            ->where('status', 'settled')
            ->count();
        $pending = Batch::where('merchant_id', $merchantId)
            ->where('status', 'pending')
            ->count();
        $failed = Batch::where('merchant_id', $merchantId)
            ->where('status', 'failed')
            ->count();

        return response()->json([
            'total' => $total,
            'settled' => $settled,
            'pending' => $pending,
            'failed' => $failed
        ]);
    }

    private function getStatistics($merchantId)
    {
        $total = Batch::where('merchant_id', $merchantId)->count();
        $settled = Batch::where('merchant_id', $merchantId)
            ->where('status', 'settled')
            ->count();
        $pending = Batch::where('merchant_id', $merchantId)
            ->where('status', 'pending')
            ->count();
        $failed = Batch::where('merchant_id', $merchantId)
            ->where('status', 'failed')
            ->count();

        return [
            'total' => $total,
            'settled' => $settled,
            'pending' => $pending,
            'failed' => $failed
        ];
    }
}
