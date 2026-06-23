<?php

namespace App\Services;

use App\Models\Country;
use App\Repositories\CountryRepository;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CountryService
{
    protected $countryRepository;

    public function __construct(CountryRepository $countryRepository)
    {
        $this->countryRepository = $countryRepository;
    }

    /**
     * Create a new country
     */
    public function create(array $data): Country
    {
        return $this->countryRepository->create($data);
    }

    /**
     * Update an existing country
     */
    public function update(Country $country, array $data): Country
    {
        return $this->countryRepository->update($country, $data);
    }

    /**
     * Delete a country
     */
    public function delete(Country $country): bool
    {
        return $this->countryRepository->delete($country);
    }

    /**
     * Bulk delete countries
     */
    public function bulkDelete(array $ids): bool
    {
        Country::whereIn('id', $ids)->delete();
        return true;
    }

    /**
     * Get all countries for API
     */
    public function getAllCountries(Request $request)
    {
        $query = Country::with('currency');

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchValue = $request->search;
            $query->where(function ($q) use ($searchValue) {
                $q->where('name->en', 'like', "%{$searchValue}%")
                  ->orWhere('name->ar', 'like', "%{$searchValue}%")
                  ->orWhere('short_name', 'like', "%{$searchValue}%")
                  ->orWhere('code', 'like', "%{$searchValue}%");
            });
        }

        // Status filter
        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $countries = $query->paginate($perPage);
        $countries->getCollection()->transform(function (Country $country) {
            return $this->transformCountry($country);
        });

        return $countries;
    }

    /**
     * Get DataTable data for countries
     */
    public function getDataTableData(Request $request)
    {
        $query = Country::with('currency');

        // Global search
        if ($request->has('search') && !empty($request->search)) {
            $searchValue = is_array($request->search) ? ($request->search['value'] ?? '') : $request->search;
            if (!empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('name->en', 'like', "%{$searchValue}%")
                      ->orWhere('name->ar', 'like', "%{$searchValue}%")
                      ->orWhere('short_name', 'like', "%{$searchValue}%")
                      ->orWhere('code', 'like', "%{$searchValue}%")
                      ->orWhereHas('currency', function ($currencyQuery) use ($searchValue) {
                          $currencyQuery->where('currency_code->en', 'like', "%{$searchValue}%")
                              ->orWhere('currency_code->ar', 'like', "%{$searchValue}%")
                              ->orWhere('name', 'like', "%{$searchValue}%");
                      });
                });
            }
        }

        // Status filter
        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)
            ->addColumn('id', fn($country) => $country->id)
            ->addColumn('name_en', fn($country) => $country->getTranslation('name', 'en'))
            ->addColumn('name_ar', fn($country) => $country->getTranslation('name', 'ar'))
            ->addColumn('short_name', fn($country) => $country->short_name)
            ->addColumn('code', fn($country) => $country->code ?? 'N/A')
            ->addColumn('currency_id', fn($country) => $country->currency_id)
            ->addColumn('currency_code', fn($country) => $this->resolveCurrencyCode($country->currency))
            ->addColumn('currency_code_translations', fn($country) => $country->currency?->getTranslations('currency_code') ?? null)
            ->addColumn('status', fn($country) => $country->status)
            ->addColumn('created_at', fn($country) => $country->created_at->format('Y-m-d H:i:s'))
            ->make(true);
    }

    private function resolveCurrencyCode($currency): ?string
    {
        if (!$currency) {
            return null;
        }

        return $currency->getTranslation('currency_code', app()->getLocale(), false)
            ?? $currency->getTranslation('currency_code', 'en', false)
            ?? null;
    }

    private function transformCountry(Country $country): Country
    {
        $country->setAttribute('currency_code', $this->resolveCurrencyCode($country->currency));
        $country->setAttribute('currency_code_translations', $country->currency?->getTranslations('currency_code'));

        return $country;
    }
}


