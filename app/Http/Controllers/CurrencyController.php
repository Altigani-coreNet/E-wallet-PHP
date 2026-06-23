<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CurrencyController extends Controller
{
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
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $currencies = Currency::orderBy('country')->get();
        return view('admin.currencies.index', compact('currencies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.currencies.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'country' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'symbol' => 'required|array',
            'symbol.en' => 'required|string|max:10',
            'symbol.ar' => 'required|string|max:20',
            'currency_code' => 'required|array',
            'currency_code.en' => 'required|string|max:10',
            'currency_code.ar' => 'required|string|max:20',
        ]);

        Currency::create([
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

        return redirect()->route('admin.currencies.index')
            ->with('success', 'Currency created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Currency $currency): View
    {
        return view('admin.currencies.show', compact('currency'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Currency $currency): View
    {
        return view('admin.currencies.edit', compact('currency'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Currency $currency): RedirectResponse
    {
        $request->validate([
            'country' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'symbol' => 'required|array',
            'symbol.en' => 'required|string|max:10',
            'symbol.ar' => 'required|string|max:20',
            'currency_code' => 'required|array',
            'currency_code.en' => 'required|string|max:10',
            'currency_code.ar' => 'required|string|max:20',
        ]);

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

        return redirect()->route('admin.currencies.index')
            ->with('success', 'Currency updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Currency $currency): RedirectResponse
    {
        $currency->delete();

        return redirect()->route('admin.currencies.index')
            ->with('success', 'Currency deleted successfully.');
    }

    /**
     * Select2 endpoint for currencies (used by merchant forms)
     */
    public function select(Request $request): JsonResponse
    {
        $search = trim((string) $request->get('search', ''));
        $limit = (int) ($request->get('limit', 20));

        $query = Currency::query();
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('currency_code->en', 'like', "%$search%")
                  ->orWhere('currency_code->ar', 'like', "%$search%")
                  ->orWhere('name', 'like', "%$search%")
                  ->orWhere('country', 'like', "%$search%");
            });
        }

        $currencies = $query->orderBy('currency_code->en')->limit($limit)->get();

        $results = $currencies->map(function (Currency $c) {
            $code = $this->resolveCurrencyCode($c);
            $name = $c->name;
            return [
                'id' => $c->id,
                'text' => $code . ' - ' . $name,
                'symbol' => $this->resolveSymbol($c),
            ];
        });

        return response()->json($results);
    }
}
