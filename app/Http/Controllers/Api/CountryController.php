<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class CountryController extends Controller
{
    use ApiResponse;

    /**
     * Country `name` JSON keys used for search (must match seeded translation keys).
     */
    private const NAME_SEARCH_LOCALES = ['en', 'ar'];

    private function sanitizeLocale(?string $locale, string $default = 'en'): string
    {
        if ($locale === null || $locale === '') {
            return $default;
        }

        $primary = strtolower(explode('-', str_replace('_', '-', $locale))[0] ?? '');

        return preg_match('/^[a-z]{2}$/', $primary) ? $primary : $default;
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

    /**
     * Get all countries
     */
    public function index(Request $request)
    {
        try {
            $countries = Country::with('currency')
                ->where('status', 1)
                ->select('id', 'name', 'short_name', 'code', 'currency_id')
                ->orderBy('name->en')
                ->get()
                ->map(function (Country $country) {
                    return [
                        'id' => $country->id,
                        'name' => $country->name,
                        'short_name' => $country->short_name,
                        'code' => $country->code,
                        'currency_id' => $country->currency_id,
                        'currency_code' => $this->resolveCurrencyCode($country->currency),
                        'currency_code_translations' => $country->currency?->getTranslations('currency_code'),
                    ];
                });

            return $this->SuccessMessage($countries);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch countries: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get countries for select dropdown
     */
    public function select(Request $request)
    {
        try {
            // Get language from request, default to 'en'
            $lang = $this->sanitizeLocale($request->get('lang'));

            $query = Country::with('currency')
                ->where('status', 1)
                ->select('id', 'name', 'short_name', 'code', 'currency_id');

            // Add search functionality
            if ($request->filled('search')) {
                $rawSearch = trim((string) $request->search);
                $searchTerm = function_exists('mb_strtolower')
                    ? mb_strtolower($rawSearch, 'UTF-8')
                    : strtolower($rawSearch);
                $likePattern = '%' . addcslashes($searchTerm, '%_\\') . '%';

                // Search every stored name locale: clients often omit `lang` and only send `search`,
                // so Arabic input must match `name->ar`, not only `name->en`.
                $locales = array_values(array_unique(array_merge([$lang], self::NAME_SEARCH_LOCALES)));

                $query->where(function ($q) use ($likePattern, $locales) {
                    foreach ($locales as $locale) {
                        $q->orWhereRaw(
                            'LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, ?))) LIKE ?',
                            ['$.' . $locale, $likePattern]
                        );
                    }
                    $q->orWhereRaw('LOWER(short_name) LIKE ?', [$likePattern])
                        ->orWhereRaw('LOWER(code) LIKE ?', [$likePattern]);
                });
            }

            $countries = $query->orderByRaw("name->'$." . $lang . "'")
                ->limit(20)
                ->get()
                ->map(function ($country) use ($lang) {
                    // Extract the name based on language
                    $nameData = is_array($country->name) ? $country->name : json_decode($country->name, true);
                    $displayName = $nameData;
                    
                    return [
                        'id' => $country->id,
                        'text' => $country->name,
                        'name' => $country->name,
                        'short_name' => $country->short_name,
                        'code' => $country->code,
                        'currency_id' => $country->currency_id,
                        'currency_code' => $this->resolveCurrencyCode($country->currency),
                        'currency_code_translations' => $country->currency?->getTranslations('currency_code'),
                        'flag_url' => $country->getFlagUrl(),
                        'flag_path' => $country->getFlagPath(),
                    ];
                });

            return $this->SuccessMessage($countries);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch countries for select: ' . $e->getMessage(), null, 500);
        }
    }
}

