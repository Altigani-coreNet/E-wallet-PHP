<?php

namespace App\Http\Controllers;

use App\Http\Requests\TerminalRequest;
use App\Models\Terminal;
use App\Services\TerminalService;
use Illuminate\Http\Request;

class TerminalController extends Controller
{
    protected $terminalService;

    public function __construct(TerminalService $terminalService)
    {
        $this->terminalService = $terminalService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->terminalService->index();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return $this->terminalService->create();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TerminalRequest $request)
    {
        $terminal = $this->terminalService->store($request);
        
        // Log the terminal creation
        $terminal->logActivity('created', auth()->user(), 'Terminal created');
        
        return redirect()->route('terminals.index')->with('success', __('translation.terminal_success_create'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Terminal $terminal)
    {
        // Log the terminal view
        // $terminal->logActivity('viewed', auth()->user(), 'Terminal viewed');
        
        return $this->terminalService->show($terminal);
    }

    /**
     * Display terminal events.
     */
    public function events(Terminal $terminal)
    {
        // Log the terminal events view
        // $terminal->logActivity('viewed', auth()->user(), 'Terminal events viewed');
        
        return view('terminals.events', compact('terminal'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Terminal $terminal)
    {
        return $this->terminalService->edit($terminal);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TerminalRequest $request, Terminal $terminal)
    {
        // Store old values for logging
        $oldValues = $terminal->toArray();
        
        $this->terminalService->update($request, $terminal);
        
        // Refresh terminal to get new values
        $terminal->refresh();
        $newValues = $terminal->toArray();
        
        // Log the terminal update
        $terminal->logActivity('updated', auth()->user(), 'Terminal updated', $oldValues, $newValues);
        
        return redirect()->route('terminals.index')->with('success', __('translation.terminal_success_update'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Terminal $terminal)
    {
        // Store terminal data before deletion for logging
        $terminalData = $terminal->toArray();
        
        $this->terminalService->destroy($terminal);
        
        // Log the terminal deletion (create log before deletion)
        $terminal->logActivity('deleted', auth()->user(), 'Terminal deleted', $terminalData);
        
        return redirect()->route('terminals.index')->with('success', __('translation.terminal_success_delete'));
    }

    /**
     * Get DataTable data
     */
    public function data(Request $request)
    {
        $merchantId = $request->get('merchant_id');
        return $this->terminalService->data($merchantId);
    }

    /**
     * Get terminals for select dropdown with merchant filtering
     */
    public function select(Request $request)
    {
        return $this->terminalService->getSelectData($request);
    }

    /**
     * Bulk delete terminals
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return response()->json(['error' => __('translation.no_records_found')], 400);
        }
        // dd($ids);
        $this->terminalService->bulkDelete(explode(',', $ids));
        return response()->json(['success' => __('translation.success_bulk_delete')]);
    }

    /**
     * Get terminals by merchant ID (for AJAX requests)
     */
    public function getByMerchant(Request $request)
    {
        $merchantId = $request->input('merchant_id');
        
        if (!$merchantId) {
            return response()->json(['error' => __('translation.merchant_id_required')], 400);
        }

        $terminals = $this->terminalService->getByMerchantId($merchantId);
        return response()->json($terminals);
    }

    /**
     * Get active terminals by merchant ID (for AJAX requests)
     */
    public function getActiveByMerchant(Request $request)
    {
        $merchantId = $request->input('merchant_id');
        
        if (!$merchantId) {
            return response()->json(['error' => __('translation.merchant_id_required')], 400);
        }

        $terminals = $this->terminalService->getActiveByMerchantId($merchantId);
        return response()->json($terminals);
    }

    /**
     * Get terminals by branch ID (for AJAX requests)
     */
    public function getByBranch(Request $request)
    {
        $branchId = $request->input('branch_id');
        
        if (!$branchId) {
            return response()->json(['error' => __('translation.branch_id_required')], 400);
        }

        $terminals = $this->terminalService->getByBranchId($branchId);
        return response()->json($terminals);
    }

    /**
     * Get active terminals by branch ID (for AJAX requests)
     */
    public function getActiveByBranch(Request $request)
    {
        $branchId = $request->input('branch_id');
        
        if (!$branchId) {
            return response()->json(['error' => __('translation.branch_id_required')], 400);
        }

        $terminals = $this->terminalService->getActiveByBranchId($branchId);
        return response()->json($terminals);
    }

    /**
     * Import terminals from file
     */
    public function import(Request $request)
    {
        try {
            $request->validate([
                'import_file' => 'required|file|mimes:xlsx,xls,csv|max:2048'
            ]);

            $result = $this->terminalService->import($request->file('import_file'));
            
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'imported_count' => $result['imported_count'],
                'errors' => $result['errors'] ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export template for terminals import
     */
    public function exportTemplate()
    {
        try {
            return $this->terminalService->exportTemplate();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to export template');
        }
    }

    /**
     * Export terminals data
     */
    public function export(Request $request)
    {
        try {
            return $this->terminalService->export($request);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to export data: ' . $e->getMessage());
        }
    }

    /**
     * Get all available terminal brands with pagination
     */
    public function getTermainlsbrands(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 50);
            $page = $request->input('page', 1);
            $search = $request->input('search', '');
            $statusFilter = $request->input('status', '');

            // Start building the query for brands only
            $query = Terminal::distinct()->select('brand');

            // Apply search filter
            if ($search) {
                $query->where('brand', 'like', "%{$search}%");
            }

            // Apply status filter
            if ($statusFilter !== '') {
                $query->where('is_active', $statusFilter === 'active');
            }

            // Get only brands that are not null or empty
            $brands = $query->whereNotNull('brand')
                           ->where('brand', '!=', '')
                           ->paginate($perPage, ['*'], 'page', $page);

            // Prepare the response with only brands
            $response = [
                'success' => true,
                'message' => 'Terminal brands retrieved successfully',
                'data' => $brands->getCollection()->pluck('brand'),
                'pagination' => [
                    'current_page' => $brands->currentPage(),
                    'last_page' => $brands->lastPage(),
                    'per_page' => $brands->perPage(),
                    'total' => $brands->total(),
                    'from' => $brands->firstItem(),
                    'to' => $brands->lastItem(),
                    'has_more_pages' => $brands->hasMorePages(),
                ],
                'applied_filters' => [
                    'search' => $search,
                    'status' => $statusFilter,
                ]
            ];

            return response()->json($response, 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving terminal brands: ' . $e->getMessage(),
                'data' => null,
                'pagination' => null,
                'applied_filters' => null,
            ], 500);
        }
    }

    /**
     * Get terminals with all filters applied
     */
   

    /**
     * Get all available models with pagination
     */
    public function getTermainlsmodels(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 50);
            $page = $request->input('page', 1);
            $search = $request->input('search', '');
            $brandFilter = $request->input('brand', '');

            $query = Terminal::distinct()->select('model');

            if ($search) {
                $query->where('model', 'like', "%{$search}%");
            }

            if ($brandFilter) {
                $query->where('brand', $brandFilter);
            }

            $models = $query->whereNotNull('model')
                           ->where('model', '!=', '')
                           ->where('is_assigned_to_group', false)
                           ->paginate($perPage, ['*'], 'page', $page);

            $response = [
                'success' => true,
                'message' => 'Models retrieved successfully',
                'data' => $models->getCollection()->pluck('model'),
                'pagination' => [
                    'current_page' => $models->currentPage(),
                    'last_page' => $models->lastPage(),
                    'per_page' => $models->perPage(),
                    'total' => $models->total(),
                    'from' => $models->firstItem(),
                    'to' => $models->lastItem(),
                    'has_more_pages' => $models->hasMorePages(),
                ]
            ];

            return response()->json($response, 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving models: ' . $e->getMessage(),
                'data' => null,
                'pagination' => null,
            ], 500);
        }
    }

    /**
     * Get all available manufacturers with pagination
     */
    public function getTermainlsmanufacturers(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 50);
            $page = $request->input('page', 1);
            $search = $request->input('search', '');
            $brandFilter = $request->input('brand', '');
            $modelFilter = $request->input('model', '');

            $query = Terminal::distinct()->select('manufacturer');

            if ($search) {
                $query->where('manufacturer', 'like', "%{$search}%");
            }

            if ($brandFilter) {
                $query->where('brand', $brandFilter);
            }

            if ($modelFilter) {
                $query->where('model', $modelFilter);
            }

            $manufacturers = $query->whereNotNull('manufacturer')
                                  ->where('manufacturer', '!=', '')
                                  ->where('is_assigned_to_group', false)
                                  ->paginate($perPage, ['*'], 'page', $page);

            $response = [
                'success' => true,
                'message' => 'Manufacturers retrieved successfully',
                'data' => $manufacturers->getCollection()->pluck('manufacturer'),
                'pagination' => [
                    'current_page' => $manufacturers->currentPage(),
                    'last_page' => $manufacturers->lastPage(),
                    'per_page' => $manufacturers->perPage(),
                    'total' => $manufacturers->total(),
                    'from' => $manufacturers->firstItem(),
                    'to' => $manufacturers->lastItem(),
                    'has_more_pages' => $manufacturers->hasMorePages(),
                ]
            ];

            return response()->json($response, 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving manufacturers: ' . $e->getMessage(),
                'data' => null,
                'pagination' => null,
            ], 500);
        }
    }

    /**
     * Get models for specific brands (hierarchical filtering)
     */
    public function getModelsByBrands(Request $request)
    {
        try {
            $brands = $request->input('brands', []);
            
            if (empty($brands)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Brands parameter is required',
                    'data' => []
                ], 400);
            }

            $query = Terminal::distinct()->select('model')
                ->whereIn('brand', $brands)
                ->whereNotNull('model')
                ->where('model', '!=', '');

            $models = $query->get()->pluck('model');

            return response()->json([
                'success' => true,
                'message' => 'Models retrieved successfully for selected brands',
                'data' => $models
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving models: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get manufacturers for specific models (hierarchical filtering)
     */
    public function getManufacturersByModels(Request $request)
    {
        try {
            $models = $request->input('models', []);
            
            if (empty($models)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Models parameter is required',
                    'data' => []
                ], 400);
            }

            $query = Terminal::distinct()->select('manufacturer')
                ->whereIn('model', $models)
                ->whereNotNull('manufacturer')
                ->where('manufacturer', '!=', '');

            $manufacturers = $query->get()->pluck('manufacturer');

            return response()->json([
                'success' => true,
                'message' => 'Manufacturers retrieved successfully for selected models',
                'data' => $manufacturers
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving manufacturers: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get all available brands with pagination
     */
    public function getBrands(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 50);
            $page = $request->input('page', 1);
            $search = $request->input('search', '');
            $statusFilter = $request->input('status', '');

            $query = Terminal::distinct()->select('brand');

            if ($search) {
                $query->where('brand', 'like', "%{$search}%");
            }

            if ($statusFilter !== '') {
                $query->where('is_active', $statusFilter === 'active');
            }

            $brands = $query->whereNotNull('brand')
                           ->where('brand', '!=', '')
                           ->paginate($perPage, ['*'], 'page', $page);

            $response = [
                'success' => true,
                'message' => 'Brands retrieved successfully',
                'data' => $brands->getCollection()->pluck('brand'),
                'pagination' => [
                    'current_page' => $brands->currentPage(),
                    'last_page' => $brands->lastPage(),
                    'per_page' => $brands->perPage(),
                    'total' => $brands->total(),
                    'from' => $brands->firstItem(),
                    'to' => $brands->lastItem(),
                    'has_more_pages' => $brands->hasMorePages(),
                ]
            ];

            return response()->json($response, 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving brands: ' . $e->getMessage(),
                'data' => null,
                'pagination' => null,
            ], 500);
        }
    }

    /**
     * Get terminals with all filters applied
     */
    public function getTermainlsWithFilters(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);
            $search = $request->input('search', '');
            $brandFilter = $request->input('brand', '');
            $modelFilter = $request->input('model', '');
            $manufacturerFilter = $request->input('manufacturer', '');
            $statusFilter = $request->input('status', '');
            $serialNoFilter = $request->input('serial_no', '');
            $sdkIdFilter = $request->input('sdk_id', '');
            $androidOsFilter = $request->input('android_os', '');
            $addTypeFilter = $request->input('add_type', '');
            $dateFrom = $request->input('date_from', '');
            $dateTo = $request->input('date_to', '');

            // Start building the query
            $query = Terminal::where('is_assigned_to_group', false)->where('is_active', true);
            // dd($request->all());
            // Apply search filter across multiple fields
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('terminal_id', 'like', "%{$search}%")
                      ->orWhere('brand', 'like', "%{$search}%")
                      ->orWhere('model', 'like', "%{$search}%")
                      ->orWhere('manufacturer', 'like', "%{$search}%")
                      ->orWhere('serial_no', 'like', "%{$search}%")
                      ->orWhere('sdk_id', 'like', "%{$search}%");
                });
            }

            // Apply individual filters
            if ($brandFilter) {
                if(str_contains($brandFilter, ',')) {
                    $brands = array_filter(array_map('trim', explode(',', $brandFilter)));
                    if (!empty($brands)) {
                        $query->whereIn('brand', $brands);
                    }
                } else {
                    $query->where('brand', trim($brandFilter));
                }
            }

            if ($modelFilter) {
                if(str_contains($modelFilter, ',')) {
                    $models = array_filter(array_map('trim', explode(',', $modelFilter)));
                    if (!empty($models)) {
                        $query->whereIn('model', $models);
                    }
                } else {
                    $query->where('model', trim($modelFilter));
                }
            }

            if ($manufacturerFilter) {
                if(str_contains($manufacturerFilter, ',')) {
                    $manufacturers = array_filter(array_map('trim', explode(',', $manufacturerFilter)));
                    if (!empty($manufacturers)) {
                        $query->whereIn('manufacturer', $manufacturers);
                    }
                } else {
                    $query->where('manufacturer', trim($manufacturerFilter));
                }
            }


            // Get the paginated results
            $terminals = $query->paginate($perPage, ['*'], 'page', $page);

            // Transform the terminals data
            $terminalsData = $terminals->getCollection()->map(function ($terminal) {
                return [
                    'id' => $terminal->id,
                    'name' => $terminal->name,
                    'terminal_id' => $terminal->terminal_id,
                    'brand' => $terminal->brand,
                    'model' => $terminal->model,
                    'manufacturer' => $terminal->manufacturer,
                    'serial_no' => $terminal->serial_no,
                    'sdk_id' => $terminal->sdk_id,
                    'sdk_version' => $terminal->sdk_version,
                    'android_os' => $terminal->android_os,
                    'add_type' => $terminal->add_type,
                    'is_active' => $terminal->is_active,
                    'status_display' => $terminal->status_display_name,
                    'created_at' => $terminal->created_at,
                    'updated_at' => $terminal->updated_at,
                ];
            });

            // Get available filter options
            $availableBrands = Terminal::distinct()->pluck('brand')->filter()->values();
            $availableModels = Terminal::distinct()->pluck('model')->filter()->values();
            $availableManufacturers = Terminal::distinct()->pluck('manufacturer')->filter()->values();
            $availableAndroidOs = Terminal::distinct()->pluck('android_os')->filter()->values();
            $availableAddTypes = Terminal::distinct()->pluck('add_type')->filter()->values();

            // Prepare the response
            $response = [
                'success' => true,
                'message' => 'Terminals retrieved with filters successfully',
                'data' => $terminalsData,
                'pagination' => [
                    'current_page' => $terminals->currentPage(),
                    'last_page' => $terminals->lastPage(),
                    'per_page' => $terminals->perPage(),
                    'total' => $terminals->total(),
                    'from' => $terminals->firstItem(),
                    'to' => $terminals->lastItem(),
                    'has_more_pages' => $terminals->hasMorePages(),
                ],
                'filters' => [
                    'available_brands' => $availableBrands,
                    'available_models' => $availableModels,
                    'available_manufacturers' => $availableManufacturers,
                    'available_android_os' => $availableAndroidOs,
                    'available_add_types' => $availableAddTypes,
                ],
                'applied_filters' => [
                    'search' => $search,
                    'brand' => $brandFilter,
                    'model' => $modelFilter,
                    'manufacturer' => $manufacturerFilter,
                    'status' => $statusFilter,
                    'serial_no' => $serialNoFilter,
                    'sdk_id' => $sdkIdFilter,
                    'android_os' => $androidOsFilter,
                    'add_type' => $addTypeFilter,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ]
            ];

            return response()->json($response, 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving terminals: ' . $e->getMessage(),
                'data' => null,
                'pagination' => null,
                'filters' => null,
                'applied_filters' => null,
            ], 500);
        }
    }
   
}
