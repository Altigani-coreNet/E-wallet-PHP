<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AdminCurrencyController extends Controller
{
    use ApiResponse;

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
     * Display a listing of currencies
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Currency::query();

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('country', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('currency_code->en', 'like', "%{$search}%")
                      ->orWhere('currency_code->ar', 'like', "%{$search}%");
                });
            }

            // Date range filter
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $perPage = $request->get('per_page', 15);
            $currencies = $query->orderBy('country')->paginate($perPage);
            $currencies->getCollection()->transform(function (Currency $currency) {
                $currency->setAttribute('currency_code', $this->resolveCurrencyCode($currency));
                $currency->setAttribute('currency_code_translations', $currency->getTranslations('currency_code'));
                $currency->setAttribute('symbol', $this->resolveSymbol($currency));
                $currency->setAttribute('symbol_translations', $currency->getTranslations('symbol'));
                return $currency;
            });

            return $this->SuccessMessage($currencies);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch currencies: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get currencies data for DataTable
     */
    public function data(Request $request)
    {
        try {
            $query = Currency::query();

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('country', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('currency_code->en', 'like', "%{$search}%")
                      ->orWhere('currency_code->ar', 'like', "%{$search}%");
                });
            }

            // Date range filter
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $total = $query->count();
            
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);
            
            $currencies = $query->orderBy('country')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get()
                ->map(function (Currency $currency) {
                    $currency->setAttribute('currency_code', $this->resolveCurrencyCode($currency));
                    $currency->setAttribute('currency_code_translations', $currency->getTranslations('currency_code'));
                    $currency->setAttribute('symbol', $this->resolveSymbol($currency));
                    $currency->setAttribute('symbol_translations', $currency->getTranslations('symbol'));
                    return $currency;
                });

            return $this->SuccessMessage([
                'data' => $currencies,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
            ]);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch currencies data: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Select2 endpoint for currencies
     */
    public function select(Request $request): JsonResponse
    {
        try {
            $search = trim((string) $request->get('search', ''));
            $limit = (int) ($request->get('limit', 20));

            $query = Currency::query();
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('currency_code', 'like', "%$search%")
                      ->orWhere('name', 'like', "%$search%")
                      ->orWhere('country', 'like', "%$search%");
                });
            }

            $currencies = $query->limit($limit)->get()->sortBy(function (Currency $currency) {
                return strtolower($this->resolveCurrencyCode($currency));
            })->values();

            $results = $currencies->map(function (Currency $c) {
                $code = $this->resolveCurrencyCode($c);
                $name = $c->name;
                return [
                    'id' => $c->id,
                    'text' => $code . ' - ' . $name,
                ];
            });

            return $this->SuccessMessage($results);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch currencies: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Store a newly created currency
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'country' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'symbol' => 'required|array',
                'symbol.en' => 'required|string|max:10',
                'symbol.ar' => 'required|string|max:20',
                'currency_code' => 'required|array',
                'currency_code.en' => 'required|string|max:10',
                'currency_code.ar' => 'required|string|max:20',
            ]);

            if ($validator->fails()) {
                return $this->ErrorMessage('Validation failed', $validator->errors(), 422);
            }

            $currency = Currency::create([
                'country' => $request->country,
                'name' => $request->name,
                'symbol' => [
                    'en' => $request->input('symbol.en'),
                    'ar' => $request->input('symbol.ar'),
                ],
                'currency_code' => [
                    'en' => $request->input('currency_code.en'),
                    'ar' => $request->input('currency_code.ar'),
                ],
            ]);

            $currency->setAttribute('currency_code', $this->resolveCurrencyCode($currency));
            $currency->setAttribute('currency_code_translations', $currency->getTranslations('currency_code'));
            $currency->setAttribute('symbol', $this->resolveSymbol($currency));
            $currency->setAttribute('symbol_translations', $currency->getTranslations('symbol'));
            return $this->SuccessMessage($currency, 201);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create currency: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Display the specified currency
     */
    public function show($id): JsonResponse
    {
        try {
            $currency = Currency::findOrFail($id);
            $currency->setAttribute('currency_code', $this->resolveCurrencyCode($currency));
            $currency->setAttribute('currency_code_translations', $currency->getTranslations('currency_code'));
            $currency->setAttribute('symbol', $this->resolveSymbol($currency));
            $currency->setAttribute('symbol_translations', $currency->getTranslations('symbol'));
            return $this->SuccessMessage($currency);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch currency: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update the specified currency
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $currency = Currency::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'country' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'symbol' => 'required|array',
                'symbol.en' => 'required|string|max:10',
                'symbol.ar' => 'required|string|max:20',
                'currency_code' => 'required|array',
                'currency_code.en' => 'required|string|max:10',
                'currency_code.ar' => 'required|string|max:20',
            ]);

            if ($validator->fails()) {
                return $this->ErrorMessage('Validation failed', $validator->errors(), 422);
            }

            $currency->update([
                'country' => $request->country,
                'name' => $request->name,
                'symbol' => [
                    'en' => $request->input('symbol.en'),
                    'ar' => $request->input('symbol.ar'),
                ],
                'currency_code' => [
                    'en' => $request->input('currency_code.en'),
                    'ar' => $request->input('currency_code.ar'),
                ],
            ]);

            $currency->setAttribute('currency_code', $this->resolveCurrencyCode($currency));
            $currency->setAttribute('currency_code_translations', $currency->getTranslations('currency_code'));
            $currency->setAttribute('symbol', $this->resolveSymbol($currency));
            $currency->setAttribute('symbol_translations', $currency->getTranslations('symbol'));
            return $this->SuccessMessage($currency, 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update currency: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified currency
     */
    public function destroy($id): JsonResponse
    {
        try {
            $currency = Currency::findOrFail($id);
            $currency->delete();
            
            return $this->SuccessMessage('Currency deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete currency: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk delete currencies
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $ids = is_string($request->ids) ? explode(',', $request->ids) : $request->ids;
            $deletedCount = Currency::whereIn('id', $ids)->delete();
            
            return $this->SuccessMessage([
                'message' => "{$deletedCount} currency(s) deleted successfully.",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete currencies: ' . $e->getMessage(), null, 500);
        }
    }
}


