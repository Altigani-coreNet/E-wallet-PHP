<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceStoreRequest;
use App\Http\Requests\ServiceUpdateRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Models\ServiceSubCategory;
use App\Traits\BroadcastsServicesListingUpdate;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    use HasFiles, BroadcastsServicesListingUpdate;

    /**
     * Display a listing of services.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Service::query()->with(['category', 'subCategory', 'merchant', 'partner.parentPartner', 'country', 'products']);

        // Auto-filter by partner_id if user is a partner user
        $user = Auth::user();
        $authenticatedPartnerId = $user?->partner_id ?? $user?->content_provider_id;
        if ($authenticatedPartnerId && !$request->has('partner_id') && !$request->has('content_provider_id') && !$request->has('merchant_id')) {
            $query->where('partner_id', $authenticatedPartnerId);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(service_name, '$.en')) like ?", ["%{$search}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(service_name, '$.ar')) like ?", ["%{$search}%"])
                  ->orWhere('short_code', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        if ($request->has('sub_category_id')) {
            $query->where('sub_category_id', $request->input('sub_category_id'));
        }

        // Filter by country
        if ($request->has('country_id')) {
            $query->where('country_id', $request->input('country_id'));
        }

        // Filter by service type
        if ($request->has('service_type')) {
            $query->where('service_type', $request->input('service_type'));
        }

        // Filter by merchant/content provider (only if explicitly provided and user is admin)
        if ($request->has('merchant_id')) {
            $query->where('partner_id', $request->input('merchant_id'));
        }
        
        if ($request->has('partner_id') && !$authenticatedPartnerId) {
            $query->where('partner_id', $request->input('partner_id'));
        }

        // Backward compatibility with old parameter names
        if ($request->has('content_provider_id') && !$authenticatedPartnerId) {
            $query->where('partner_id', $request->input('content_provider_id'));
        }

        // Filter by status (workflow: active / inactive / pending)
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $services = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ServiceResource::collection($services),
            'meta' => [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'per_page' => $services->perPage(),
                'total' => $services->total(),
            ],
        ]);
    }

    /**
     * Get select options for dropdowns.
     */
    public function selectOptions(Request $request): JsonResponse
    {
        $query = Service::query();

        // Auto-filter by partner
        $user = Auth::user();
        $authenticatedPartnerId = $user?->partner_id ?? $user?->content_provider_id;
        if ($authenticatedPartnerId && !$request->has('partner_id') && !$request->has('content_provider_id') && !$request->has('merchant_id')) {
            $query->where('partner_id', $authenticatedPartnerId);
        }

        // Only active by default
        if (!$request->has('include_inactive')) {
            $query->where('is_active', true)->where('status', 'active');
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        if ($request->has('sub_category_id')) {
            $query->where('sub_category_id', $request->input('sub_category_id'));
        }

        // Filter by country
        if ($request->has('country_id')) {
            $query->where('country_id', $request->input('country_id'));
        }

        // Filter by merchant/content provider (only if explicitly provided and user is admin)
        if ($request->has('merchant_id')) {
            $query->where('partner_id', $request->input('merchant_id'));
        }

        if ($request->has('partner_id') && !$authenticatedPartnerId) {
            $query->where('partner_id', $request->input('partner_id'));
        }

        // Filter by service type
        if ($request->has('service_type')) {
            $query->where('service_type', $request->input('service_type'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(service_name, '$.en')) like ?", ["%{$search}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(service_name, '$.ar')) like ?", ["%{$search}%"]);
            });
        }

        // Get limit from request, default to 5 if no search, 100 if searching
        $limit = $request->has('limit') ? (int) $request->limit : ($request->has('search') && !empty($request->search) ? 100 : 5);

        $services = $query->orderBy('id')->limit($limit)->get(['id', 'service_name']);

        // Format the response similar to countries endpoint with text field containing JSON
        $formattedServices = $services->map(function ($service) {
            $nameEn = $service->serviceNameForLocale('en');
            $nameAr = $service->serviceNameForLocale('ar');
            $fallback = $nameEn ?? $nameAr ?? $service->id;
            return [
                'id' => $service->id,
                'service_name' => [
                    'en' => $nameEn,
                    'ar' => $nameAr,
                ],
                'text' => json_encode([
                    'en' => $nameEn ?? $fallback,
                    'ar' => $nameAr ?? $fallback,
                ]),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedServices,
        ]);
    }

    /**
     * Store a newly created service.
     */
    public function store(ServiceStoreRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            
            // Remove merchant_id if it exists (not used in this table)
            unset($data['merchant_id']);
            
            if (isset($data['merchant_id']) && !isset($data['partner_id'])) {
                $data['partner_id'] = $data['merchant_id'];
            }
            if (!isset($data['partner_id'])) {
                $authPartnerId = Auth::user()?->partner_id ?? Auth::user()?->content_provider_id;
                if ($authPartnerId) {
                    $data['partner_id'] = $authPartnerId;
                }
            }
            $data['service_name'] = $this->normalizeServiceName($data['service_name'] ?? null);
            
            if ($request->hasFile('image')) {
                // Use shared uploader helper for consistency and correct folder handling
                $data['image'] = $this->uploadImageAndGetFileName($request, 'image', 'uploads/services');
            }

            if (!empty($data['sub_category_id'])) {
                $subCategory = ServiceSubCategory::find($data['sub_category_id']);
                if (!$subCategory || (!empty($data['category_id']) && $subCategory->category_id !== $data['category_id'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected sub-category does not belong to the selected category.',
                    ], 422);
                }
            }

            $service = Service::create($data);
            $this->broadcastServicesListingUpdate();

            return response()->json($this->appendServicesListingBroadcastWarning([
                'success' => true,
                'message' => 'Service created successfully.',
                'data' => new ServiceResource($service->load(['category', 'subCategory', 'partner', 'country', 'products'])),
            ]), 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create service.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified service.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $service = Service::with(['category', 'subCategory', 'merchant', 'partner.parentPartner', 'country', 'products'])->findOrFail($id);

            // Check ownership if user is a merchant
            $user = Auth::user();
            $authPartnerId = $user?->partner_id ?? $user?->content_provider_id;
            if ($authPartnerId && $service->partner_id !== $authPartnerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new ServiceResource($service),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found.',
            ], 404);
        }
    }

    /**
     * Update the specified service.
     */
    public function update(ServiceUpdateRequest $request, string $id): JsonResponse
    {
        try {
            $service = Service::findOrFail($id);

            // Check ownership if user is a merchant
            $user = Auth::user();
            $authPartnerId = $user?->partner_id ?? $user?->content_provider_id;
            if ($authPartnerId && $service->partner_id !== $authPartnerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to update this service.',
                ], 403);
            }

            $data = $request->validated();
            
            // Remove merchant_id if it exists (not used in this table)
            unset($data['merchant_id']);
            
            if (isset($data['merchant_id']) && !isset($data['partner_id'])) {
                $data['partner_id'] = $data['merchant_id'];
            }
            if (array_key_exists('service_name', $data)) {
                $data['service_name'] = $this->normalizeServiceName($data['service_name']);
            }
            
            if ($request->hasFile('image')) {
                // Use shared uploader helper for consistency and correct folder handling
                $data['image'] = $this->uploadImageAndGetFileName($request, 'image', 'uploads/services');
            }

            if (!empty($data['sub_category_id'])) {
                $selectedCategoryId = $data['category_id'] ?? $service->category_id;
                $subCategory = ServiceSubCategory::find($data['sub_category_id']);
                if (!$subCategory || ($selectedCategoryId && $subCategory->category_id !== $selectedCategoryId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected sub-category does not belong to the selected category.',
                    ], 422);
                }
            }

            // Prevent partner users from changing ownership
            if ($authPartnerId && isset($data['partner_id'])) {
                unset($data['partner_id']);
            }
            
            $service->update($data);
            $this->broadcastServicesListingUpdate();

            return response()->json($this->appendServicesListingBroadcastWarning([
                'success' => true,
                'message' => 'Service updated successfully.',
                'data' => new ServiceResource($service->load(['category', 'subCategory', 'partner', 'country', 'products'])),
            ]));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified service.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $service = Service::findOrFail($id);

            // Check ownership if user is a merchant
            $user = Auth::user();
            $authPartnerId = $user?->partner_id ?? $user?->content_provider_id;
            if ($authPartnerId && $service->partner_id !== $authPartnerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete this service.',
                ], 403);
            }

            $service->delete();
            $this->broadcastServicesListingUpdate();

            return response()->json($this->appendServicesListingBroadcastWarning([
                'success' => true,
                'message' => 'Service deleted successfully.',
            ]));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete service.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete services.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:services,id',
        ]);

        try {
            $user = Auth::user();
            $query = Service::whereIn('id', $request->input('ids'));

            // Filter by partner if user is partner-scoped
            $authPartnerId = $user?->partner_id ?? $user?->content_provider_id;
            if ($authPartnerId) {
                $query->where('partner_id', $authPartnerId);
            }

            $deletedCount = $query->count();
            $query->delete();
            $this->broadcastServicesListingUpdate();

            return response()->json($this->appendServicesListingBroadcastWarning([
                'success' => true,
                'message' => "{$deletedCount} service(s) deleted successfully.",
            ]));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete services.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export services to CSV.
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $query = Service::query()->with(['category', 'subCategory', 'merchant', 'partner.parentPartner', 'country', 'products']);

            // Auto-filter by partner for partner-scoped users
            $user = Auth::user();
            $authenticatedPartnerId = $user?->partner_id ?? $user?->content_provider_id;
            if ($authenticatedPartnerId && !$request->has('partner_id') && !$request->has('content_provider_id') && !$request->has('merchant_id')) {
                $query->where('partner_id', $authenticatedPartnerId);
            }

            // Apply same filters as index
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(service_name, '$.en')) like ?", ["%{$search}%"])
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(service_name, '$.ar')) like ?", ["%{$search}%"])
                      ->orWhere('short_code', 'like', "%{$search}%");
                });
            }

            if ($request->has('category_id')) {
                $query->where('category_id', $request->input('category_id'));
            }
            if ($request->has('sub_category_id')) {
                $query->where('sub_category_id', $request->input('sub_category_id'));
            }

            if ($request->has('merchant_id')) {
                $query->where('partner_id', $request->input('merchant_id'));
            }
            
            if ($request->has('partner_id') && !$authenticatedPartnerId) {
                $query->where('partner_id', $request->input('partner_id'));
            }

            // Backward compatibility with old parameter name (only if user is admin)
            if ($request->has('content_provider_id') && !$authenticatedPartnerId) {
                $query->where('partner_id', $request->input('content_provider_id'));
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->has('country_id')) {
                $query->where('country_id', $request->input('country_id'));
            }

            if ($request->has('service_type')) {
                $query->where('service_type', $request->input('service_type'));
            }

            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            }
            
            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            }

            $services = $query->get();

            $data = $services->map(function ($service) {
                return [
                    'ID' => $service->id,
                    'Service Name' => is_array($service->service_name)
                        ? ($service->service_name['en'] ?? $service->service_name['ar'] ?? 'N/A')
                        : ($service->service_name ?? 'N/A'),
                    'Service Type' => $service->service_type?->getDisplayName() ?? 'N/A',
                    'Short Code' => $service->short_code ?? 'N/A',
                    'Country' => $service->country?->name ?? 'N/A',
                    'Category' => $service->category?->name_en ?? 'N/A',
                    'Sub Category' => $service->subCategory?->name_en ?? 'N/A',
                    'Partner' => $service->partner?->name ?? 'N/A',
                    'Products Count' => $service->products->count(),
                    'Status' => $service->status,
                    'Active' => $service->is_active ? 'Yes' : 'No',
                    'Created At' => $service->created_at?->toDateTimeString(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export services.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import services from CSV.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'services' => 'required|array',
            'services.*.category_id' => 'required|exists:service_categories,id',
            'services.*.country_id' => 'required|exists:countries,id',
            'services.*.service_type' => 'required|in:digital,ivr,sms',
            'services.*.service_name' => 'required|string|max:255',
        ]);

        try {
            $user = Auth::user();
            $services = $request->input('services');
            $created = [];
            $errors = [];

            foreach ($services as $index => $serviceData) {
                try {
                    if (isset($serviceData['content_provider_id']) && !isset($serviceData['partner_id'])) {
                        $serviceData['partner_id'] = $serviceData['content_provider_id'];
                    }
                    if (isset($serviceData['merchant_id']) && !isset($serviceData['partner_id'])) {
                        $serviceData['partner_id'] = $serviceData['merchant_id'];
                    }
                    if (!isset($serviceData['partner_id'])) {
                        $authPartnerId = $user?->partner_id ?? $user?->content_provider_id;
                        if ($authPartnerId) {
                            $serviceData['partner_id'] = $authPartnerId;
                        }
                    }
                    unset($serviceData['content_provider_id']);
                    
                    // Remove merchant_id if it exists
                    unset($serviceData['merchant_id']);
                    
                    $service = Service::create($serviceData);
                    $created[] = $service;
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $index + 1,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => count($created) . ' services imported successfully.',
                'data' => [
                    'created_count' => count($created),
                    'error_count' => count($errors),
                    'errors' => $errors,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import services.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle service status (active/inactive).
     */
    public function toggleStatus(string $id): JsonResponse
    {
        try {
            $service = Service::findOrFail($id);

            // Check ownership if user is a merchant
            $user = Auth::user();
            $authPartnerId = $user?->partner_id ?? $user?->content_provider_id;
            if ($authPartnerId && $service->partner_id !== $authPartnerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to update this service.',
                ], 403);
            }

            $service->is_active = !$service->is_active;
            $service->save();
            $this->broadcastServicesListingUpdate();

            return response()->json($this->appendServicesListingBroadcastWarning([
                'success' => true,
                'message' => 'Service status updated successfully.',
                'data' => new ServiceResource($service->load(['category', 'subCategory', 'partner', 'country', 'products'])),
            ]));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service status.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function normalizeServiceName($serviceName): array
    {
        if (is_array($serviceName)) {
            $en = trim((string) ($serviceName['en'] ?? ''));
            $ar = trim((string) ($serviceName['ar'] ?? ''));
            if ($en === '' && $ar !== '') $en = $ar;
            if ($ar === '' && $en !== '') $ar = $en;
            return ['en' => $en, 'ar' => $ar];
        }

        $name = trim((string) $serviceName);
        return ['en' => $name, 'ar' => $name];
    }
}

