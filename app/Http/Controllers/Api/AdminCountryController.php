<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CountryRequest;
use App\Models\Country;
use App\Services\CountryService;
use App\Traits\ApiResponse;
use App\Traits\Select2Trait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminCountryController extends Controller
{
    use ApiResponse, Select2Trait;
    
    protected $countryService;

    public function __construct(CountryService $countryService)
    {
        $this->countryService = $countryService;
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

    /**
     * Display a listing of countries
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $countries = $this->countryService->getAllCountries($request);
            return $this->SuccessMessage($countries);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch countries: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get countries data for DataTable
     */
    public function data(Request $request)
    {
        try {
            $query = Country::with('currency');

            if ($request->has('search') && !empty($request->search)) {
                $search = is_array($request->search) ? ($request->search['value'] ?? '') : $request->search;
                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name->en', 'like', "%{$search}%")
                            ->orWhere('name->ar', 'like', "%{$search}%")
                            ->orWhere('short_name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhereHas('currency', function ($currencyQuery) use ($search) {
                                $currencyQuery->where('currency_code', 'like', "%{$search}%");
                            });
                    });
                }
            }

            if ($request->has('status') && $request->status !== '' && $request->status !== null) {
                $query->where('status', $request->status);
            }

            $total = $query->count();
            $perPage = (int) $request->get('per_page', 15);
            $page = (int) $request->get('page', 1);

            $countries = $query->orderBy('created_at', 'desc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get()
                ->map(function (Country $country) {
                    $country->setAttribute('name_en', $country->getTranslation('name', 'en'));
                    $country->setAttribute('name_ar', $country->getTranslation('name', 'ar'));
                    return $this->transformCountry($country);
                });

            return $this->SuccessMessage([
                'data' => $countries,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'current_page' => $page,
                'per_page' => $perPage,
                'last_page' => $perPage > 0 ? (int) ceil($total / $perPage) : 1,
            ]);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch countries data: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get countries for select dropdown
     */
    public function select(Request $request): JsonResponse
    {
        try {
            $query = Country::with('currency');
            
            // Filter by regions from header if present (Country model doesn't have country_id, so filter by id)
            $regionsFromHeader = $this->getRegionsFromHeader();
            if (!empty($regionsFromHeader)) {
                $query->whereIn('id', $regionsFromHeader);
            }
            
            // Apply search if provided
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name->en', 'like', "%{$search}%")
                        ->orWhere('name->ar', 'like', "%{$search}%")
                        ->orWhereHas('currency', function ($currencyQuery) use ($search) {
                            $currencyQuery->where('currency_code->en', 'like', "%{$search}%")
                                ->orWhere('currency_code->ar', 'like', "%{$search}%");
                        });
                });
            }
            
            // Only active countries
            $query->where('status', 1);
            
            $countries = $query->orderBy('name->en')
                ->limit(100)
                ->get()
                ->map(function (Country $country) {
                    return [
                        'id' => $country->id,
                        'text' => $country->getTranslation('name', app()->getLocale(), false)
                            ?? $country->getTranslation('name', 'en', false)
                            ?? '',
                        'name' => $country->name,
                        'currency_id' => $country->currency_id,
                        'currency_code' => $this->resolveCurrencyCode($country->currency),
                        'currency_code_translations' => $country->currency?->getTranslations('currency_code'),
                    ];
                });
            
            return $this->SuccessMessage($countries);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch countries for select: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Extract regions from X-Regions header
     */
    private function getRegionsFromHeader(): array
    {
        $request = request();
        if (!$request) {
            return [];
        }

        $regionsHeader = $request->header('X-Regions');
        if (!$regionsHeader) {
            return [];
        }

        try {
            $regionIds = json_decode($regionsHeader, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($regionIds)) {
                return [];
            }
            return array_filter(array_map('strval', $regionIds));
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Store a newly created country
     */
    public function store(CountryRequest $request): JsonResponse
    {
        try {
            $country = $this->countryService->create($request->validated());
            return $this->SuccessMessage($country,  201);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create country: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Display the specified country
     */
    public function show($id): JsonResponse
    {
        try {
            $country = Country::with('currency')->findOrFail($id);
            return $this->SuccessMessage($this->transformCountry($country));
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch country: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update the specified country
     */
    public function update(CountryRequest $request, $id): JsonResponse
    {
        try {
            $country = Country::findOrFail($id);
            $country = $this->countryService->update($country, $request->validated());
            
            return $this->SuccessMessage($this->transformCountry($country->load('currency')), 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update country: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified country
     */
    public function destroy($id): JsonResponse
    {
        try {
            $country = Country::findOrFail($id);
            $this->countryService->delete($country);
            
            return $this->SuccessMessage( 'Country deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete country: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk delete countries
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $ids = explode(',', $request->ids);
            $this->countryService->bulkDelete($ids);
            
            return $this->SuccessMessage( 'Countries deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete countries: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Change country status
     */
    public function changeStatus($id): JsonResponse
    {
        try {
            $country = Country::findOrFail($id);
            $country->status = !$country->status;
            $country->save();
            
            return $this->SuccessMessage($country,   200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to change country status: ' . $e->getMessage(), null, 500);
        }
    }
}

