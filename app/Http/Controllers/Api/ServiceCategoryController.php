<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCategoryStoreRequest;
use App\Http\Requests\ServiceCategoryUpdateRequest;
use App\Http\Resources\ServiceCategoryResource;
use App\Models\ServiceCategory;
use App\Traits\BroadcastsServicesListingUpdate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Traits\HasFiles;

class ServiceCategoryController extends Controller
{
    use HasFiles, BroadcastsServicesListingUpdate;
    /**
     * Display a listing of service categories.
     */
    public function index(Request $request): JsonResponse
    {
        $hasParentColumn = Schema::hasColumn('service_categories', 'parent_id');
        $query = ServiceCategory::query();

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($hasParentColumn) {
            $query->with('parent')->withCount('children');

            $hierarchy = $request->input('hierarchy');
            if ($hierarchy === 'parents') {
                $query->whereNull('parent_id');
            } elseif ($hierarchy === 'children') {
                $query->whereNotNull('parent_id');
            }
        }

        // Search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination with services count filtered by merchant if applicable
        $perPage = $request->input('per_page', 15);
        $user = Auth::user();
        
        if ($user && $user->content_provider_id) {
            // Count only services that belong to the merchant
            $categories = $query->withCount(['services' => function ($q) use ($user) {
                $q->where('content_provider_id', $user->content_provider_id);
            }, 'subCategories'])->paginate($perPage);
        } else {
            $categories = $query->withCount(['services', 'subCategories'])->paginate($perPage);
        }

        return response()->json([
            'success' => true,
            'data' => ServiceCategoryResource::collection($categories),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    /**
     * Get categories for select dropdown.
     */
    public function select(Request $request): JsonResponse
    {
        $hasParentColumn = Schema::hasColumn('service_categories', 'parent_id');
        $query = ServiceCategory::query();

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($hasParentColumn && $request->boolean('parents_only')) {
            $query->whereNull('parent_id');
        }

        // Only active by default
        if (!$request->has('include_inactive')) {
            $query->where('is_active', true);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%");
            });
        }

        // Get limit from request, default to 5 if no search, 100 if searching
        $limit = $request->has('limit') ? (int) $request->limit : ($request->has('search') && !empty($request->search) ? 100 : 5);

        $categories = $query->orderBy('name_en')->limit($limit)->get(['id', 'name_en', 'name_ar', 'code']);

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(ServiceCategoryStoreRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $hasParentColumn = Schema::hasColumn('service_categories', 'parent_id');

            if (!$hasParentColumn) {
                unset($data['parent_id']);
            }

            if ($hasParentColumn && !empty($data['parent_id']) && !ServiceCategory::where('id', $data['parent_id'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent category not found.',
                ], 422);
            }
            
            // Auto-generate code if not provided
            if (empty($data['code'])) {
                $type = $data['type'] ?? ServiceCategory::TYPE_SERVICE;
                $data['code'] = ServiceCategory::generateCategoryCode($type);
            }

            if ($request->hasFile('image')) {
                $data['image'] = $this->uploadImageAndGetFileName($request, 'image', 'uploads/service-categories');
            }
            
            $category = ServiceCategory::create($data);
            $this->broadcastServicesListingUpdate();

            return response()->json($this->appendServicesListingBroadcastWarning([
                'success' => true,
                'message' => 'Service category created successfully.',
                'data' => new ServiceCategoryResource($category),
            ]), 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create service category.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified category.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $hasParentColumn = Schema::hasColumn('service_categories', 'parent_id');
            
            if ($user && $user->content_provider_id) {
                // Load only services that belong to the merchant
                $query = ServiceCategory::with(['services' => function ($q) use ($user) {
                    $q->where('content_provider_id', $user->content_provider_id);
                }])->withCount(['services' => function ($q) use ($user) {
                    $q->where('content_provider_id', $user->content_provider_id);
                }]);

                if ($hasParentColumn) {
                    $query->with(['parent', 'children', 'subCategories'])->withCount(['children', 'subCategories']);
                } else {
                    $query->with('subCategories')->withCount('subCategories');
                }
                $category = $query->findOrFail($id);
            } else {
                $query = ServiceCategory::with(['services'])->withCount(['services']);
                if ($hasParentColumn) {
                    $query->with(['parent', 'children', 'subCategories'])->withCount(['children', 'subCategories']);
                } else {
                    $query->with('subCategories')->withCount('subCategories');
                }
                $category = $query->findOrFail($id);
            }

            return response()->json([
                'success' => true,
                'data' => new ServiceCategoryResource($category),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service category not found.',
            ], 404);
        }
    }

    /**
     * Update the specified category.
     */
    public function update(ServiceCategoryUpdateRequest $request, string $id): JsonResponse
    {
        try {
            $category = ServiceCategory::findOrFail($id);
            $data = $request->validated();
            $hasParentColumn = Schema::hasColumn('service_categories', 'parent_id');

            if (!$hasParentColumn) {
                unset($data['parent_id']);
            }

            if (
                $hasParentColumn &&
                array_key_exists('parent_id', $data) &&
                !empty($data['parent_id']) &&
                $data['parent_id'] === $category->id
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'A category cannot be its own parent.',
                ], 422);
            }

            if ($request->hasFile('image')) {
                $data['image'] = $this->uploadImageAndGetFileName($request, 'image', 'uploads/service-categories');
            }

            $category->update($data);
            $this->broadcastServicesListingUpdate();

            return response()->json($this->appendServicesListingBroadcastWarning([
                'success' => true,
                'message' => 'Service category updated successfully.',
                'data' => new ServiceCategoryResource($category),
            ]));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service category.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified category.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $category = ServiceCategory::findOrFail($id);
            $hasParentColumn = Schema::hasColumn('service_categories', 'parent_id');
            
            // Check if category has services
            if ($category->services()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category with associated services.',
                ], 422);
            }

