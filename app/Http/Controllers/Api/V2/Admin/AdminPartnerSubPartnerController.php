<?php

namespace App\Http\Controllers\Api\V2\Admin;

use App\Http\Controllers\Controller;
use App\Services\PartnerService as ContentProviderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Sub-partners under a root parent partner (inherits country & category from parent).
 */
class AdminPartnerSubPartnerController extends Controller
{
    use ApiResponse;

    public function __construct(protected ContentProviderService $contentProviderService)
    {
    }

    public function index(Request $request, string $parentId): JsonResponse
    {
        try {
            $request->merge(['parent_id' => $parentId]);

            return $this->SuccessMessage($this->contentProviderService->index($request));
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch sub-partners: ' . $e->getMessage(), null, 500);
        }
    }

    public function store(Request $request, string $parentId): JsonResponse
    {
        try {
            $partner = $this->contentProviderService->storeSubPartner($request, $parentId);

            return $this->SuccessMessage($partner, 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create sub-partner: ' . $e->getMessage(), null, 500);
        }
    }
}
