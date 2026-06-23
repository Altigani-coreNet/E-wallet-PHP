<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantCatalogProductResource;
use App\Http\Resources\MerchantServiceCatalogResource;
use App\Http\Resources\MerchantServiceDetailResource;
use App\Models\HomeScreenConfiguration;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Support\ServicesCatalogPublicListing;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MerchantHomeCategoryController extends Controller
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
                'MERCHANT_CATEGORY_HOME_FETCH_ERROR',
                500
            );
        }
    }

    /**
     * Full merchant catalog using category-first hierarchy:
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
                'MERCHANT_CATEGORY_CATALOG_FETCH_ERROR',
                500
            );
        }
    }

    /**
     * Merchant-facing service detail (auth:api). Same visibility rules as /services/home and /services/catalog.
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
                    'MERCHANT_SERVICE_NOT_FOUND',
                    404
                );
            }

            return $this->SuccessMessage([
                'service' => new MerchantServiceDetailResource($service),
            ]);
        } catch (\Throwable $e) {
            return $this->ErrorMessage(
                'Failed to fetch service: ' . $e->getMessage(),
                'MERCHANT_SERVICE_DETAIL_FETCH_ERROR',
                500
            );
        }
    }

    /**
     * V2 Home-screen payload based on home_screen_configurations order:
     * - category => old category structure
     * - service  => direct service resource
     * - product  => direct product resource
     */
    public function homeServicesV2(Request $request): JsonResponse
    {
        try {
            $serviceLimit = (int) $request->input('limit', 5);
            $serviceLimit = max(1, min($serviceLimit, 50));
            $items = $this->configuredItemsPayload($request, $serviceLimit);
            $quickActions = collect($items)->filter(fn (array $item) => (bool) ($item['is_quick_action'] ?? false))->values()->all();

            return $this->SuccessMessage([
                'quick_actions' => $quickActions,
                'items' => $items,
            ]);
        } catch (\Throwable $e) {
            return $this->ErrorMessage(
                'Failed to fetch v2 configured home services: ' . $e->getMessage(),
                'MERCHANT_HOME_V2_FETCH_ERROR',
                500
            );
        }
    }

    /**
     * V2 full catalog payload based on home_screen_configurations order.
     */
    public function catalogV2(Request $request): JsonResponse
    {
        try {
            $items = $this->configuredItemsPayload($request);
            $quickActions = collect($items)->filter(fn (array $item) => (bool) ($item['is_quick_action'] ?? false))->values()->all();

            return $this->SuccessMessage([
                'quick_actions' => $quickActions,
                'items' => $items,
            ]);
        } catch (\Throwable $e) {
            return $this->ErrorMessage(
                'Failed to fetch v2 configured catalog: ' . $e->getMessage(),
                'MERCHANT_CATALOG_V2_FETCH_ERROR',
                500
            );
        }
    }

    /**
     * Fetch HomeScreenConfiguration records ordered by sort_order, eager-load
     * each configurable morph with its full nested relations (type-specific),
     * then map each item to its matching API resource.
     *
     * @return array<int, array<string, mixed>>
     */
    private function configuredItemsPayload(Request $request, ?int $serviceLimit = null): array
    {
        // Step 1 – fetch ordered config rows (full model so morphTo can resolve)
        $configs = HomeScreenConfiguration::query()
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        // Fall back to the default category listing when nothing is configured
        if ($configs->isEmpty()) {
            $categories = ServicesCatalogPublicListing::buildCategoryCatalog($request, $serviceLimit);
            return collect(ServicesCatalogPublicListing::apiPayload($categories))
                ->map(fn (array $category) => array_merge($category, ['type' => 'category', 'is_quick_action' => false]))
                ->values()
                ->all();
        }

        $partnerId = ServicesCatalogPublicListing::catalogPartnerScopeId($request);

        // Step 2 – eager-load the polymorphic `configurable` relation with
        //           type-specific nested relations in a single loadMorph call.
        $configs->loadMorph('configurable', [

            // ── Category ────────────────────────────────────────────────────
            ServiceCategory::class => [
                'services' => function ($serviceQuery) use ($request, $partnerId, $serviceLimit) {
                    $this->applyServiceVisibilityFilters($serviceQuery, $request, $partnerId);
                    $serviceQuery
                        ->with([
                            'products' => fn ($pq) => $pq
                                ->where('status', true)
                                ->with(['serviceForms.fields'])
                                ->orderBy('id'),
                        ])
                        ->orderBy('id')
                        ->when($serviceLimit !== null, fn ($q) => $q->limit($serviceLimit));
                },
            ],

            // ── Service ─────────────────────────────────────────────────────
            Service::class => [
                'category:id,name_en,name_ar,code,image',
                'country:id,name,short_name',
                'partner' => fn ($q) => $q->with(['parentPartner:id,name']),
                'products' => fn ($pq) => $pq
                    ->where('status', true)
                    ->with(['serviceForms.fields'])
                    ->orderBy('id'),
            ],

            // ── Product ─────────────────────────────────────────────────────
            Product::class => [
                'serviceForms.fields',
                'service' => fn ($q) => $q->with([
                    'category:id,name_en,name_ar,code,image',
                    'country:id,name,short_name',
                    'partner' => fn ($pq) => $pq->with(['parentPartner:id,name']),
                ]),
            ],
        ]);

        // Step 3 – map each ordered config item to its resource
        $items = [];
        foreach ($configs as $config) {
            $entity = $config->configurable;

            if (! $entity) {
                continue;
            }

            switch ($config->configurable_type) {

                case ServiceCategory::class:
                    // Skip categories that are inactive or not a service-type category
                    if (! $entity->is_active || $entity->type !== ServiceCategory::TYPE_SERVICE) {
                        break;
                    }
                    $categoryArray = ServicesCatalogPublicListing::apiPayload(collect([$entity]))[0] ?? null;
                    if ($categoryArray) {
                        $items[] = array_merge($categoryArray, [
                            'type' => 'category',
                            'is_quick_action' => (bool) $config->is_quick_action,
                        ]);
                    }
                    break;

                case Service::class:
                    $serviceData = (new MerchantServiceCatalogResource($entity))->resolve();
                    $serviceData['type'] = 'service';
                    $serviceData['is_quick_action'] = (bool) $config->is_quick_action;
                    $items[] = $serviceData;
                    break;

                case Product::class:
                    $productData = (new MerchantCatalogProductResource($entity))->resolve();
                    $productData['type'] = 'product';
                    $productData['is_quick_action'] = (bool) $config->is_quick_action;
                    $items[] = $productData;
                    break;
            }
        }

        return $items;
    }

    private function applyServiceVisibilityFilters(Builder|Relation $query, Request $request, ?string $partnerId): Builder|Relation
    {
        return $query
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
            });
    }
}

