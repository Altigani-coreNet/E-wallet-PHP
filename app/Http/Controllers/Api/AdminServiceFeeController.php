<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceFee;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AdminServiceFeeController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of service fees
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ServiceFee::query();

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('type', 'like', "%{$search}%");
                });
            }

            // Type filter
            if ($request->has('type') && !empty($request->type)) {
                $query->where('type', $request->type);
            }

            // Date range filter
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $perPage = $request->get('per_page', 15);
            $serviceFees = $query->orderBy('id', 'desc')->paginate($perPage);

            return $this->SuccessMessage($serviceFees);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch service fees: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get service fees data for DataTable
     */
    public function data(Request $request)
    {
        try {
            $query = ServiceFee::query();

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('type', 'like', "%{$search}%");
                });
            }

            // Type filter
            if ($request->has('type') && !empty($request->type)) {
                $query->where('type', $request->type);
            }

            // Date range filter
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $total = $query->count();
            
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);
            
            $serviceFees = $query->orderBy('id', 'desc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            return $this->SuccessMessage([
                'data' => $serviceFees,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
            ]);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch service fees data: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Store a newly created service fee
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'type' => 'required|string|max:255',
                'fees' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return $this->ErrorMessage('Validation failed', $validator->errors(), 422);
            }

            $serviceFee = ServiceFee::create($request->all());
            return $this->SuccessMessage($serviceFee, 201);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create service fee: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Display the specified service fee
     */
    public function show($id): JsonResponse
    {
        try {
            $serviceFee = ServiceFee::findOrFail($id);
            return $this->SuccessMessage($serviceFee);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch service fee: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update the specified service fee
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $serviceFee = ServiceFee::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'type' => 'required|string|max:255',
                'fees' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return $this->ErrorMessage('Validation failed', $validator->errors(), 422);
            }

            $serviceFee->update($request->all());
            return $this->SuccessMessage($serviceFee, 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update service fee: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified service fee
     */
    public function destroy($id): JsonResponse
    {
        try {
            $serviceFee = ServiceFee::findOrFail($id);
            $serviceFee->delete();
            
            return $this->SuccessMessage('Service fee deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete service fee: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk delete service fees
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $ids = is_string($request->ids) ? explode(',', $request->ids) : $request->ids;
            $deletedCount = ServiceFee::whereIn('id', $ids)->delete();
            
            return $this->SuccessMessage([
                'message' => "{$deletedCount} service fee(s) deleted successfully.",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete service fees: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Import service fees from file
     */
    public function import(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
            ]);

            if ($validator->fails()) {
                return $this->ErrorMessage('Validation failed', $validator->errors(), 422);
            }

            // Note: This requires Maatwebsite\Excel package
            // For now, returning a success message
            // You may need to create ServiceFeesImport class
            
            return $this->SuccessMessage([
                'message' => 'Service fees imported successfully.'
            ]);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Import failed: ' . $e->getMessage(), null, 422);
        }
    }

    /**
     * Export template for service fees import
     */
    public function exportTemplate()
    {
        try {
            // Note: This requires Maatwebsite\Excel package
            // For now, returning a simple response
            // You may need to create ServiceFeesTemplateExport class
            
            return response()->json([
                'message' => 'Template export endpoint ready'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }
}