            if ($hasParentColumn && $category->children()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete category with sub-categories.',
                ], 422);
            }

            $category->delete();
            $this->broadcastServicesListingUpdate();

            return response()->json($this->appendServicesListingBroadcastWarning([
                'success' => true,
                'message' => 'Service category deleted successfully.',
            ]));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete service category.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete categories.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:service_categories,id',
        ]);

        try {
            $ids = $request->input('ids');
            $hasParentColumn = Schema::hasColumn('service_categories', 'parent_id');
            
            // Check if any categories have services
            $categoriesWithServices = ServiceCategory::whereIn('id', $ids)
                ->has('services')
                ->count();

            $categoriesWithChildren = 0;
            if ($hasParentColumn) {
                $categoriesWithChildren = ServiceCategory::whereIn('id', $ids)
                    ->has('children')
                    ->count();
            }

            if ($categoriesWithServices > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete categories with associated services.',
                ], 422);
            }

            if ($categoriesWithChildren > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete categories with sub-categories.',
                ], 422);
            }

            ServiceCategory::whereIn('id', $ids)->delete();
            $this->broadcastServicesListingUpdate();

            return response()->json($this->appendServicesListingBroadcastWarning([
                'success' => true,
                'message' => 'Service categories deleted successfully.',
            ]));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete service categories.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle category active status.
     */
    public function toggleStatus(string $id): JsonResponse
    {
        try {
            $category = ServiceCategory::findOrFail($id);
            $category->is_active = !$category->is_active;
            $category->save();
            $this->broadcastServicesListingUpdate();

            return response()->json($this->appendServicesListingBroadcastWarning([
                'success' => true,
                'message' => 'Status updated successfully.',
                'data' => new ServiceCategoryResource($category),
            ]));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export categories to CSV.
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $query = ServiceCategory::query();
            $hasParentColumn = Schema::hasColumn('service_categories', 'parent_id');

            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }

            if ($hasParentColumn) {
                $hierarchy = $request->input('hierarchy');
                if ($hierarchy === 'parents') {
                    $query->whereNull('parent_id');
                } elseif ($hierarchy === 'children') {
                    $query->whereNotNull('parent_id');
                }
            }

            // Apply same filters as index
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name_en', 'like', "%{$search}%")
                      ->orWhere('name_ar', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $categories = $query->get();

            $data = $categories->map(function ($category) {
                return [
                    'ID' => $category->id,
                    'Name (English)' => $category->name_en,
                    'Name (Arabic)' => $category->name_ar,
                    'Code' => $category->code,
                    'Type' => $category->type ?? 'service',
                    'Active' => $category->is_active ? 'Yes' : 'No',
                    'Description' => $category->description ?? '',
                    'Created At' => $category->created_at?->toDateTimeString(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export service categories.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

