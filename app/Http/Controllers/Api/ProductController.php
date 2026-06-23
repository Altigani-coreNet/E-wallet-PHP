<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Service;
use App\Services\ProductServiceFormsSyncService;
use App\Traits\BroadcastsServicesListingUpdate;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    use HasFiles, BroadcastsServicesListingUpdate;

    private function normalizeScalar($value): ?string
    {
        if (is_array($value) || is_object($value)) {
            return null;
        }
        $value = is_string($value) ? trim($value) : $value;
        return ($value === '' || $value === null) ? null : (string) $value;
    }

    private function normalizeNameTranslations($namePayload): array
    {
        if (is_string($namePayload)) {
            return ['en' => trim($namePayload), 'ar' => ''];
        }
        if (!is_array($namePayload)) {
            return ['en' => '', 'ar' => ''];
        }

        return [
            'en' => is_scalar($namePayload['en'] ?? null) ? trim((string) ($namePayload['en'] ?? '')) : '',
            'ar' => is_scalar($namePayload['ar'] ?? null) ? trim((string) ($namePayload['ar'] ?? '')) : '',
        ];
    }

    /**
     * Apply filters shared by index() and export().
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Product>  $query
     */
    private function applyProductListFilters($query, Request $request): void
    {
        $user = Auth::user();
        $authenticatedPartnerId = $user?->partner_id ?? $user?->content_provider_id;
        if ($authenticatedPartnerId && ! $request->has('partner_id') && ! $request->has('content_provider_id') && ! $request->has('merchant_id')) {
            $query->whereHas('service', function ($q) use ($authenticatedPartnerId) {
                $q->where('partner_id', $authenticatedPartnerId);
            });
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('service_url', 'like', "%{$search}%")
                    ->orWhere('notify_url', 'like', "%{$search}%")
                    ->orWhere('prepay_url', 'like', "%{$search}%")
                    ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(name, \'$.en\')) like ?', ["%{$search}%"])
                    ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(name, \'$.ar\')) like ?', ["%{$search}%"]);
            });
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->input('service_id'));
        }

        if ($request->filled('country_id')) {
            $countryId = $request->input('country_id');
            $query->where(function ($q) use ($countryId) {
                $q->where('country_id', $countryId)
                    ->orWhereHas('service', function ($sq) use ($countryId) {
                        $sq->where('country_id', $countryId);
                    });
            });
        }

        if ($request->filled('merchant_id')) {
            $partnerId = $request->input('merchant_id');
            $query->whereHas('service', function ($q) use ($partnerId) {
                $q->where('partner_id', $partnerId);
            });
        }

        if ($request->filled('partner_id') && ! $authenticatedPartnerId) {
            $query->whereHas('service', function ($q) use ($request) {
                $q->where('partner_id', $request->input('partner_id'));
            });
        }

        if ($request->filled('content_provider_id') && ! $authenticatedPartnerId) {
            $query->whereHas('service', function ($q) use ($request) {
                $q->where('partner_id', $request->input('content_provider_id'));
            });
        }

        if ($request->filled('service_type')) {
            $query->whereHas('service', function ($q) use ($request) {
                $q->where('service_type', $request->input('service_type'));
            });
        }

        if ($request->has('is_active')) {
            $query->where('status', $request->boolean('is_active'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
    }

    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with(['service.partner.parentPartner', 'service.country', 'country'])
            ->withCount('serviceForms');

        $this->applyProductListFilters($query, $request);

        $allowedSort = ['created_at', 'updated_at', 'id', 'status'];
        $sortBy = $request->input('sort_by', 'created_at');
        if (! in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'created_at';
        }
        $sortOrder = strtolower((string) $request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = min(100, max(1, (int) $request->input('per_page', 15)));
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Export products (same filters as index).
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $query = Product::query()
                ->with(['service.partner.parentPartner', 'service.country', 'country'])
                ->withCount('serviceForms');

            $this->applyProductListFilters($query, $request);

            $products = $query->orderBy('created_at', 'desc')->get();

            $data = $products->map(function ($product) {
                $nameEn = $product->getTranslation('name', 'en', false) ?? '';
                $nameAr = $product->getTranslation('name', 'ar', false) ?? '';

                $countryName = $product->country?->name;
                if (is_array($countryName)) {
                    $countryName = $countryName['en'] ?? $countryName['ar'] ?? '';
                }
                if ($countryName === null || $countryName === '') {
                    $c = $product->service?->country?->name;
                    $countryName = is_array($c) ? ($c['en'] ?? $c['ar'] ?? 'N/A') : ($c ?? 'N/A');
                }

                return [
                    'ID' => $product->id,
                    'Product Name (EN)' => $nameEn,
                    'Product Name (AR)' => $nameAr,
                    'Service UUID' => $product->service?->id ?? 'N/A',
                    'Service Name' => $product->service?->serviceNameForLocale('en') ?? 'N/A',
                    'Partner' => $product->service?->partner?->name ?? 'N/A',
                    'Country' => is_string($countryName) ? $countryName : 'N/A',
                    'Forms Count' => $product->service_forms_count ?? 0,
                    'Active' => $product->status ? 'Yes' : 'No',
                    'Created At' => $product->created_at?->toDateTimeString(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export products.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created product.
     */
    public function store(ProductStoreRequest $request): JsonResponse
    {
        try {
            $payload = $request->validated();
            $formsRaw = $payload['forms'] ?? null;
            unset($payload['forms']);

            $product = DB::transaction(function () use ($request, $payload, $formsRaw) {
                // Derive country_id from service when not provided
                if (empty($payload['country_id']) && ! empty($payload['service_id'])) {
                    $service = Service::select(['id', 'country_id'])->find($payload['service_id']);
                    if ($service?->country_id) {
                        $payload['country_id'] = $service->country_id;
                    }
                }

                // Handle image upload (multipart) before persisting product
                if ($request->hasFile('image')) {
                    // Keep product uploads consistent with ServiceController image strategy
                    $payload['image'] = $this->uploadImageAndGetFileName($request, 'image', 'uploads/products');
                }

                $namePayload = $payload['name'] ?? null;
                unset($payload['name']);
                $descriptionPayload = $payload['description'] ?? null;
                unset($payload['description']);

                $created = new Product();
                $created->service_id = $this->normalizeScalar($payload['service_id'] ?? null);
                $created->service_sub_category_id = $this->normalizeScalar($payload['service_sub_category_id'] ?? null);
                $created->type_id = $this->normalizeScalar($payload['type_id'] ?? null);
                $created->country_id = $this->normalizeScalar($payload['country_id'] ?? null);
                $created->service_url = $this->normalizeScalar($payload['service_url'] ?? null);
                $created->notify_url = $this->normalizeScalar($payload['notify_url'] ?? null);
                $created->prepay_url = $this->normalizeScalar($payload['prepay_url'] ?? null);
                $created->image = $this->normalizeScalar($payload['image'] ?? null);
                $created->status = array_key_exists('status', $payload) ? (bool) $payload['status'] : true;
                $created->setTranslations('name', $this->normalizeNameTranslations($namePayload));
                $created->setTranslations('description', $this->normalizeNameTranslations($descriptionPayload));
                $created->save();

                if (is_array($formsRaw) && count($formsRaw) > 0) {
                    $normalized = ProductServiceFormsSyncService::normalizeFromBuilder($formsRaw);
                    if (count($normalized) > 0) {
                        app(ProductServiceFormsSyncService::class)->sync($created, $normalized);
                    }
                }

                return $created->fresh();
            });

            $product->load(['service', 'serviceForms.fields']);
            $this->broadcastServicesListingUpdate();

            return response()->json($this->appendServicesListingBroadcastWarning([
                'success' => true,
                'message' => 'Product created successfully.',
                'data' => new ProductResource($product),
            ]), 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified product.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $product = Product::with(['service', 'serviceForms.fields'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new ProductResource($product),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }
    }

    /**
     * Update the specified product.
     */
    public function update(ProductUpdateRequest $request, string $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $payload = $request->validated();
            $shouldSyncForms = $request->exists('forms');
            $formsRaw = $payload['forms'] ?? null;
            unset($payload['forms']);

            $product = DB::transaction(function () use ($request, $product, $payload, $shouldSyncForms, $formsRaw) {
                // Derive country_id from service when not provided (service_id may be updated)
                $serviceId = $payload['service_id'] ?? $product->service_id;
                if (empty($payload['country_id']) && ! empty($serviceId)) {
                    $service = Service::select(['id', 'country_id'])->find($serviceId);
                    if ($service?->country_id) {
                        $payload['country_id'] = $service->country_id;
                    }
                }

                // Handle image upload (multipart)
                if ($request->hasFile('image')) {
                    $payload['image'] = $this->uploadImageAndGetFileName($request, 'image', 'uploads/products');
                }

                $namePayload = $payload['name'] ?? null;
                unset($payload['name']);
                $descriptionPayload = array_key_exists('description', $payload) ? $payload['description'] : null;
                unset($payload['description']);

                if (array_key_exists('service_id', $payload)) $product->service_id = $this->normalizeScalar($payload['service_id']);
                if (array_key_exists('service_sub_category_id', $payload)) $product->service_sub_category_id = $this->normalizeScalar($payload['service_sub_category_id']);
                if (array_key_exists('type_id', $payload)) $product->type_id = $this->normalizeScalar($payload['type_id']);
                if (array_key_exists('country_id', $payload)) $product->country_id = $this->normalizeScalar($payload['country_id']);
                if (array_key_exists('service_url', $payload)) $product->service_url = $this->normalizeScalar($payload['service_url']);
                if (array_key_exists('notify_url', $payload)) $product->notify_url = $this->normalizeScalar($payload['notify_url']);
                if (array_key_exists('prepay_url', $payload)) $product->prepay_url = $this->normalizeScalar($payload['prepay_url']);
                if (array_key_exists('image', $payload)) $product->image = $this->normalizeScalar($payload['image']);
                if (array_key_exists('status', $payload)) $product->status = (bool) $payload['status'];
                if ($namePayload !== null) {
                    $product->setTranslations('name', $this->normalizeNameTranslations($namePayload));
                }
                if ($descriptionPayload !== null) {
                    $product->setTranslations('description', $this->normalizeNameTranslations($descriptionPayload));
                }
                $product->save();

                $product = $product->fresh();

                if ($shouldSyncForms && is_array($formsRaw)) {
                    $normalized = ProductServiceFormsSyncService::normalizeFromBuilder($formsRaw);
                    app(ProductServiceFormsSyncService::class)->sync($product, $normalized);
                    $product = $product->fresh();
                }

                return $product;
            });

            $product->load(['service', 'serviceForms.fields']);
            $this->broadcastServicesListingUpdate();

            return response()->json($this->appendServicesListingBroadcastWarning([
                'success' => true,
                'message' => 'Product updated successfully.',
                'data' => new ProductResource($product),
            ]));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified product.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();
            $this->broadcastServicesListingUpdate();

            return response()->json($this->appendServicesListingBroadcastWarning([
                'success' => true,
                'message' => 'Product deleted successfully.',
            ]));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete products.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:products,id',
        ]);

        try {
            Product::whereIn('id', $request->input('ids'))->delete();
            $this->broadcastServicesListingUpdate();

            return response()->json($this->appendServicesListingBroadcastWarning([
                'success' => true,
                'message' => 'Products deleted successfully.',
            ]));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete products.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle product status (active/inactive).
     */
    public function toggleStatus(string $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $product->status = !$product->status;
            $product->save();
            $this->broadcastServicesListingUpdate();

            return response()->json($this->appendServicesListingBroadcastWarning([
                'success' => true,
                'message' => 'Product status updated successfully.',
                'data' => new ProductResource($product->load(['service'])),
            ]));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product status.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get products for select options.
     */
    public function selectOptions(Request $request): JsonResponse
    {
        try {
            $query = Product::query()->with(['service']);

            // Filter by service if provided
            if ($request->has('service_id')) {
                $query->where('service_id', $request->input('service_id'));
            }

            // Only active products
            if ($request->boolean('active_only', true)) {
                $query->where('status', true);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('service_url', 'like', "%{$search}%")
                      ->orWhere('notify_url', 'like', "%{$search}%")
                      ->orWhere('prepay_url', 'like', "%{$search}%");
                });
            }

            // Get limit from request, default to 5 if no search, 100 if searching
            $limit = $request->has('limit') ? (int) $request->limit : ($request->has('search') && !empty($request->search) ? 100 : 5);

            $products = $query->limit($limit)->get();

            $options = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'value' => $product->id,
                    'label' => $product->getTranslation('name', app()->getLocale(), false)
                        ?? $product->getTranslation('name', 'en', false)
                        ?? "Product #{$product->id}",
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'service_url' => $product->service_url,
                        'notify_url' => $product->notify_url,
                        'prepay_url' => $product->prepay_url,
                        'service' => $product->service ? [
                            'id' => $product->service->id,
                            'service_name' => [
                                'en' => $product->service->serviceNameForLocale('en'),
                                'ar' => $product->service->serviceNameForLocale('ar'),
                            ],
                        ] : null,
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $options,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product options.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
