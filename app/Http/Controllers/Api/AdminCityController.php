<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CityRequest;
use App\Models\City;
use App\Services\CityService;
use App\Traits\ApiResponse;
use App\Traits\Select2Trait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminCityController extends Controller
{
    use ApiResponse, Select2Trait;
    
    protected $cityService;

    public function __construct(CityService $cityService)
    {
        $this->cityService = $cityService;
    }

    /**
     * Display a listing of cities
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $cities = $this->cityService->getAllCities($request);
            return $this->SuccessMessage($cities);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch cities: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get cities data for DataTable
     */
    public function data(Request $request)
    {
        try {
            $query = City::with('country');

            if ($request->has('search') && !empty($request->search)) {
                $search = is_array($request->search) ? ($request->search['value'] ?? '') : $request->search;
                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name->en', 'like', "%{$search}%")
                            ->orWhere('name->ar', 'like', "%{$search}%")
                            ->orWhereHas('country', function ($countryQuery) use ($search) {
                                $countryQuery->where('name->en', 'like', "%{$search}%")
                                    ->orWhere('name->ar', 'like', "%{$search}%");
                            });
                    });
                }
            }

            if ($request->has('status') && $request->status !== '' && $request->status !== null) {
                $query->where('status', $request->status);
            }

            if ($request->has('country_id') && !empty($request->country_id)) {
                $query->where('country_id', $request->country_id);
            }

            $total = $query->count();
            $perPage = (int) $request->get('per_page', 15);
            $page = (int) $request->get('page', 1);

            $cities = $query->orderBy('created_at', 'desc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get()
                ->map(function (City $city) {
                    return [
                        'id' => $city->id,
                        'name' => $city->name,
                        'name_en' => $city->getTranslation('name', 'en'),
                        'name_ar' => $city->getTranslation('name', 'ar'),
                        'country_id' => $city->country_id,
                        'country_name' => $city->country ? $city->country->getTranslation('name', 'en') : 'N/A',
                        'status' => $city->status,
                        'created_at' => $city->created_at?->format('Y-m-d H:i:s'),
                    ];
                });

            return $this->SuccessMessage([
                'data' => $cities,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'current_page' => $page,
                'per_page' => $perPage,
                'last_page' => $perPage > 0 ? (int) ceil($total / $perPage) : 1,
            ]);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch cities data: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get cities for select dropdown
     */
    public function select(Request $request): JsonResponse
    {
        try {
            $query = City::query();
            
            $filterParams = ['search', 'type', 'country_id'];
            return $this->getSelect2DataV2($request, $query, ['name'], $filterParams);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch cities for select: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Store a newly created city
     */
    public function store(CityRequest $request): JsonResponse
    {
        try {
            $city = $this->cityService->create($request->validated());
            return $this->SuccessMessage($city,  201);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create city: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Display the specified city
     */
    public function show($id): JsonResponse
    {
        try {
            $city = City::with('country')->findOrFail($id);
            return $this->SuccessMessage($city);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch city: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update the specified city
     */
    public function update(CityRequest $request, $id): JsonResponse
    {
        try {
            $city = City::findOrFail($id);
            $city = $this->cityService->update($city, $request->validated());
            
            return $this->SuccessMessage($city, 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update city: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified city
     */
    public function destroy($id): JsonResponse
    {
        try {
            $city = City::findOrFail($id);
            $this->cityService->delete($city);
            
            return $this->SuccessMessage( 'City deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete city: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk delete cities
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $ids = explode(',', $request->ids);
            $this->cityService->bulkDelete($ids);
            
            return $this->SuccessMessage( 'Cities deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete cities: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Change city status
     */
    public function changeStatus($id): JsonResponse
    {
        try {
            $city = City::findOrFail($id);
            $city->status = !$city->status;
            $city->save();
            
            return $this->SuccessMessage($city, 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to change city status: ' . $e->getMessage(),  500);
        }
    }
}

