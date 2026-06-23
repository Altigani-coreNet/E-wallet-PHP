<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TerminalRequest;
use App\Models\Terminal;
use App\Services\TerminalService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class TerminalController extends Controller
{
    use ApiResponse;

    protected $terminalService;

    public function __construct(TerminalService $terminalService)
    {
        $this->terminalService = $terminalService;
    }


    public function getBrands(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 50);
            $page = $request->input('page', 1);
            $search = $request->input('search', '');
            $modelFilter = $request->input('model', '');
            $manufacturerFilter = $request->input('manufacturer', '');

            $query = Terminal::distinct()->select('brand');

            if ($search) {
                $query->where('brand', 'like', "%{$search}%");
            }

            if ($modelFilter) {
                $query->where('model', $modelFilter);
            }

            if ($manufacturerFilter) {
                $query->where('manufacturer', $manufacturerFilter);
            }

            $brands = $query->whereNotNull('brand')
                           ->where('brand', '!=', '')
                           ->where('is_assigned_to_group', false)
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
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $terminals = $this->terminalService->getAllTerminals($request);
            return $this->SuccessMessage($terminals);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch terminals: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TerminalRequest $request): JsonResponse
    {
        try {
            $terminal = $this->terminalService->createTerminal($request->validated());
            return $this->SuccessMessage($terminal, 201);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create terminal: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Terminal $terminal): JsonResponse
    {
        try {
            return $this->SuccessMessage($terminal);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch terminal: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TerminalRequest $request, Terminal $terminal): JsonResponse
    {
        try {
            $updatedTerminal = $this->terminalService->update($request, $terminal);
            return $this->SuccessMessage($updatedTerminal);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update terminal: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Terminal $terminal): JsonResponse
    {
        try {
            $this->terminalService->delete($terminal);
            return $this->SuccessMessage(['message' => 'Terminal deleted successfully']);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete terminal: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get terminals data for DataTables
     */
    public function data(Request $request): JsonResponse
    {
        try {
            $data = $this->terminalService->getDataTableData($request);
            return $this->SuccessMessage($data);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch terminals data: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get terminals for select dropdown
     */
    public function select(Request $request): JsonResponse
    {
        try {
            $terminals = $this->terminalService->getTerminalsForSelect($request);
            return $this->SuccessMessage($terminals);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch terminals for select: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get terminals by merchant
     */
    public function getByMerchant(Request $request): JsonResponse
    {
        try {
            $request->validate(['merchant_id' => 'required|exists:merchants,id']);
            $terminals = $this->terminalService->getByMerchant($request->merchant_id);
            return $this->SuccessMessage($terminals);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch terminals by merchant: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get active terminals by merchant
     */
    public function getActiveByMerchant(Request $request): JsonResponse
    {
        try {
            $request->validate(['merchant_id' => 'required|exists:merchants,id']);
            $terminals = $this->terminalService->getActiveByMerchant($request->merchant_id);
            return $this->SuccessMessage($terminals);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch active terminals by merchant: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get terminals by branch
     */
    public function getByBranch(Request $request): JsonResponse
    {
        try {
            $request->validate(['branch_id' => 'required|exists:branches,id']);
            $terminals = $this->terminalService->getByBranch($request->branch_id);
            return $this->SuccessMessage($terminals);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch terminals by branch: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get active terminals by branch
     */
    public function getActiveByBranch(Request $request): JsonResponse
    {
        try {
            $request->validate(['branch_id' => 'required|exists:branches,id']);
            $terminals = $this->terminalService->getActiveByBranch($request->branch_id);
            return $this->SuccessMessage($terminals);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch active terminals by branch: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk delete terminals
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:terminals,id'
            ]);

            dd($request->ids);
            
            $this->terminalService->bulkDelete($request->ids);
            return $this->SuccessMessage(['message' => 'Terminals deleted successfully']);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete terminals: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Import terminals
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv'
            ]);

            $result = $this->terminalService->import($request->file('file'));
            return $this->SuccessMessage($result);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to import terminals: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export template
     */
    public function exportTemplate(): JsonResponse
    {
        try {
            $template = $this->terminalService->exportTemplate();
            return $this->SuccessMessage($template);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to export template: ' . $e->getMessage(), null, 500);
        }
    }

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
            $query = Terminal::query();

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
                $query->where('brand', $brandFilter);
            }

            if ($modelFilter) {
                $query->where('model', $modelFilter);
            }

            if ($manufacturerFilter) {
                $query->where('manufacturer', $manufacturerFilter);
            }

            if ($statusFilter !== '') {
                $query->where('is_active', $statusFilter === 'active');
            }

            if ($serialNoFilter) {
                $query->where('serial_no', 'like', "%{$serialNoFilter}%");
            }

            if ($sdkIdFilter) {
                $query->where('sdk_id', 'like', "%{$sdkIdFilter}%");
            }

            if ($androidOsFilter) {
                $query->where('android_os', 'like', "%{$androidOsFilter}%");
            }

            if ($addTypeFilter) {
                $query->where('add_type', $addTypeFilter);
            }

            // Apply date range filters
            if ($dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $query->where('created_at', '<=', $dateTo . ' 23:59:59');
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

    /**
     * Register or retrieve terminal by device information
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function registerOrRetrieveTerminal(Request $request): JsonResponse
    {
        try {
            // Validate request data
            $request->validate([
                'device_id' => 'required|string',
                'brand' => 'nullable|string',
                'model' => 'nullable|string',
                'manufacturer' => 'nullable|string',
                'serial_no' => 'nullable|string',
                'sdk_id' => 'nullable|string',
                'sdk_version' => 'nullable|string',
                'android_os' => 'nullable|string',
            ]);

            $deviceData = $request->only([
                'device_id',
                'brand',
                'model',
                'manufacturer',
                'serial_no',
                'sdk_id',
                'sdk_version',
                'android_os'
            ]);

            // Call service method
            $result = $this->terminalService->registerOrRetrieveTerminal($deviceData);

            if ($result['success']) {
                return $this->SuccessMessage([
                    'terminal_id' => $result['terminal_id'],
                    'is_new' => $result['is_new'],
                    'message' => $result['message']
                ]);
            } else {
                return $this->ErrorMessage($result['message'], null, 400);
            }

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to process terminal registration: ' . $e->getMessage(), null, 500);
        }
    }

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
} 