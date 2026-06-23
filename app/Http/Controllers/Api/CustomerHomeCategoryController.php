<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantServiceDetailResource;
use App\Models\Service;
use App\Support\ServicesCatalogPublicListing;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerHomeCategoryController extends Controller
{
    use ApiResponse;

    /**
     * Home-screen catalog using category-first hierarchy:
     * categories -> services -> products -> product_forms -> fields.
     */
    public function homeServices(Request $request): JsonResponse
    {
        try {
            $serviceLimit = (int) $request->input('limit', 5);
            $serviceLimit = max(1, min($serviceLimit, 50));

            $categories = ServicesCatalogPublicListing::buildCategoryCatalog($request, $serviceLimit);
            $categories = ServicesCatalogPublicListing::applyHomeScreenConfiguration($categories);
            $responseCategories = ServicesCatalogPublicListing::apiPayload($categories);
            $quickActionCategory = collect($responseCategories)
                ->sortByDesc(function (array $category) {
                    return count($category['services'] ?? []);
                })
                ->first();

            return $this->SuccessMessage([
                'categories' => $responseCategories,
                'quick_action' => $quickActionCategory,
            ]);
        } catch (\Throwable $e) {
            return $this->ErrorMessage(
                'Failed to fetch category home services catalog: ' . $e->getMessage(),
                'CUSTOMER_CATEGORY_HOME_FETCH_ERROR',
                500
            );
        }
    }

    /**
     * Full customer catalog using category-first hierarchy:
     * categories -> services -> products -> product_forms -> fields.
     */
    public function catalog(Request $request): JsonResponse
    {
        try {
            $categories = ServicesCatalogPublicListing::buildCategoryCatalog($request);
            $responseCategories = ServicesCatalogPublicListing::apiPayload($categories);

            return $this->SuccessMessage([
                'categories' => $responseCategories,
            ]);
        } catch (\Throwable $e) {
            return $this->ErrorMessage(
                'Failed to fetch category services catalog: ' . $e->getMessage(),
                'CUSTOMER_CATEGORY_CATALOG_FETCH_ERROR',
                500
            );
        }
    }

    /**
     * Customer-facing service detail (customer.jwt). Same visibility rules as /services/home and /services/catalog.
     */
    public function serviceDetails(Request $request, string $id): JsonResponse
    {
        try {
            $partnerId = ServicesCatalogPublicListing::catalogPartnerScopeId($request);

            $service = Service::query()
                ->where('id', $id)
                ->where('is_active', true)
                ->where('status', 'active')
                ->whereHas('partner', function ($partnerQuery) {
                    $partnerQuery->where('is_active', true)
                        ->where('status', 'approved');
                })
                ->when($partnerId, function ($q) use ($partnerId) {
                    $q->where('partner_id', $partnerId);
                })
                ->when($request->filled('country_id'), function ($q) use ($request) {
                    $q->where('country_id', $request->input('country_id'));
                })
                ->when($request->filled('service_type'), function ($q) use ($request) {
                    $q->where('service_type', $request->input('service_type'));
                })
                ->whereHas('products', function ($productQuery) {
                    $productQuery->where('status', true);
                })
                ->with([
                    'category:id,name_en,name_ar,code,image',
                    'products' => function ($productQuery) {
                        $productQuery->where('status', true)
                            ->with([
                                'serviceForms' => function ($formQuery) {
                                    $formQuery->with('fields');
                                },
                            ])
                            ->orderBy('id');
                    },
                ])
                ->first();

            if (! $service) {
                return $this->ErrorMessage(
                    'Service not found or not available.',
                    'CUSTOMER_SERVICE_NOT_FOUND',
                    404
                );
            }

            return $this->SuccessMessage([
                'service' => new MerchantServiceDetailResource($service),
            ]);
        } catch (\Throwable $e) {
            return $this->ErrorMessage(
                'Failed to fetch service: ' . $e->getMessage(),
                'CUSTOMER_SERVICE_DETAIL_FETCH_ERROR',
                500
            );
        }
    }
}
