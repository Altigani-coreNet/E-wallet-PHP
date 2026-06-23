<?php

namespace App\Http\Controllers;

use App\Http\Requests\TerminalRequest;
use App\Models\Terminal;
use App\Services\TerminalService;
use App\Traits\Select2Trait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MerchantTerminalController extends Controller
{
    use Select2Trait;
    
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
        return view('merchant.terminals.index',['has_toolbar' => true]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('merchant.terminals.create',['has_toolbar' => true]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TerminalRequest $request)
    {
        $terminal = $this->terminalService->store($request, \Auth::user()->merchant_id);
        return redirect()->route('merchant.terminals.index')->with('success', 'Terminal created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Terminal $terminal)
    {
        // Ensure the terminal belongs to the authenticated merchant
        if ($terminal->merchant_id !== auth()->user()->merchant_id) {
            abort(403, 'Unauthorized access to this terminal.');
        }
        
        return view('merchant.terminals.show', compact('terminal'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit( $terminal)
    {
        // Ensure the terminal belongs to the authenticated merchant
        // if ($terminal->merchant_id !== auth()->user()->merchant_id) {
        //     abort(403, 'Unauthorized access to this terminal.');
        // }
        
        return view('merchant.terminals.edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TerminalRequest $request, Terminal $terminal)
    {
        // Ensure the terminal belongs to the authenticated merchant
        if ($terminal->merchant_id !== auth()->user()->merchant_id) {
            abort(403, 'Unauthorized access to this terminal.');
        }
        
        $this->terminalService->update($request, $terminal);
        return redirect()->route('merchant.terminals.index')->with('success', 'Terminal updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Terminal $terminal)
    {
        // Ensure the terminal belongs to the authenticated merchant
        if ($terminal->merchant_id !== auth()->user()->merchant_id) {
            abort(403, 'Unauthorized access to this terminal.');
        }
        
        $this->terminalService->destroy($terminal);
        return redirect()->route('merchant.terminals.index')->with('success', 'Terminal deleted successfully');
    }

    /**
     * Get DataTable data
     */
    public function data()
    {
        return $this->terminalService->data(auth()->user()->merchant_id);
    }

    /**
     * Bulk delete terminals
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return response()->json(['error' => 'No terminals selected'], 400);
        }
        // dd($ids);

        $this->terminalService->bulkDelete($ids, auth()->user()->merchant_id);
        return response()->json(['success' => 'Terminals deleted successfully']);
    }

    /**
     * Get terminals for select dropdown
     */
    public function select(Request $request): JsonResponse
    {
        $search = $request->get('search');
        
        $query = Terminal::where('merchant_id', auth()->user()->merchant_id);
        
        return $this->getSelect2DataInNormalSearch($request, $query, ['name', 'terminal_id']);
    }

    /**
     * Preview terminals import from file
     */
    public function importPreview(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:2048'
        ]);

        try {
            $result = $this->terminalService->importPreview($request->file('import_file'), auth()->user()->merchant_id);
            
            return response()->json([
                'success' => true,
                'rows' => $result['rows'] ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Preview failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Import terminals from file
     */
    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:2048'
        ]);

        try {
            $result = $this->terminalService->import($request->file('import_file'), auth()->user()->merchant_id);
            
            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Terminals imported successfully',
                'imported_count' => $result['imported_count'] ?? 0,
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
            return $this->terminalService->exportTemplate(auth()->user()->merchant_id);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export template: ' . $e->getMessage());
        }
    }

    /**
     * Export terminals data
     */
    public function export(Request $request)
    {
        try {
            $search = $request->get('search', '');
            $orderColumn = $request->get('order_column', 1);
            $orderDirection = $request->get('order_direction', 'asc');
            
            // Get terminals filtered by merchant
            $terminals = Terminal::where('merchant_id', auth()->user()->merchant_id)
                                ->with(['branch']);
            
            // Apply search filter
            if (!empty($search)) {
                $terminals->where(function($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                          ->orWhere('terminal_id', 'like', '%' . $search . '%')
                          ->orWhere('model', 'like', '%' . $search . '%')
                          ->orWhere('manufacturer', 'like', '%' . $search . '%');
                });
            }
            
            // Apply ordering
            $columns = ['id', 'name', 'terminal_id', 'branch_id', 'model', 'manufacturer', 'is_active', 'created_at', 'updated_at'];
            if (isset($columns[$orderColumn])) {
                $terminals->orderBy($columns[$orderColumn], $orderDirection);
            } else {
                $terminals->orderBy('name', 'asc');
            }
            
            $terminals = $terminals->get();
            
            // Generate CSV
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="terminals_export_' . date('Y-m-d_H-i-s') . '.csv"',
            ];

            $callback = function() use ($terminals) {
                $file = fopen('php://output', 'w');
                
                // Add headers
                fputcsv($file, [
                    'ID',
                    'Name',
                    'Terminal ID',
                    'Branch',
                    'Model',
                    'Manufacturer',
                    'Serial No',
                    'SDK ID',
                    'SDK Version',
                    'Android OS',
                    'Add Type',
                    'Status',
                    'Created At',
                    'Updated At'
                ]);
                
                // Add data
                foreach ($terminals as $terminal) {
                    fputcsv($file, [
                        $terminal->id,
                        $terminal->name,
                        $terminal->terminal_id,
                        $terminal->branch->name ?? 'N/A',
                        $terminal->model ?? 'N/A',
                        $terminal->manufacturer ?? 'N/A',
                        $terminal->serial_no ?? 'N/A',
                        $terminal->sdk_id ?? 'N/A',
                        $terminal->sdk_version ?? 'N/A',
                        $terminal->android_os ?? 'N/A',
                        $terminal->add_type ?? 'N/A',
                        $terminal->is_active ? 'Active' : 'Inactive',
                        $terminal->created_at->format('Y-m-d H:i:s'),
                        $terminal->updated_at->format('Y-m-d H:i:s')
                    ]);
                }
                
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export data: ' . $e->getMessage());
        }
    }

    /**
     * Get terminals by branch ID (for AJAX requests)
     */
    public function getByBranch(Request $request)
    {
        $branchId = $request->input('branch_id');
        
        if (!$branchId) {
            return response()->json(['error' => 'Branch ID is required'], 400);
        }

        // Ensure the branch belongs to the authenticated merchant
        $branch = \App\Models\Branch::where('id', $branchId)
                                   ->where('merchant_id', auth()->user()->merchant_id)
                                   ->first();
        
        if (!$branch) {
            return response()->json(['error' => 'Branch not found or unauthorized'], 404);
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
            return response()->json(['error' => 'Branch ID is required'], 400);
        }

        // Ensure the branch belongs to the authenticated merchant
        $branch = \App\Models\Branch::where('id', $branchId)
                                   ->where('merchant_id', auth()->user()->merchant_id)
                                   ->first();
        
        if (!$branch) {
            return response()->json(['error' => 'Branch not found or unauthorized'], 404);
        }

        $terminals = $this->terminalService->getActiveByBranchId($branchId);
        return response()->json($terminals);
    }
} 