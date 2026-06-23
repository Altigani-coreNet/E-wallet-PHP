<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\BranchService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
{
    use ApiResponse;

    protected $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    /**
     * Get all branches with pagination and filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $merchantId = auth()->user()->merchant_id;
            
            if (!$merchantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with a merchant'
                ], 403);
            }

            $query = Branch::where('merchant_id', $merchantId);

            // Search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%");
                });
            }

            // Status filter
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Date range filters
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $branches = $query->paginate($perPage);

            // Add sequential IDs to the items
            $branchesWithIds = collect($branches->items())->map(function ($branch, $index) use ($branches) {
                $branch->sequential_id = (($branches->currentPage() - 1) * $branches->perPage()) + $index + 1;
                return $branch;
            });

            return response()->json([
                'success' => true,
                'data' => $branchesWithIds,
                'pagination' => [
                    'total' => $branches->total(),
                    'per_page' => $branches->perPage(),
                    'current_page' => $branches->currentPage(),
                    'last_page' => $branches->lastPage(),
                    'from' => $branches->firstItem(),
                    'to' => $branches->lastItem(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branches',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new branch
     */
    public function store(BranchRequest $request): JsonResponse
    {
        try {
            $merchantId = auth()->user()->merchant_id;
            
            if (!$merchantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with a merchant'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'address' => 'nullable|string|max:1000',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Enforce plan scope & limit for branches
            // Total branches already created for this merchant
            $currentCount = Branch::where('merchant_id', $merchantId)->count();

            // If merchant is not allowed to create another branch, return 406
            if (!User::merchantCanCreateBranch($merchantId, $currentCount)) {
                return $this->ErrorMessage(
                    'Branch limit reached for your current plan.',
                    'PLAN_BRANCHES_LIMIT_REACHED',
                    406
                );
            }

            $branch = $this->branchService->store($request, $merchantId);

            return response()->json([
                'success' => true,
                'message' => 'Branch request submitted successfully. It will be reviewed by administrators.',
                'data' => $branch
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create branch',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific branch
     */
    public function show($id): JsonResponse
    {
        try {
            $merchantId = auth()->user()->merchant_id;
            
            $branch = Branch::where('id', $id)
                           ->where('merchant_id', $merchantId)
                           ->first();

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch not found or access denied'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $branch
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branch',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a branch
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $merchantId = auth()->user()->merchant_id;
            
            $branch = Branch::where('id', $id)
                           ->where('merchant_id', $merchantId)
                           ->first();

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch not found or access denied'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'address' => 'nullable|string|max:1000',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updatedBranch = $this->branchService->update($request, $branch);

            return response()->json([
                'success' => true,
                'message' => 'Branch updated successfully',
                'data' => $updatedBranch
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update branch',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a branch
     */
    public function destroy($id): JsonResponse
    {
        try {
            $merchantId = auth()->user()->merchant_id;
            
            $branch = Branch::where('id', $id)
                           ->where('merchant_id', $merchantId)
                           ->first();

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch not found or access denied'
                ], 404);
            }

            $this->branchService->destroy($branch);

            return response()->json([
                'success' => true,
                'message' => 'Branch deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete branch',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete branches
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $merchantId = auth()->user()->merchant_id;
            
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $this->branchService->bulkDelete($request->ids, $merchantId);

            return response()->json([
                'success' => true,
                'message' => 'Branches deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete branches',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get branches for select dropdown
     */
    public function select(Request $request): JsonResponse
    {
        try {
            $merchantId = auth()->user()->merchant_id;
            
            $query = Branch::where('merchant_id', $merchantId)
                          ->where('status', '!=', 'pending'); // Don't show pending branches

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%");
                });
            }

            $branches = $query->select('id', 'name', 'address', 'is_active')
                             ->limit(50)
                             ->get();

            return response()->json([
                'success' => true,
                'data' => $branches
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branches',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import branches from file
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $merchantId = auth()->user()->merchant_id;
            
            $validator = Validator::make($request->all(), [
                'import_file' => 'required|file|mimes:xlsx,xls,csv|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->branchService->import($request->file('import_file'), $merchantId);

            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Branches imported successfully',
                'imported_count' => $result['imported_count'] ?? 0,
                'errors' => $result['errors'] ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview import data
     */
    public function importPreview(Request $request): JsonResponse
    {
        try {
            $merchantId = auth()->user()->merchant_id;
            
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:csv,txt,xlsx,xls'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->branchService->importPreview($request->file('file'), $merchantId);

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'errors' => $result['errors'] ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to preview import: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Export template
     */
    public function exportTemplate()
    {
        try {
            $merchantId = auth()->user()->merchant_id;
            return $this->branchService->exportTemplate($merchantId);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export branches data
     */
    public function export(Request $request)
    {
        try {
            $merchantId = auth()->user()->merchant_id;
            
            $filters = [
                'search' => $request->get('search'),
                'status' => $request->get('status'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ];

            return $this->branchService->export($filters, $merchantId);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get branches by IDs (for SoftPos to display branch names in terminals)
     */
    public function byIds(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:branches,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $merchantId = auth()->user()->merchant_id;
            
            if (!$merchantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with a merchant'
                ], 403);
            }

            // Fetch branches by IDs that belong to the authenticated merchant
            $branches = Branch::where('merchant_id', $merchantId)
                ->whereIn('id', $request->ids)
                ->select('id', 'name', 'address', 'status')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $branches
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branches',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}


