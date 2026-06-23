<?php

namespace App\Http\Controllers\Api\V2\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminTagController extends Controller
{
    use ApiResponse;

    /**
     * List tags with pagination and filters.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Tag::query()->with(['country']);

            // Search
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%");
                });
            }

            // Country filter
            if ($countryId = $request->input('country_id')) {
                $query->where('country_id', $countryId);
            }

            // Shop filter
            if ($shopId = $request->input('shop_id')) {
                $query->where('shop_id', $shopId);
            }

            // Status filter
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            // Date range filter
            if ($fromDate = $request->input('from_date')) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate = $request->input('to_date')) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            // Pagination
            $perPage = (int) $request->input('per_page', 15);
            $tags = $query->orderByDesc('created_at')->paginate($perPage);

            return $this->SuccessMessage($tags);
        } catch (\Exception $exception) {
            Log::error('AdminTagController@index error: ' . $exception->getMessage(), [
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->ErrorMessage('Failed to fetch tags', null, 500);
        }
    }

    /**
     * Show tag details.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $tag = Tag::with(['country'])->findOrFail($id);
            return $this->SuccessMessage($tag);
        } catch (\Exception $exception) {
            Log::error('AdminTagController@show error: ' . $exception->getMessage(), [
                'id' => $id,
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->ErrorMessage('Failed to fetch tag', null, 404);
        }
    }

    /**
     * Create a new tag.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'country_id' => ['nullable', 'exists:countries,id'],
                'shop_id' => ['nullable', 'string'],
                'status' => ['nullable', 'boolean'],
            ]);

            $tag = Tag::create($validated);

            return $this->SuccessMessage($tag, 'Tag created successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->ErrorMessage('Validation failed', $e->errors(), 422);
        } catch (\Exception $exception) {
            Log::error('AdminTagController@store error: ' . $exception->getMessage(), [
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->ErrorMessage('Failed to create tag', null, 500);
        }
    }

    /**
     * Update a tag.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $tag = Tag::findOrFail($id);

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('tags')->ignore($tag->id)],
                'country_id' => ['nullable', 'exists:countries,id'],
                'shop_id' => ['nullable', 'string'],
                'status' => ['nullable', 'boolean'],
            ]);

            $tag->update($validated);

            return $this->SuccessMessage($tag, 'Tag updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->ErrorMessage('Validation failed', $e->errors(), 422);
        } catch (\Exception $exception) {
            Log::error('AdminTagController@update error: ' . $exception->getMessage(), [
                'id' => $id,
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->ErrorMessage('Failed to update tag', null, 500);
        }
    }

    /**
     * Delete a tag.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $tag = Tag::findOrFail($id);
            $tag->delete();

            return $this->SuccessMessage(null, 'Tag deleted successfully');
        } catch (\Exception $exception) {
            Log::error('AdminTagController@destroy error: ' . $exception->getMessage(), [
                'id' => $id,
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->ErrorMessage('Failed to delete tag', null, 500);
        }
    }

    /**
     * Retrieve tag statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $query = Tag::query();

            if ($countryId = $request->input('country_id')) {
                $query->where('country_id', $countryId);
            }

            if ($shopId = $request->input('shop_id')) {
                $query->where('shop_id', $shopId);
            }

            $baseQuery = clone $query;

            $statistics = [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('status', true)->count(),
                'inactive' => (clone $baseQuery)->where('status', false)->count(),
            ];

            return $this->SuccessMessage($statistics);
        } catch (\Exception $exception) {
            Log::error('AdminTagController@statistics error: ' . $exception->getMessage(), [
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->ErrorMessage('Failed to fetch tag statistics', null, 500);
        }
    }

    /**
     * Export tags as CSV.
     */
    public function export(Request $request)
    {
        try {
            $query = Tag::query()->with(['country']);

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%");
                });
            }

            if ($countryId = $request->input('country_id')) {
                $query->where('country_id', $countryId);
            }

            if ($shopId = $request->input('shop_id')) {
                $query->where('shop_id', $shopId);
            }

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($fromDate = $request->input('from_date')) {
                $query->whereDate('created_at', '>=', $fromDate);
            }

            if ($toDate = $request->input('to_date')) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            $tags = $query->orderByDesc('created_at')->get();

            if ($tags->isEmpty()) {
                return $this->ErrorMessage('No tags found for export', null, 404);
            }

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="admin_tags_export_' . date('Y-m-d_H-i-s') . '.csv"',
            ];

            $callback = function () use ($tags) {
                $file = fopen('php://output', 'w');

                fputcsv($file, [
                    'ID',
                    'Name',
                    'Slug',
                    'Country',
                    'Shop ID',
                    'Status',
                    'Created At',
                    'Updated At',
                ]);

                foreach ($tags as $tag) {
                    fputcsv($file, [
                        $tag->id,
                        $tag->name,
                        $tag->slug,
                        optional($tag->country)->name ?? optional($tag->country)->en_name ?? '',
                        $tag->shop_id,
                        $tag->status ? 'Active' : 'Inactive',
                        optional($tag->created_at)?->format('Y-m-d H:i:s'),
                        optional($tag->updated_at)?->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($file);
            };

            return new StreamedResponse($callback, 200, $headers);
        } catch (\Exception $exception) {
            Log::error('AdminTagController@export error: ' . $exception->getMessage(), [
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->ErrorMessage('Failed to export tags', null, 500);
        }
    }
}







