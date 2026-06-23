<?php

namespace App\Http\Controllers;

use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use App\Services\BranchService;
use App\Traits\Select2Trait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Str;

class MerchantBranchController extends Controller
{
    use AuthorizesRequests, Select2Trait;
    
    protected $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!auth()->user()->can('branches') && !auth()->user()->can('view_branches')) {
            abort(403, 'Unauthorized access to branches.');
        }
        return view('merchant.branches.index', ['has_toolbar' => true]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!auth()->user()->can('branches') && !auth()->user()->can('request_branches')) {
            abort(403, 'Unauthorized access to create branches.');
        }
        return view('merchant.branches.create', ['has_toolbar' => true]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BranchRequest $request)
    {
        if (!auth()->user()->can('branches') && !auth()->user()->can('request_branches')) {
            abort(403, 'Unauthorized access to create branches.');
        }
        $branch = $this->branchService->store($request, auth()->user()->merchant_id);
        return redirect()->route('merchant.branches.index')->with('success', 'Branch request submitted successfully. It will be reviewed by administrators.');
    }

    /**
     * Display the specified resource.
     */
    public function show($branch)
    {
        return view('merchant.branches.show', ['branch' => $branch]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($branch)
    {
        return view('merchant.branches.edit', ['branch' => $branch, 'has_toolbar' => true]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BranchRequest $request, Branch $branch)
    {
        if (!auth()->user()->can('branches') && !auth()->user()->can('edit_branches')) {
            abort(403, 'Unauthorized access to edit branches.');
        }
        // Ensure the branch belongs to the authenticated merchant
        if ($branch->merchant_id !== auth()->user()->merchant_id) {
            abort(403, 'Unauthorized access to this branch.');
        }
        
        $this->branchService->update($request, $branch);
        return redirect()->route('merchant.branches.index')->with('success', 'Branch updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch)
    {
        if (!auth()->user()->can('branches') && !auth()->user()->can('delete_branches')) {
            abort(403, 'Unauthorized access to delete branches.');
        }
        // Ensure the branch belongs to the authenticated merchant
        if ($branch->merchant_id !== auth()->user()->merchant_id) {
            abort(403, 'Unauthorized access to this branch.');
        }
        
        $this->branchService->destroy($branch);
        return redirect()->route('merchant.branches.index')->with('success', 'Branch deleted successfully');
    }

    /**
     * Get DataTable data
     */
    public function data()
    {
        if (!auth()->user()->can('branches') && !auth()->user()->can('view_branches')) {
            abort(403, 'Unauthorized access to branches.');
        }
        return $this->branchService->data(auth()->user()->merchant_id);
    }

    /**
     * Bulk delete branches
     */
    public function bulkDelete(Request $request)
    {
        if (!auth()->user()->can('branches') && !auth()->user()->can('delete_branches')) {
            abort(403, 'Unauthorized access to delete branches.');
        }
        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return response()->json(['error' => 'No branches selected'], 400);
        }

        $this->branchService->bulkDelete($ids, auth()->user()->merchant_id);
        return response()->json(['success' => 'Branches deleted successfully']);
    }

    /**
     * Get branches for select dropdown
     */
    public function select(Request $request): JsonResponse
    {
        if (!auth()->user()->can('branches') && !auth()->user()->can('view_branches')) {
            abort(403, 'Unauthorized access to branches.');
        }
        $search = $request->get('search');
        
        $query = Branch::where('merchant_id', auth()->user()->merchant_id)
                      ->where('status', '!=', 'pending'); // Don't show pending branches in dropdown
        
        return $this->getSelect2DataInNormalSearch($request, $query, ['name']);
    }

    /**
     * Import branches from file
     */
    public function import(Request $request)
    {
        if (!auth()->user()->can('branches') && !auth()->user()->can('request_branches')) {
            abort(403, 'Unauthorized access to import branches.');
        }
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:2048'
        ]);

        try {
            $result = $this->branchService->import($request->file('import_file'), auth()->user()->merchant_id);
            
            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Branches imported successfully',
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
     * Preview branches import data before actual import
     */
    public function importPreview(Request $request): JsonResponse
    {
        if (!auth()->user()->can('branches') && !auth()->user()->can('request_branches')) {
            abort(403, 'Unauthorized access to import branches.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],
        ]);

        $merchantId = auth()->user()->merchant_id;
        $filePath = $request->file('file')->getRealPath();

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();
            $highestColumn = $sheet->getHighestDataColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

            // Headers
            $headers = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $value = trim((string) $sheet->getCell($colLetter . '1')->getValue());
                $headers[$col] = Str::of($value)->lower()->trim()->toString();
            }

            $headerAliasMap = [
                'name' => ['name', 'branch_name', 'branch name'],
                'address' => ['address', 'street', 'location'],
                'is_active' => ['is_active', 'is active', 'active', 'status'],
            ];

            $columnKeyByIndex = [];
            foreach ($headers as $idx => $header) {
                foreach ($headerAliasMap as $key => $aliases) {
                    if (in_array($header, $aliases, true)) {
                        $columnKeyByIndex[$idx] = $key;
                        break;
                    }
                }
            }

            $rows = [];
            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = [];
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    if (!isset($columnKeyByIndex[$col])) continue;
                    $key = $columnKeyByIndex[$col];
                    $colLetter = Coordinate::stringFromColumnIndex($col);
                    $value = $sheet->getCell($colLetter . $row)->getFormattedValue();
                    $rowData[$key] = is_string($value) ? trim($value) : $value;
                }

                if (!array_filter($rowData)) continue;

                // Check for duplicate branch name
                $duplicateName = false;
                if (isset($rowData['name']) && !empty($rowData['name'])) {
                    $duplicateName = Branch::where('merchant_id', $merchantId)
                        ->where('name', $rowData['name'])
                        ->exists();
                }

                // Validate is_active field
                $isActiveValid = true;
                if (isset($rowData['is_active'])) {
                    $activeValue = strtolower((string) $rowData['is_active']);
                    $isActiveValid = in_array($activeValue, ['1', '0', 'true', 'false', 'yes', 'no', 'active', 'inactive']);
                }

                $rows[] = [
                    'original' => $rowData,
                    'flags' => [
                        'duplicateName' => (bool) $duplicateName,
                        'isActiveValid' => $isActiveValid,
                        'hasName' => isset($rowData['name']) && !empty($rowData['name']),
                        'hasAddress' => isset($rowData['address']) && !empty($rowData['address']),
                    ]
                ];
            }

            return response()->json([
                'success' => true,
                'rows' => $rows,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to parse file: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Export template for branches import
     */
    public function exportTemplate()
    {
        if (!auth()->user()->can('branches') && !auth()->user()->can('request_branches')) {
            abort(403, 'Unauthorized access to export template.');
        }
        try {
            return $this->branchService->exportTemplate(auth()->user()->merchant_id);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export template: ' . $e->getMessage());
        }
    }

    /**
     * Export branches data
     */
    public function export(Request $request)
    {
        if (!auth()->user()->can('branches') && !auth()->user()->can('view_branches')) {
            abort(403, 'Unauthorized access to export branches.');
        }
        try {
            $filters = [
                'search' => $request->get('search'),
                'status' => $request->get('status'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ];

            return $this->branchService->export($filters, auth()->user()->merchant_id);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export data: ' . $e->getMessage());
        }
    }
} 