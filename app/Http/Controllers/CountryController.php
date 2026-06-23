<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Traits\Select2Trait;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class CountryController extends Controller
{
    use Select2Trait;

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('admin.countries.index');
    }

    /**
     * Get countries data for DataTables
     */
    public function data(Request $request)
    {
        $query = Country::query();

        // Global search (DataTables format)
        if ($request->has('search') && is_array($request->search) && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('name->en', 'like', "%{$searchValue}%")
                  ->orWhere('name->ar', 'like', "%{$searchValue}%")
                  ->orWhere('short_name', 'like', "%{$searchValue}%")
                  ->orWhere('code', 'like', "%{$searchValue}%");
            });
        }

        return DataTables::of($query)
            ->addColumn('record_select', function ($country) {
                return view('admin.countries.data_table.record_select', ['id' => $country->id])->render();
            })
            ->addColumn('name_en', function ($country) {
                return $country->getTranslation('name', 'en');
            })
            ->addColumn('name_ar', function ($country) {
                return $country->getTranslation('name', 'ar');
            })
            ->addColumn('status', function ($country) {
                return view('admin.countries.data_table.status', [
                    'id' => $country->id,
                    'status' => $country->status
                ])->render();
            })
            ->editColumn('code', function ($country) {
                return $country->code ?? 'N/A';
            })
            ->editColumn('created_at', function ($country) {
                return $country->created_at->format('M d, Y');
            })
            ->addColumn('actions', 'admin.countries.data_table.actions')
            ->rawColumns(['record_select', 'status', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.countries.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|array',
            'name.en' => 'required|string|max:255',
            'name.ar' => 'required|string|max:255',
            'short_name' => 'required|string|max:10|unique:countries',
            'code' => 'nullable|string|max:10',
            'status' => 'required|boolean',
        ]);

        Country::create($request->all());

        return redirect()->route('admin.countries.index')
            ->with('success', 'Country created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Country $country, Request $request)
    {
        // Handle status change via AJAX
        if ($request->has('status') && $request->ajax()) {
            $country->update(['status' => !$country->status]);
            return response()->json([
                'success' => true,
                'message' => 'Country status updated successfully.',
                'status' => $country->status
            ]);
        }
        
        return view('admin.countries.show', compact('country'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Country $country): View
    {
        return view('admin.countries.edit', compact('country'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Country $country): RedirectResponse
    {
        $request->validate([
            'name' => 'required|array',
            'name.en' => 'required|string|max:255',
            'name.ar' => 'required|string|max:255',
            'short_name' => 'required|string|max:10|unique:countries,short_name,' . $country->id,
            'code' => 'nullable|string|max:10',
            'status' => 'required|boolean',
        ]);

        $country->update($request->all());

        return redirect()->route('admin.countries.index')
            ->with('success', 'Country updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Country $country): RedirectResponse
    {
        $country->delete();

        return redirect()->route('admin.countries.index')
            ->with('success', 'Country deleted successfully.');
    }

    /**
     * Bulk delete countries
     */
    public function bulkDelete(Request $request)
    {
        try {
            $ids = explode(',', $request->ids);
            Country::whereIn('id', $ids)->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Get countries for select dropdown
     */
    public function select(Request $request)
    {
        // $query = Country::query();
        return $this->getSelect2Data($request, Country::class, ['name']);
    }
}
