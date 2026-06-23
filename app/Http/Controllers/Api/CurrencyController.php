<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class CurrencyController extends Controller
{
    use ApiResponse;

    private const TRANSLATED_SEARCH_LOCALES = ['en', 'ar'];

    private function resolveCurrencyCode(Currency $currency): string
    {
        return $currency->getTranslation('currency_code', app()->getLocale(), false)
            ?? $currency->getTranslation('currency_code', 'en', false)
            ?? '';
    }

    private function resolveSymbol(Currency $currency): string
    {
        return $currency->getTranslation('symbol', app()->getLocale(), false)
            ?? $currency->getTranslation('symbol', 'en', false)
            ?? '';
    }

    /**
     * Get all currencies
     */
    public function index(Request $request)
    {
        try {
            $currencies = Currency::select('id', 'name', 'symbol', 'currency_code', 'country')
                ->orderBy('name')
                ->get()
                ->map(function (Currency $currency) {
                    $currency->setAttribute('currency_code', $this->resolveCurrencyCode($currency));
                    $currency->setAttribute('symbol', $this->resolveSymbol($currency));
                    return $currency;
                });

            return $this->SuccessMessage($currencies);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch currencies: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get currencies for select dropdown
     */
    public function select(Request $request)
    {
        try {
            $query = Currency::select('id', 'name', 'symbol', 'currency_code');

            if ($request->filled('search')) {
                $rawSearch = trim((string) $request->search);
                $searchTerm = function_exists('mb_strtolower')
                    ? mb_strtolower($rawSearch, 'UTF-8')
                    : strtolower($rawSearch);
                $likePattern = '%' . addcslashes($searchTerm, '%_\\') . '%';

                $query->where(function ($q) use ($likePattern) {
                    $q->whereRaw('LOWER(name) LIKE ?', [$likePattern])
                        ->orWhereRaw('LOWER(country) LIKE ?', [$likePattern]);

                    foreach (self::TRANSLATED_SEARCH_LOCALES as $locale) {
                        $q->orWhereRaw(
                            'LOWER(JSON_UNQUOTE(JSON_EXTRACT(currency_code, ?))) LIKE ?',
                            ['$.' . $locale, $likePattern]
                        )->orWhereRaw(
                            'LOWER(JSON_UNQUOTE(JSON_EXTRACT(symbol, ?))) LIKE ?',
                            ['$.' . $locale, $likePattern]
                        );
                    }
                });
            }

            $currencies = $query->orderBy('name')
                ->limit(20)
                ->get()
                ->map(function (Currency $currency) {
                    $code = $this->resolveCurrencyCode($currency);
                    return [
                        'id' => $currency->id,
                        'text' => $currency->name . ' (' . $code . ')',
                        'name' => $currency->name,
                        'symbol' => $this->resolveSymbol($currency),
                        'symbol_translations' => $currency->getTranslations('symbol'),
                        'code' => $code,
                        'currency_code' => $code,
                        'currency_code_translations' => $currency->getTranslations('currency_code'),
                    ];
                });

            return $this->SuccessMessage($currencies);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch currencies for select: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get single currency details by UUID
     */
    public function show($id)
    {
        try {
            $currency = Currency::find($id);

            if (!$currency) {
                return $this->ErrorMessage('Currency not found', null, 404);
            }

            $code = $this->resolveCurrencyCode($currency);
            return $this->SuccessMessage([
                'id' => $currency->id,
                'name' => $currency->name,
                'symbol' => $this->resolveSymbol($currency),
                'symbol_translations' => $currency->getTranslations('symbol'),
                'currency_code' => $code,
                'code' => $code,
                'currency_code_translations' => $currency->getTranslations('currency_code'),
                'country' => $currency->country,
            ]);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch currency details: ' . $e->getMessage(), null, 500);
        }
    }
}

