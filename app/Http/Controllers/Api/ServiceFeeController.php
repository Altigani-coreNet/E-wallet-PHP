<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceFee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceFeeController extends Controller
{
    /**
     * Get all service fees with pagination and filters (Read-only for merchants)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ServiceFee::query();

            // Search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('type', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Type filter
            if ($request->has('type') && !empty($request->type)) {
                $query->where('type', $request->type);
            }

            // Date range filters
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Only show active fees to merchants
            $query->where('is_active', true);

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $serviceFees = $query->paginate($perPage);

            // Add sequential IDs
            $feesWithIds = collect($serviceFees->items())->map(function ($fee, $index) use ($serviceFees) {
                $fee->sequential_id = (($serviceFees->currentPage() - 1) * $serviceFees->perPage()) + $index + 1;
                return $fee;
            });

            return response()->json([
                'success' => true,
                'data' => $feesWithIds,
                'pagination' => [
                    'total' => $serviceFees->total(),
                    'per_page' => $serviceFees->perPage(),
                    'current_page' => $serviceFees->currentPage(),
                    'last_page' => $serviceFees->lastPage(),
                    'from' => $serviceFees->firstItem(),
                    'to' => $serviceFees->lastItem(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch service fees',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific service fee
     */
    public function show($id): JsonResponse
    {
        try {
            $serviceFee = ServiceFee::find($id);

            if (!$serviceFee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service fee not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $serviceFee
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch service fee',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available service fee types
     */
    public function types(): JsonResponse
    {
        try {
            $types = ServiceFee::select('type')
                              ->distinct()
                              ->pluck('type');

            return response()->json([
                'success' => true,
                'data' => $types
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch service fee types',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

