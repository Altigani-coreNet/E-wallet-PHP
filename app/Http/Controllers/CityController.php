<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Traits\Select2Trait;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class CityController extends Controller
{
    use Select2Trait;

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('admin.cities.index');
    }

    /**
     * Get cities data for DataTables
     */
    public function data(Request $request)
    {
        $query = City::with('country');

        // Global search (DataTables format)
        if ($request->has('search') && is_array($request->search) && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('name->en', 'like', "%{$searchValue}%")
                  ->orWhere('name->ar', 'like', "%{$searchValue}%")
                  ->orWhereHas('country', function ($countryQuery) use ($searchValue) {
                      $countryQuery->where('name->en', 'like', "%{$searchValue}%")
                                   ->orWhere('name->ar', 'like', "%{$searchValue}%");
                  });
            });
        }

        return DataTables::of($query)
            ->addColumn('record_select', function ($city) {
                return view('admin.cities.data_table.record_select', ['id' => $city->id])->render();
            })
            ->addColumn('name_en', function ($city) {
                return $city->getTranslation('name', 'en');
            })
            ->addColumn('name_ar', function ($city) {
                return $city->getTranslation('name', 'ar');
            })
            ->addColumn('country_name', function ($city) {
                return $city->country ? $city->country->getTranslation('name', 'en') : 'N/A';
            })
            ->addColumn('status', function ($city) {
                return view('admin.cities.data_table.status', [
                    'id' => $city->id,
                    'status' => $city->status
                ])->render();
            })
            ->editColumn('created_at', function ($city) {
                return $city->created_at->format('M d, Y');
            })
            ->addColumn('actions', 'admin.cities.data_table.actions')
            ->rawColumns(['record_select', 'status', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $countries = Country::where('status', true)->orderBy('name')->get();
        return view('admin.cities.create', compact('countries'));
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
            'country_id' => 'required|exists:countries,id',
            'status' => 'required|boolean',
        ]);

        City::create($request->all());

        return redirect()->route('admin.cities.index')
            ->with('success', 'City created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(City $city, Request $request)
    {
        // Handle status change via AJAX
        if ($request->has('status') && $request->ajax()) {
            $city->update(['status' => !$city->status]);
            return response()->json([
                'success' => true,
                'message' => 'City status updated successfully.',
                'status' => $city->status
            ]);
        }
        
        $city->load('country');
        return view('admin.cities.show', compact('city'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(City $city): View
    {
        $countries = Country::where('status', true)->orderBy('name')->get();
        return view('admin.cities.edit', compact('city', 'countries'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, City $city): RedirectResponse
    {
        $request->validate([
            'name' => 'required|array',
            'name.en' => 'required|string|max:255',
            'name.ar' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'status' => 'required|boolean',
        ]);

        $city->update($request->all());

        return redirect()->route('admin.cities.index')
            ->with('success', 'City updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(City $city): RedirectResponse
    {
        $city->delete();

        return redirect()->route('admin.cities.index')
            ->with('success', 'City deleted successfully.');
    }

    /**
     * Bulk delete cities
     */
    public function bulkDelete(Request $request)
    {
        try {
            $ids = explode(',', $request->ids);
            City::whereIn('id', $ids)->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Get cities for select dropdown
     */
    public function select(Request $request)
    {
        // $query = City::query();
        $query = City::query();

                $filterParams = ['search', 'type', 'country_id'];
                if ($request->country_id) {
        //            dd($request->country_id);
                }
                return $this->getSelect2DataV2($request, $query, ['name'], $filterParams);

                
    }
}
