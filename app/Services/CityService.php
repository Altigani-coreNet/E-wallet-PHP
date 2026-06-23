<?php

namespace App\Services;

use App\Models\City;
use App\Repositories\CityRepository;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CityService
{
    protected $cityRepository;

    public function __construct(CityRepository $cityRepository)
    {
        $this->cityRepository = $cityRepository;
    }

    /**
     * Create a new city
     */
    public function create(array $data): City
    {
        return $this->cityRepository->create($data);
    }

    /**
     * Update an existing city
     */
    public function update(City $city, array $data): City
    {
        return $this->cityRepository->update($city, $data);
    }

    /**
     * Delete a city
     */
    public function delete(City $city): bool
    {
        return $this->cityRepository->delete($city);
    }

    /**
     * Bulk delete cities
     */
    public function bulkDelete(array $ids): bool
    {
        City::whereIn('id', $ids)->delete();
        return true;
    }

    /**
     * Get all cities for API
     */
    public function getAllCities(Request $request)
    {
        $query = City::with('country');

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchValue = $request->search;
            $query->where(function ($q) use ($searchValue) {
                $q->where('name->en', 'like', "%{$searchValue}%")
                  ->orWhere('name->ar', 'like', "%{$searchValue}%")
                  ->orWhereHas('country', function ($countryQuery) use ($searchValue) {
                      $countryQuery->where('name->en', 'like', "%{$searchValue}%")
                                   ->orWhere('name->ar', 'like', "%{$searchValue}%");
                  });
            });
        }

        // Status filter
        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        // Country filter
        if ($request->has('country_id') && !empty($request->country_id)) {
            $query->where('country_id', $request->country_id);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        return $query->paginate($perPage);
    }

    /**
     * Get DataTable data for cities
     */
    public function getDataTableData(Request $request)
    {
        $query = City::with('country');

        // Global search
        if ($request->has('search') && !empty($request->search)) {
            $searchValue = is_array($request->search) ? ($request->search['value'] ?? '') : $request->search;
            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('name->en', 'like', "%{$searchValue}%")
                      ->orWhere('name->ar', 'like', "%{$searchValue}%")
                      ->orWhereHas('country', function ($countryQuery) use ($searchValue) {
                          $countryQuery->where('name->en', 'like', "%{$searchValue}%")
                                       ->orWhere('name->ar', 'like', "%{$searchValue}%");
                      });
                });
            }
        }

        // Status filter
        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        // Country filter
        if ($request->has('country_id') && !empty($request->country_id)) {
            $query->where('country_id', $request->country_id);
        }

        return DataTables::of($query)
            ->addColumn('id', fn($city) => $city->id)
            ->addColumn('name_en', fn($city) => $city->getTranslation('name', 'en'))
            ->addColumn('name_ar', fn($city) => $city->getTranslation('name', 'ar'))
            ->addColumn('country_name', fn($city) => $city->country ? $city->country->getTranslation('name', 'en') : 'N/A')
            ->addColumn('country_id', fn($city) => $city->country_id)
            ->addColumn('status', fn($city) => $city->status)
            ->addColumn('created_at', fn($city) => $city->created_at->format('Y-m-d H:i:s'))
            ->make(true);
    }
}


