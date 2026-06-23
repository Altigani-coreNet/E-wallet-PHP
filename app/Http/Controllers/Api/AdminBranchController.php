<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\BranchStatusUpdateMail;
use App\Models\Branch;
use App\Models\Merchant;
use App\Services\AdminBranchService;
use App\Traits\ApiResponse;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\Mail;

class AdminBranchController extends Controller
{
    use ApiResponse;

    protected $branchService;

    public function __construct(AdminBranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    /**
     * Get all branches with pagination, search, and filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $branches = $this->branchService->index($request);
            return $this->SuccessMessage($branches);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch branches: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get branch details
     */
    public function show(string $id): JsonResponse
    {
        try {
            $data = $this->branchService->show($id);
            return $this->SuccessMessage($data);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch branch: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Create a new branch
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $branch = $this->branchService->store($request);
            return $this->SuccessMessage($branch, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->ErrorMessage('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create branch: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update branch
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $branch = $this->branchService->update($request, $id);
            return $this->SuccessMessage($branch);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->ErrorMessage('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update branch: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Delete branch
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->branchService->destroy($id);
            return $this->SuccessMessage(['message' => 'Branch deleted successfully']);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete branch: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get branch statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->branchService->statistics();
            return $this->SuccessMessage($stats);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch statistics: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export branches
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $data = $this->branchService->export($request);
            return $this->SuccessMessage($data);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to export branches: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk delete branches
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:branches,id'
            ]);

            $result = $this->branchService->bulkDelete($validated['ids']);
            return $this->SuccessMessage($result);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete branches: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export template for import
     */
    public function exportTemplate()
    {
        try {
            return $this->branchService->exportTemplate();
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to generate template: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Preview import data
     */
    public function importPreview(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
            ]);

            $file = $request->file('import_file');
            $preview = $this->branchService->importPreview($file);
            
            return $this->SuccessMessage($preview);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to preview import: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Import branches from file
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
            ]);

            $result = $this->branchService->import($request);
            return $this->SuccessMessage($result);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to import branches: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Approve branch
     */
    public function approve(string $id): JsonResponse
    {
        try {
            $branch = Branch::with('merchant')->findOrFail($id);
            $oldStatus = $branch->status ?? null;

            $result = $this->branchService->approve($id);

            $branch->refresh();
            $merchant = $branch->merchant ?? Merchant::find($branch->merchant_id);
            try {
                if ($merchant) {
                    Mail::to($merchant->email)->send(new BranchStatusUpdateMail($branch, $merchant, $oldStatus, $branch->status ?? 'approved'));
                }
            } catch (\Throwable $mailError) {
                \Log::warning('Failed to send BranchStatusUpdateMail (approve): ' . $mailError->getMessage());
            }
            return $this->SuccessMessage($result);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to approve branch: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Reject branch
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        try {
            $rejectionReason = $request->input('rejection_reason', '');
            $invalidFields = $request->input('invalid_fields', []);
            
            $branch = Branch::with('merchant')->findOrFail($id);
            $oldStatus = $branch->status ?? null;

            $result = $this->branchService->reject($id, $rejectionReason, $invalidFields);

            $branch->refresh();
            $merchant = $branch->merchant ?? Merchant::find($branch->merchant_id);
            try {
                if ($merchant) {
                    Mail::to($merchant->email)->send(new BranchStatusUpdateMail($branch, $merchant, $oldStatus, $branch->status ?? 'rejected'));
                }
            } catch (\Throwable $mailError) {
                \Log::warning('Failed to send BranchStatusUpdateMail (reject): ' . $mailError->getMessage());
            }
            return $this->SuccessMessage($result);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to reject branch: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Suspend branch
     */
    public function suspend(Request $request, string $id): JsonResponse
    {
        try {
            $suspensionReason = $request->input('suspension_reason', '');
            
            $branch = Branch::with('merchant')->findOrFail($id);
            $oldStatus = $branch->status ?? null;

            $result = $this->branchService->suspend($id, $suspensionReason);

            $branch->refresh();
            $merchant = $branch->merchant ?? Merchant::find($branch->merchant_id);
            try {
                if ($merchant) {
                    Mail::to($merchant->email)->send(new BranchStatusUpdateMail($branch, $merchant, $oldStatus, $branch->status ?? 'suspended'));
                }
            } catch (\Throwable $mailError) {
                \Log::warning('Failed to send BranchStatusUpdateMail (suspend): ' . $mailError->getMessage());
            }
            return $this->SuccessMessage($result);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to suspend branch: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Unsuspend branch
     */
    public function unsuspend(string $id): JsonResponse
    {
        try {
            $branch = Branch::with('merchant')->findOrFail($id);
            $oldStatus = $branch->status ?? null;

            $result = $this->branchService->unsuspend($id);

            $branch->refresh();
            $merchant = $branch->merchant ?? Merchant::find($branch->merchant_id);
            try {
                if ($merchant) {
                    Mail::to($merchant->email)->send(new BranchStatusUpdateMail($branch, $merchant, $oldStatus, $branch->status ?? 'approved'));
                }
            } catch (\Throwable $mailError) {
                \Log::warning('Failed to send BranchStatusUpdateMail (unsuspend): ' . $mailError->getMessage());
            }
            return $this->SuccessMessage($result);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to unsuspend branch: ' . $e->getMessage(), null, 500);
        }
    }
}
