<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceSubCategoryStoreRequest;
use App\Http\Requests\ServiceSubCategoryUpdateRequest;
use App\Http\Resources\ServiceSubCategoryResource;
use App\Models\ServiceSubCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceSubCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ServiceSubCategory::with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $query->orderBy($request->input('sort_by', 'created_at'), $request->input('sort_order', 'desc'));
        $items = $query->withCount('services')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => ServiceSubCategoryResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function select(Request $request): JsonResponse
    {
        $query = ServiceSubCategory::query();

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        if (!$request->has('include_inactive')) {
            $query->where('is_active', true);
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%");
            });
        }

        $limit = $request->has('limit') ? (int) $request->limit : 100;
        $items = $query->orderBy('name_en')->limit($limit)->get(['id', 'category_id', 'name_en', 'name_ar', 'code']);

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(ServiceSubCategoryStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (empty($data['code'])) {
            $data['code'] = ServiceSubCategory::generateSubCategoryCode();
        }

        $item = ServiceSubCategory::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Service sub-category created successfully.',
            'data' => new ServiceSubCategoryResource($item->load('category')),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $item = ServiceSubCategory::with('category')->withCount('services')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new ServiceSubCategoryResource($item),
        ]);
    }

    public function update(ServiceSubCategoryUpdateRequest $request, string $id): JsonResponse
    {
        $item = ServiceSubCategory::findOrFail($id);
        $item->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Service sub-category updated successfully.',
            'data' => new ServiceSubCategoryResource($item->load('category')),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $item = ServiceSubCategory::findOrFail($id);
        if ($item->services()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete sub-category with associated services.',
            ], 422);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service sub-category deleted successfully.',
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:service_sub_categories,id',
        ]);

        $ids = $request->input('ids');
        if (ServiceSubCategory::whereIn('id', $ids)->has('services')->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete sub-categories with associated services.',
            ], 422);
        }

        ServiceSubCategory::whereIn('id', $ids)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service sub-categories deleted successfully.',
        ]);
    }

    public function toggleStatus(string $id): JsonResponse
    {
        $item = ServiceSubCategory::findOrFail($id);
        $item->is_active = !$item->is_active;
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'data' => new ServiceSubCategoryResource($item->load('category')),
        ]);
    }
}
