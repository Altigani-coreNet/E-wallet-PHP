<?php

namespace App\Support;

use App\Http\Resources\MerchantServiceCatalogResource;
use App\Models\HomeScreenConfiguration;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class ServicesCatalogPublicListing
{
    /**
     * Same partner/merchant scoping as catalog listings (auth user or explicit query params).
     */
    public static function catalogPartnerScopeId(Request $request): ?string
    {
        $user = Auth::user();
        $authenticatedPartnerId = $user?->partner_id ?? $user?->content_provider_id;

        return $authenticatedPartnerId ?: $request->input('partner_id', $request->input('merchant_id'));
    }

    public static function buildCategoryCatalog(Request $request, ?int $serviceLimit = null): Collection
    {
        $partnerId = self::catalogPartnerScopeId($request);

        return ServiceCategory::query()
            ->where('is_active', true)
            ->where('type', ServiceCategory::TYPE_SERVICE)
            ->when($request->filled('category_id'), function ($query) use ($request) {
                $query->where('id', $request->input('category_id'));
            })
            ->whereHas('services', function ($serviceQuery) use ($request, $partnerId) {
                $serviceQuery->where('is_active', true)
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
            })
            ->with([
                'services' => function ($serviceQuery) use ($request, $partnerId, $serviceLimit) {
                    $serviceQuery->where('is_active', true)
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
                        ->when($request->filled('search'), function ($q) use ($request) {
                            $search = (string) $request->input('search');
                            $q->where(function ($searchQuery) use ($search) {
                                $searchQuery->where('id', 'like', "%{$search}%")
                                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(service_name, '$.en')) like ?", ["%{$search}%"])
                                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(service_name, '$.ar')) like ?", ["%{$search}%"]);
                            });
                        })
                        ->whereHas('products', function ($productQuery) {
                            $productQuery->where('status', true);
                        })
                        ->with([
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
                        ->orderBy('id')
                        ->when($serviceLimit !== null, function ($query) use ($serviceLimit) {
                            $query->limit($serviceLimit);
                        });
                },
            ])
            ->orderBy('id')
            ->get();
    }

    public static function applyHomeScreenConfiguration(Collection $categories): Collection
    {
        $configItems = HomeScreenConfiguration::query()
            ->orderBy('sort_order')
            ->get(['configurable_type', 'configurable_id']);

        if ($configItems->isEmpty()) {
            return $categories;
        }

        $selectedCategoryIds = $configItems
            ->where('configurable_type', ServiceCategory::class)
            ->pluck('configurable_id')
            ->values();
        $selectedServiceIds = $configItems
            ->where('configurable_type', Service::class)
            ->pluck('configurable_id')
            ->values();
        $selectedProductIds = $configItems
            ->where('configurable_type', Product::class)
            ->pluck('configurable_id')
            ->values();

        return $categories->map(function (ServiceCategory $category) use ($selectedCategoryIds, $selectedServiceIds, $selectedProductIds) {
            $isCategoryExplicitlySelected = $selectedCategoryIds->contains($category->id);

            $filteredServices = $category->services->map(function (Service $service) use ($isCategoryExplicitlySelected, $selectedServiceIds, $selectedProductIds) {
                $isServiceExplicitlySelected = $selectedServiceIds->contains($service->id);

                $filteredProducts = $service->products->filter(function (Product $product) use ($isCategoryExplicitlySelected, $isServiceExplicitlySelected, $selectedProductIds) {
                    if ($isCategoryExplicitlySelected || $isServiceExplicitlySelected) {
                        return true;
                    }

                    return $selectedProductIds->contains($product->id);
                })->values();

                $hasSelectedProduct = $filteredProducts->isNotEmpty();
                if (! $isCategoryExplicitlySelected && ! $isServiceExplicitlySelected && ! $hasSelectedProduct) {
                    return null;
                }

                $service->setRelation('products', $filteredProducts);

                return $service;
            })->filter()->values();

            if (! $isCategoryExplicitlySelected && $filteredServices->isEmpty()) {
                return null;
            }

            $category->setRelation('services', $filteredServices);

            return $category;
        })->filter()->values();
    }

    /**
     * @param  Collection<int, ServiceCategory>  $categories
     * @return array<int, array<string, mixed>>
     */
    public static function apiPayload(Collection $categories): array
    {
        return $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name_en' => $category->name_en,
                'name_ar' => $category->name_ar,
                'code' => $category->code,
                'category_image' => $category->image ? coreservice_asset($category->image) : coreservice_asset('settings.png'),
                'services' => MerchantServiceCatalogResource::collection($category->services)->resolve(),
            ];
        })->values()->all();
    }
}
