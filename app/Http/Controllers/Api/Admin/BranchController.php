<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class BranchController extends Controller
{
    use ApiResponse;

    protected $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $branches = $this->branchService->getAllBranches($request);
            return $this->SuccessMessage($branches);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch branches: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BranchRequest $request): JsonResponse
    {
        try {
            $branch = $this->branchService->create($request->validated());
            return $this->SuccessMessage($branch, 201);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create branch: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Branch $branch): JsonResponse
    {
        try {
            return $this->SuccessMessage($branch);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch branch: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BranchRequest $request, Branch $branch): JsonResponse
    {
        try {
            $updatedBranch = $this->branchService->update($branch, $request->validated());
            return $this->SuccessMessage($updatedBranch);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update branch: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch): JsonResponse
    {
        try {
            $this->branchService->delete($branch);
            return $this->SuccessMessage(['message' => 'Branch deleted successfully']);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete branch: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get branches data for DataTables
     */
    public function data(Request $request): JsonResponse
    {
        try {
            $data = $this->branchService->getDataTableData($request);
            return $this->SuccessMessage($data);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch branches data: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get branches for select dropdown
     */
    public function select(Request $request): JsonResponse
    {
        try {
            $branches = $this->branchService->getBranchesForSelect($request);
            return $this->SuccessMessage($branches);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch branches for select: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get branches by merchant
     */
    public function getByMerchant(Request $request): JsonResponse
    {
        try {
            $request->validate(['merchant_id' => 'required|exists:merchants,id']);
            $branches = $this->branchService->getByMerchant($request->merchant_id);
            return $this->SuccessMessage($branches);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch branches by merchant: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get active branches by merchant
     */
    public function getActiveByMerchant(Request $request): JsonResponse
    {
        try {
            $request->validate(['merchant_id' => 'required|exists:merchants,id']);
            $branches = $this->branchService->getActiveByMerchant($request->merchant_id);
            return $this->SuccessMessage($branches);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch active branches by merchant: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk delete branches
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:branches,id'
            ]);

            $this->branchService->bulkDelete($request->ids);
            return $this->SuccessMessage(['message' => 'Branches deleted successfully']);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete branches: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Import branches
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv'
            ]);

            $result = $this->branchService->import($request->file('file'));
            return $this->SuccessMessage($result);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to import branches: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export template
     */
    public function exportTemplate(): JsonResponse
    {
        try {
            $template = $this->branchService->exportTemplate();
            return $this->SuccessMessage($template);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to export template: ' . $e->getMessage(), null, 500);
        }
    }
} 