<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TerminalRequest;
use App\Http\Resources\TerminalResource;
use App\Models\Terminal;
use App\Services\TerminalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MerchantTerminalApiController extends Controller
{
    protected $terminalService;

    public function __construct(TerminalService $terminalService)
    {
        $this->terminalService = $terminalService;
    }

    protected function getMerchantId(): string
    {
        $user = auth()->guard('external')->user() ?? auth()->user();

        return $user->merchant_id ?? $user->merchant->id;
    }

    /**
     * List all terminals with pagination (TerminalResource).
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $merchantId = $this->getMerchantId();
            $perPage = (int) $request->get('per_page', 15);
            $search = $request->get('search', '');

            $query = Terminal::where('merchant_id', $merchantId);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('terminal_id', 'like', '%' . $search . '%')
                        ->orWhere('model', 'like', '%' . $search . '%')
                        ->orWhere('manufacturer', 'like', '%' . $search . '%')
                        ->orWhere('brand', 'like', '%' . $search . '%');
                });
            }

            if ($request->filled('status')) {
                $query->where('is_active', $request->get('status') === 'active' ? 1 : 0);
            }

            if ($request->filled('terminal_status')) {
                $query->where('terminal_status', $request->get('terminal_status'));
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->get('date_from'));
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->get('date_to'));
            }

            $terminals = $query
                ->with('branch')
                ->orderBy('name', 'asc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Terminals retrieved successfully',
                'data' => [
                    'data' => TerminalResource::collection($terminals->items()),
                    'current_page' => $terminals->currentPage(),
                    'per_page' => $terminals->perPage(),
                    'total' => $terminals->total(),
                    'last_page' => $terminals->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching terminals: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch terminals',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Terminal $terminal): JsonResponse
    {
        try {
            $merchantId = $this->getMerchantId();

            if ($terminal->merchant_id !== $merchantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this terminal',
                ], 403);
            }

            $terminal->load('branch');

            return response()->json([
                'success' => true,
                'message' => 'Terminal retrieved successfully',
                'data' => new TerminalResource($terminal),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching terminal: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch terminal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(TerminalRequest $request): JsonResponse
    {
        try {
            $merchantId = $this->getMerchantId();
            $terminal = $this->terminalService->store($request, $merchantId);
            $terminal->load('branch');

            return response()->json([
                'success' => true,
                'message' => 'Terminal created successfully',
                'data' => new TerminalResource($terminal),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating terminal: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create terminal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(TerminalRequest $request, Terminal $terminal): JsonResponse
    {
        try {
            $merchantId = $this->getMerchantId();

            if ($terminal->merchant_id !== $merchantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this terminal',
                ], 403);
            }

            $terminal = $this->terminalService->update($request, $terminal);
            $terminal->load('branch');

            return response()->json([
                'success' => true,
                'message' => 'Terminal updated successfully',
                'data' => new TerminalResource($terminal),
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating terminal: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update terminal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Terminal $terminal): JsonResponse
    {
        try {
            $merchantId = $this->getMerchantId();

            if ($terminal->merchant_id !== $merchantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this terminal',
                ], 403);
            }

            $this->terminalService->destroy($terminal);

            return response()->json([
                'success' => true,
                'message' => 'Terminal deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting terminal: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete terminal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $merchantId = $this->getMerchantId();
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No terminals selected',
                ], 400);
            }

            $this->terminalService->bulkDelete($ids, $merchantId);

            return response()->json([
                'success' => true,
                'message' => 'Terminals deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error bulk deleting terminals: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete terminals',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function select(Request $request): JsonResponse
    {
        try {
            $merchantId = $this->getMerchantId();
            $search = $request->get('search', '');

            $query = Terminal::where('merchant_id', $merchantId)
                ->where('is_active', true);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('terminal_id', 'like', '%' . $search . '%');
                });
            }

            $terminals = $query->orderBy('name', 'asc')
                ->limit(50)
                ->get(['id', 'name', 'terminal_id']);

            return response()->json([
                'success' => true,
                'data' => $terminals,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching terminals for select: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch terminals',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getByBranch(Request $request): JsonResponse
    {
        try {
            $merchantId = $this->getMerchantId();
            $branchId = $request->input('branch_id');

            if (!$branchId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch ID is required',
                ], 400);
            }

            $branch = \App\Models\Branch::where('id', $branchId)
                ->where('merchant_id', $merchantId)
                ->first();

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch not found or unauthorized',
                ], 404);
            }

            $terminals = $this->terminalService->getByBranchId($branchId);

            return response()->json([
                'success' => true,
                'data' => TerminalResource::collection($terminals),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching terminals by branch: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch terminals',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function export(Request $request)
    {
        try {
            $merchantId = $this->getMerchantId();
            $request->merge(['merchant_id' => $merchantId]);

            return $this->terminalService->export($request);
        } catch (\Exception $e) {
            Log::error('Error exporting terminals: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to export data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function exportTemplate()
    {
        try {
            $merchantId = $this->getMerchantId();

            return $this->terminalService->exportTemplate($merchantId);
        } catch (\Exception $e) {
            Log::error('Error exporting template: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to export template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function importPreview(Request $request): JsonResponse
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ]);

        try {
            $merchantId = $this->getMerchantId();
            $result = $this->terminalService->importPreview($request->file('import_file'), $merchantId);

            return response()->json([
                'success' => true,
                'rows' => $result['rows'] ?? [],
            ]);
        } catch (\Exception $e) {
            Log::error('Error previewing import: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Preview failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ]);

        try {
            $merchantId = $this->getMerchantId();
            $result = $this->terminalService->import($request->file('import_file'), $merchantId);

            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Terminals imported successfully',
                'imported_count' => $result['imported_count'] ?? 0,
                'errors' => $result['errors'] ?? [],
            ]);
        } catch (\Exception $e) {
            Log::error('Error importing terminals: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
