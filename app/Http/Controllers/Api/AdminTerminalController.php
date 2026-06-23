<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TerminalRequest;
use App\Models\Terminal;
use App\Services\TerminalService;
use App\Traits\ApiResponse;
use Illuminate\Http\{Request, JsonResponse};

class AdminTerminalController extends Controller
{
    use ApiResponse;

    protected $terminalService;

    public function __construct(TerminalService $terminalService)
    {
        $this->terminalService = $terminalService;
    }

    /**
     * Get all terminals with pagination, search, and filters
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
     * Get terminal details
     */
    public function show(string $id): JsonResponse
    {
        try {
            $terminal = Terminal::findOrFail($id);
            return $this->SuccessMessage($terminal);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch terminal: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Create a new terminal
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'merchant_id' => 'required|exists:merchants,id',
                'branch_id' => 'nullable|exists:branches,id',
                'user_id' => 'nullable|exists:users,id',
                'terminal_id' => 'nullable|string|unique:terminals,terminal_id',
                'name' => 'required|string|max:255',
                'serial_number' => 'nullable|string|max:255',
                'serial_no' => 'nullable|string|max:255',
                'model' => 'nullable|string|max:255',
                'brand' => 'nullable|string|max:255',
                'manufacturer' => 'nullable|string|max:255',
                'sdk_id' => 'nullable|string|max:255',
                'sdk_version' => 'nullable|string|max:255',
                'android_os' => 'nullable|string|max:255',
                'add_type' => 'nullable|string|in:static,auto',
                'is_active' => 'nullable|boolean',
                'terminal_status' => 'nullable|in:online,offline,testing,maintenance',
            ]);

            // Map serial_number to serial_no if provided
            if (isset($validated['serial_number'])) {
                $validated['serial_no'] = $validated['serial_number'];
                unset($validated['serial_number']);
            }

            $terminal = $this->terminalService->createTerminal($validated);

            return $this->SuccessMessage($terminal, 201);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create terminal: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update terminal
     */
    public function update(TerminalRequest $request, string $id): JsonResponse
    {
        try {
            $terminal = Terminal::findOrFail($id);

            $terminal = $this->terminalService->update($request, $terminal);

            return $this->SuccessMessage($terminal);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update terminal: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Delete terminal
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $terminal = Terminal::findOrFail($id);
            $this->terminalService->destroy($terminal);

            return $this->SuccessMessage(['message' => 'Terminal deleted successfully']);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete terminal: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get terminal statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_terminals' => Terminal::count(),
                'online_terminals' => Terminal::where('terminal_status', 'online')->count(),
                'offline_terminals' => Terminal::where('terminal_status', 'offline')->count(),
                'testing_terminals' => Terminal::where('terminal_status', 'testing')->count(),
                'maintenance_terminals' => Terminal::where('terminal_status', 'maintenance')->count(),
                'terminals_this_month' => Terminal::whereMonth('created_at', now()->month)->count(),
                'terminals_today' => Terminal::whereDate('created_at', now())->count(),
            ];

            return $this->SuccessMessage($stats);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch statistics: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export terminals
     */
    public function export(Request $request)
    {
        try {
            // Map date_from/date_to to from_date/to_date for service
            $exportRequest = clone $request;
            if ($request->has('date_from')) {
                $exportRequest->merge(['from_date' => $request->input('date_from')]);
            }
            if ($request->has('date_to')) {
                $exportRequest->merge(['to_date' => $request->input('date_to')]);
            }

            return $this->terminalService->export($exportRequest);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to export terminals: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk delete terminals
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:terminals,id'
            ]);

            $this->terminalService->bulkDelete($validated['ids']);

            return $this->SuccessMessage([
                'message' => count($validated['ids']) . " terminal(s) deleted successfully",
                'count' => count($validated['ids'])
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete terminals: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk status change
     */
    public function bulkStatusChange(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:terminals,id',
                'status' => 'required|in:online,offline,testing,maintenance'
            ]);

            $count = Terminal::whereIn('id', $validated['ids'])
                ->update(['terminal_status' => $validated['status']]);

            return $this->SuccessMessage([
                'message' => "{$count} terminal(s) status updated successfully",
                'count' => $count
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update terminal status: ' . $e->getMessage(), null, 500);
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview terminals import from file
     */
    public function importPreview(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'import_file' => 'required|file|mimes:xlsx,xls,csv',
                'merchant_id' => 'required|exists:merchants,id'
            ]);

            $file = $request->file('import_file');
            $merchantId = $request->input('merchant_id');

            $result = $this->terminalService->importPreview($file, $merchantId);

            return $this->SuccessMessage([
                'rows' => $result['rows'],
                'total' => count($result['rows']),
                'valid' => count(array_filter($result['rows'], fn($r) => !($r['flags']['error'] ?? false))),
                'invalid' => count(array_filter($result['rows'], fn($r) => ($r['flags']['error'] ?? false)))
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Preview failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Import terminals from file
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'import_file' => 'required|file|mimes:xlsx,xls,csv',
                'merchant_id' => 'required|exists:merchants,id'
            ]);

            $file = $request->file('import_file');
            $merchantId = $request->input('merchant_id');

            $result = $this->terminalService->import($file, $merchantId);

            return $this->SuccessMessage([
                'message' => $result['message'],
                'imported_count' => $result['imported_count'],
                'errors' => $result['errors']
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Import failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get terminals for filter/select (used in terminal group form)
     */
    public function filters(Request $request): JsonResponse
    {
        try {
            $query = Terminal::query();

            // Filter by merchant if provided
            if ($merchantId = $request->input('merchant_id')) {
                $query->where('merchant_id', $merchantId);
            }

            // Filter by brand
            if ($brand = $request->input('brand')) {
                $brands = explode(',', $brand);
                $query->whereIn('brand', $brands);
            }

            // Filter by model
            if ($model = $request->input('model')) {
                $models = explode(',', $model);
                $query->whereIn('model', $models);
            }

            // Filter by manufacturer
            if ($manufacturer = $request->input('manufacturer')) {
                $manufacturers = explode(',', $manufacturer);
                $query->whereIn('manufacturer', $manufacturers);
            }

            // Search
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('terminal_id', 'like', "%{$search}%");
                });
            }

            // Only active terminals
            $query->where('is_active', true);

            $terminals = $query->get();

            return $this->SuccessMessage($terminals);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch terminals: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get all brands
     */
    public function brands(): JsonResponse
    {
        try {
            $brands = Terminal::whereNotNull('brand')
                ->distinct()
                ->pluck('brand')
                ->filter()
                ->values();

            return $this->SuccessMessage($brands);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch brands: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get models filtered by brands
     */
    public function modelsByBrands(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'brands' => 'required|array',
                'brands.*' => 'string'
            ]);

            $models = Terminal::whereIn('brand', $validated['brands'])
                ->whereNotNull('model')
                ->distinct()
                ->pluck('model')
                ->filter()
                ->values();

            return $this->SuccessMessage($models);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch models: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get manufacturers filtered by models
     */
    public function manufacturersByModels(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'models' => 'required|array',
                'models.*' => 'string'
            ]);

            $manufacturers = Terminal::whereIn('model', $validated['models'])
                ->whereNotNull('manufacturer')
                ->distinct()
                ->pluck('manufacturer')
                ->filter()
                ->values();

            return $this->SuccessMessage($manufacturers);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch manufacturers: ' . $e->getMessage(), null, 500);
        }
    }
}



