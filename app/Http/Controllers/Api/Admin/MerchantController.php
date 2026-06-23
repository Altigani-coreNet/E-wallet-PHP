<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantRequest;
use App\Models\Merchant;
use App\Services\MerchantService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class MerchantController extends Controller
{
    use ApiResponse;



    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $merchants = $this->merchantService->getAllMerchants($request);
            return $this->SuccessMessage($merchants);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch merchants: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MerchantRequest $request): JsonResponse
    {
        try {
            $merchant = $this->merchantService->create($request->validated());
            return $this->SuccessMessage($merchant, 201);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create merchant: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Merchant $merchant): JsonResponse
    {
        try {
            return $this->SuccessMessage($merchant);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch merchant: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MerchantRequest $request, Merchant $merchant): JsonResponse
    {
        try {
            $updatedMerchant = $this->merchantService->update($merchant, $request->validated());
            return $this->SuccessMessage($updatedMerchant);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update merchant: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Merchant $merchant): JsonResponse
    {
        try {
            $this->merchantService->delete($merchant);
            return $this->SuccessMessage(['message' => 'Merchant deleted successfully']);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete merchant: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get merchants data for DataTables
     */
    public function data(Request $request): JsonResponse
    {
        try {
            $data = $this->merchantService->getDataTableData($request);
            return $this->SuccessMessage($data);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch merchants data: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get merchants for select dropdown
     */
    public function select(Request $request): JsonResponse
    {
        try {
            $merchants = $this->merchantService->getMerchantsForSelect($request);
            return $this->SuccessMessage($merchants);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch merchants for select: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk delete merchants
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:merchants,id'
            ]);

            $this->merchantService->bulkDelete($request->ids);
            return $this->SuccessMessage(['message' => 'Merchants deleted successfully']);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete merchants: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Import merchants
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv'
            ]);

            $result = $this->merchantService->import($request->file('file'));
            return $this->SuccessMessage($result);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to import merchants: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export template
     */
    public function exportTemplate(): JsonResponse
    {
        try {
            $template = $this->merchantService->exportTemplate();
            return $this->SuccessMessage($template);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to export template: ' . $e->getMessage(), null, 500);
        }
    }
} 