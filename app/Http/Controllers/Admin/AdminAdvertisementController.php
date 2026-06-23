<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdvertisementStoreRequest;
use App\Http\Requests\AdvertisementUpdateRequest;
use App\Models\Advertisement;
use App\Services\AdvertisementService;
use App\Support\AdvertisementBroadcast;
use App\Traits\MessageManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class AdminAdvertisementController extends Controller
{
    use MessageManager;
    public function __construct(private readonly AdvertisementService $advertisementService)
    {
    }

    /**
     * Display a listing of advertisements
     */
    public function index(): View
    {
        return view('admin.advertisements.index');
    }

    /**
     * Show the form for creating a new advertisement
     */
    public function create(): View
    {
        return view('admin.advertisements.create');
    }

    /**
     * Store a newly created advertisement in storage
     */
    public function store(AdvertisementStoreRequest $request): RedirectResponse
    {
        try {
            $this->advertisementService->create($request->validated());
            AdvertisementBroadcast::broadcastFullListing();
            $this->SuccessMessage('Advertisement created successfully');
            return redirect()->route('admin.advertisements.index')
                ->with('success', 'Advertisement created successfully');
        } catch (\Exception $e) {
            $this->ErrorMessage($e->getMessage());
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Show the form for editing the specified advertisement
     */
    public function edit(Advertisement $advertisement): View
    {
        return view('admin.advertisements.edit', compact('advertisement'));
    }

    /**
     * Update the specified advertisement in storage
     */
    public function update(AdvertisementUpdateRequest $request, Advertisement $advertisement): RedirectResponse
    {
        try {
            $this->advertisementService->update($advertisement, $request->validated());
            AdvertisementBroadcast::broadcastFullListing();
            $this->SuccessMessage('Advertisement updated successfully');
            return redirect()->route('admin.advertisements.index')
                ->with('success', 'Advertisement updated successfully');
        } catch (\Exception $e) {
            $this->ErrorMessage($e->getMessage());
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified advertisement from storage
     */
    public function destroy(Advertisement $advertisement): JsonResponse
    {
        try {
            $this->advertisementService->delete($advertisement);
            AdvertisementBroadcast::broadcastFullListing();

            $this->SuccessMessage('Advertisement deleted successfully');
            
            return response()->json([
                'success' => true,
                'message' => 'Advertisement deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get advertisements data for DataTable
     */
    public function data(Request $request)
    {
        $query = Advertisement::with('country')->select('advertisements.*');

        // Country filter
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Date range filters
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [
                $request->date('date_from')->startOfDay(),
                $request->date('date_to')->endOfDay(),
            ]);
        } elseif ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date('date_from')->startOfDay());
        } elseif ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date('date_to')->endOfDay());
        }

        // Search functionality
        $textSearch = is_array($request->search) ? ($request->search['value'] ?? null) : $request->get('search');
        if (!empty($textSearch)) {
            $query->where(function ($q) use ($textSearch) {
                $q->where('name', 'like', "%{$textSearch}%")
                  ->orWhereHas('country', function ($countryQuery) use ($textSearch) {
                      $countryQuery->where('name', 'like', "%{$textSearch}%");
                  });
            });
        }

        return DataTables::of($query)
            ->addColumn('image_preview', function ($advertisement) {
                return view('admin.advertisements.data_table.image', compact('advertisement'))->render();
            })
            ->addColumn('country_name', function ($advertisement) {
                return $advertisement->country->name ?? '-';
            })
            ->addColumn('status_badge', function ($advertisement) {
                return $advertisement->getStatusBadge();
            })
            ->addColumn('dates', function ($advertisement) {
                $startDate = $advertisement->start_date ? $advertisement->start_date->format('Y-m-d') : '-';
                $endDate = $advertisement->end_date ? $advertisement->end_date->format('Y-m-d') : '-';
                return "{$startDate} / {$endDate}";
            })
            ->addColumn('actions', function ($advertisement) {
                return view('admin.advertisements.data_table.actions', compact('advertisement'))->render();
            })
            ->rawColumns(['image_preview', 'status_badge', 'actions'])
            ->make(true);
    }
}

