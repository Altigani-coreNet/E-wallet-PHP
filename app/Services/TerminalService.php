<?php

namespace App\Services;

use App\Models\Terminal;
use App\Repositories\TerminalRepository;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class TerminalService
{
    protected $terminalRepository;

    public function __construct(TerminalRepository $terminalRepository)
    {
        $this->terminalRepository = $terminalRepository;
    }

    /**
     * Display a listing of terminals
     */
    public function index()
    {
        return view('terminals.index');
    }

    /**
     * Show the form for creating a new terminal
     */
    public function create()
    {
        return view('terminals.create');
    }

    /**
     * Create a new terminal with data
     */
    public function createTerminal(array $data)
    {
        // Generate unique terminal ID if not provided
        if (empty($data['terminal_id'])) {
            $data['terminal_id'] = Terminal::generateTerminalId();
        }
        
        $this->normalizeIsActive($data);
        
        // Set add_type to 'static' for manual creation if not provided
        if (!isset($data['add_type'])) {
            $data['add_type'] = 'static';
        }
        
        // Auto-set country_id from merchant if merchant_id is provided
        // Always set from merchant, even if country_id is explicitly null or empty
        if (!empty($data['merchant_id'])) {
            $merchant = \App\Models\Merchant::query()->where('id', '=', $data['merchant_id'], 'and')->first();
            if ($merchant && $merchant->country_id) {
                $data['country_id'] = $merchant->country_id;
            }
        }
        // dd($data);
        return $this->terminalRepository->create($data);
    }

    /**
     * Store a newly created terminal
     */
    public function store(Request $request, $merchantId = null)
    {
        $data = $request->validated();
        
        // Generate unique terminal ID if not provided
        if (empty($data['terminal_id'])) {
            $data['terminal_id'] = Terminal::generateTerminalId();
        }
        
        $this->normalizeIsActive($data);
        
        // Set add_type to 'static' for manual creation (admin/merchant dashboard)
        if (!isset($data['add_type'])) {
            $data['add_type'] = 'static';
        }
        
        // Set merchant_id if provided in request or passed as parameter
        if (isset($data['merchant_id'])) {
            $data['merchant_id'] = $data['merchant_id'];
        } elseif ($merchantId) {
            $data['merchant_id'] = $merchantId;
        }
        
        // Auto-set country_id from merchant if merchant_id is provided
        // Always set from merchant, even if country_id is explicitly null or empty
        if (!empty($data['merchant_id'])) {
            $merchant = \App\Models\Merchant::query()->where('id', '=', $data['merchant_id'], 'and')->first();
            if ($merchant && $merchant->country_id) {
                $data['country_id'] = $merchant->country_id;
            }
        }
        
        return $this->terminalRepository->create($data);
    }

    /**
     * Display the specified terminal
     */
    public function show(Terminal $terminal)
    {
        if (request()->has('status')) {
            return $this->changeStatus($terminal);
        }

        // Log the terminal view action
        // $terminal->logs()->create([
        //     'action' => 'viewed',
        //     'metadata' => [
        //         'type' => 'view',
        //         'event' => 'Admin viewed terminal profile',
        //         'message' => 'Terminal profile viewed by Admin',
        //         'viewed_at' => now(),
        //         'viewed_by' => auth('admin')->user()?->name ?? 'System'
        //     ],
        //     'user_id' => auth('admin')->user()?->id,
        //     'user_type' => auth('admin')->user() ? get_class(auth('admin')->user()) : null
        // ]);

        return view('terminals.show', compact('terminal'));
    }

    /**
     * Show the form for editing the specified terminal
     */
    public function edit(Terminal $terminal)
    {
        return view('terminals.edit', compact('terminal'));
    }

    /**
     * Update the specified terminal
     */
    public function update(Request $request, Terminal $terminal)
    {
        $data = $request->validated();
        
        $this->normalizeIsActive($data);
        
        // Handle merchant_id update
        if (!empty($data['merchant_id'])) {
            // Auto-update country_id from merchant if merchant_id changed
            $merchant = \App\Models\Merchant::query()->where('id', '=', $data['merchant_id'], 'and')->first();
            if ($merchant && $merchant->country_id) {
                $data['country_id'] = $merchant->country_id;
            }
        }
        
        return $this->terminalRepository->update($terminal, $data);
    }

    /**
     * Remove the specified terminal
     */
    public function destroy(Terminal $terminal)
    {
        return $this->terminalRepository->delete($terminal);
    }

    /**
     * Get DataTable data for terminals
     */
    public function data($merchantId = null)
    {
        $query = Terminal::with(['merchant'])->withCountry();

        // Apply merchant filter if provided
        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }

        // Apply search filter
        if (request()->has('search') && !empty(request()->search)) {
            $searchValue = request()->search;
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhere('terminal_id', 'like', "%{$searchValue}%")
                  ->orWhere('device_id', 'like', "%{$searchValue}%")
                  ->orWhereHas('merchant', function($q) use ($searchValue) {
                      $q->where('name', 'like', "%{$searchValue}%");
                  });
            });
        }

        // Apply status filter (is_active)
        if (request()->has('status') && !empty(request()->status)) {
            $status = request()->status === 'active' ? 1 : 0;
            $query->where('is_active', $status);
        }

        // Apply terminal status filter (online/offline/testing)
        if (request()->has('terminal_status') && !empty(request()->terminal_status)) {
            $query->where('terminal_status', request()->terminal_status);
        }

        // Apply merchant filter
        if (request()->has('merchant_id') && !empty(request()->merchant_id)) {
            $query->where('merchant_id', request()->merchant_id);
        }

        // Apply date filters
        if (request()->has('from_date') && !empty(request()->from_date)) {
            $query->whereDate('created_at', '>=', request()->from_date);
        }

        if (request()->has('to_date') && !empty(request()->to_date)) {
            $query->whereDate('created_at', '<=', request()->to_date);
        }

        return DataTables::of($query)
            ->addColumn('record_select', function ($terminal) {
                return view('terminals.data_table.checkbox', ['id' => $terminal->id])->render();
            })
            ->addColumn('merchant_name', function ($terminal) {
                return $terminal->merchant ? $terminal->merchant->name : 'N/A';
            })
            ->addColumn('terminal_info', function ($terminal) {
                return $terminal->name . ' - ' . $terminal->terminal_id . ' - ' . $terminal->model;
            })
            ->editColumn('termainl_status', function ($terminal) {
                return $terminal->getTerminalStatus();
            })
            ->addColumn('brand', function ($terminal) {
                return $terminal->brand ?? 'N/A';
            })
            ->addColumn('sdk_id', function ($terminal) {
                return $terminal->sdk_id ?? 'N/A';
            })
            ->addColumn('sdk_version', function ($terminal) {
                return $terminal->sdk_version ?? 'N/A';
            })
            ->addColumn('android_os', function ($terminal) {
                return $terminal->android_os ?? 'N/A';
            })
            ->addColumn('add_type', function ($terminal) {
                $badgeClass = $terminal->add_type === 'auto' ? 'badge-light-success' : 'badge-light-warning';
                $text = $terminal->add_type === 'auto' ? __('translation.auto') : __('translation.static');
                return '<span class="badge ' . $badgeClass . '">' . $text . '</span>';
            })
            ->addColumn('created_at', function ($terminal) {
                return $terminal->created_at->format('Y-m-d H:i:s');
            })
            ->addColumn('country', function ($terminal) {
                return $terminal->country ? $terminal->country->name : 'N/A';
            })
            ->addColumn('status', function ($terminal) {
                return view('terminals.data_table.status', ['is_active' => $terminal->is_active])->render();
            })
            ->addColumn('actions', function ($terminal) {
                return view('terminals.data_table.actions', ['id' => $terminal->id])->render();
            })
            ->addColumn('merchant_actions', function ($terminal) {
                return view('merchant.terminals.data_table.actions', ['id' => $terminal->id])->render();
            })
            ->rawColumns(['record_select', 'termainl_status', 'status', 'actions', 'merchant_actions', 'add_type'])
            ->make(true);
    }

    /**
     * Change terminal status
     */
    public function changeStatus(Terminal $terminal)
    {
        $terminal->update(['is_active' => !$terminal->is_active]);
        return redirect()->back()->with('success', __('translation.terminal_status_changed'));
    }

    /**
     * Bulk delete terminals
     */
    public function bulkDelete(array $ids, $merchantId = null)
    {
        $terminals = Terminal::whereIn('id', $ids)->get();
        
        foreach ($terminals as $terminal) {
            $terminal->delete();
        }
        
        return true;
    }

    /**
     * Get all terminals for API
     */
    public function getAllTerminals(Request $request)
    {
        $query = Terminal::with([
            'country:id,name'
        ]);

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchValue = $request->search;
            $query->where(function ($q) use ($searchValue) {
                $q->where('terminal_id', 'like', "%{$searchValue}%")
                  ->orWhere('name', 'like', "%{$searchValue}%")
                  ->orWhere('brand', 'like', "%{$searchValue}%")
                  ->orWhere('model', 'like', "%{$searchValue}%")
                  ->orWhere('serial_no', 'like', "%{$searchValue}%");
            });
        }

        // Status filter (is_active)
        if ($request->has('status') && !empty($request->status)) {
            $status = $request->status === 'active' ? 1 : 0;
            $query->where('is_active', $status);
        }

        // Terminal status filter
        if ($request->has('terminal_status') && !empty($request->terminal_status)) {
            $query->where('terminal_status', $request->terminal_status);
        }

        // Merchant filter
        if ($request->has('merchant_id') && !empty($request->merchant_id)) {
            $query->where('merchant_id', $request->merchant_id);
        }

        // Branch filter
        if ($request->has('branch_id') && !empty($request->branch_id)) {
            $query->where('branch_id', $request->branch_id);
        }

        // Country filter
        if ($request->has('country_id') && !empty($request->country_id)) {
            $query->where('country_id', $request->country_id);
        }

        // Date filters
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get terminals for select dropdown
     */
    public function getTerminalsForSelect(Request $request)
    {
        $query = Terminal::select('id', 'terminal_id', 'brand', 'model');

        // Search functionality for select2
        if ($request->has('search') && !empty($request->search)) {
            $searchValue = $request->search;
            $query->where(function ($q) use ($searchValue) {
                $q->where('terminal_id', 'like', "%{$searchValue}%")
                  ->orWhere('brand', 'like', "%{$searchValue}%")
                  ->orWhere('model', 'like', "%{$searchValue}%");
            });
        }

        // Only active terminals
        $query->where('is_active', 1);

        // Filter by merchant if provided
        if ($request->has('merchant_id')) {
            $query->where('merchant_id', $request->merchant_id);
        }



        return $query->get();
    }

    /**
     * Get terminals by merchant
     */
    public function getByMerchant($merchantId)
    {
        return Terminal::where('merchant_id', $merchantId)->get();
    }

    /**
     * Get active terminals by merchant
     */
    public function getActiveByMerchant($merchantId)
    {
        return Terminal::where('merchant_id', $merchantId)->where('is_active', 1)->get();
    }

    

    /**
     * Get DataTable data for terminals
     */
    public function getDataTableData(Request $request)
    {
        return $this->data($request->get('merchant_id'));
    }

    /**
     * Delete terminal
     */
    public function delete(Terminal $terminal)
    {
        return $this->terminalRepository->delete($terminal);
    }

    /**
     * Get terminals by merchant ID
     */
    public function getByMerchantId($merchantId)
    {
        return $this->terminalRepository->getByMerchantId($merchantId);
    }

    /**
     * Get active terminals by merchant ID
     */
    public function getActiveByMerchantId($merchantId)
    {
        return $this->terminalRepository->getActiveByMerchantId($merchantId);
    }

    /**
     * Get terminals for select dropdown with merchant filtering
     */
    public function getSelectData(Request $request)
    {
        return $this->terminalRepository->getSelectData($request);
    }

    /**
     * Get terminals by branch ID
     */
    public function getByBranchId($branchId)
    {
        return $this->terminalRepository->getByBranchId($branchId);
    }

    /**
     * Get active terminals by branch ID
     */
    public function getActiveByBranchId($branchId)
    {
        return $this->terminalRepository->getActiveByBranchId($branchId);
    }

    /**
     * Preview terminals import from file
     */
    public function importPreview($file, $merchantId = null)
    {
        $rows = [];
        
        try {
            $filePath = $file->getRealPath();
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();
            $highestColumn = $sheet->getHighestDataColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            // Read headers
            $headers = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $value = trim((string) $sheet->getCell($colLetter . '1')->getValue());
                $headers[$col] = \Illuminate\Support\Str::of($value)->lower()->trim()->toString();
            }

            // Map header aliases to internal keys
            $headerAliasMap = [
                'name' => ['name', 'terminal_name'],
                'terminal_id' => ['terminal_id', 'terminal id', 'terminalid', 'tid'],
                'brand' => ['brand'],
                'model' => ['model'],
                'manufacturer' => ['manufacturer'],
                'serial_no' => ['serial_no', 'serial no', 'serial', 'serial number', 'serialno'],
                'sdk_id' => ['sdk_id', 'sdk id', 'sdkid'],
                'sdk_version' => ['sdk_version', 'sdk version', 'sdkversion'],
                'android_os' => ['android_os', 'android os', 'android', 'os'],
                'add_type' => ['add_type', 'add type', 'addtype', 'type'],
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

            // Read data rows
            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = [];
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    if (!isset($columnKeyByIndex[$col])) continue;
                    $key = $columnKeyByIndex[$col];
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $value = $sheet->getCell($colLetter . $row)->getFormattedValue();
                    $rowData[$key] = is_string($value) ? trim($value) : $value;
                }

                // Skip empty rows
                if (!array_filter($rowData)) continue;

                $flags = [];
                $error = null;
                
                // Validate required fields
                if (empty($rowData['name'])) {
                    $error = 'Name is required';
                }
                if (empty($rowData['terminal_id'])) {
                    if (!$error) $error = 'Terminal ID is required';
                }
                
                // Check if terminal_id already exists
                if (!empty($rowData['terminal_id'])) {
                    $exists = Terminal::where('terminal_id', $rowData['terminal_id'])
                        ->where('merchant_id', $merchantId)
                        ->exists();
                    if ($exists) {
                        $error = 'Terminal ID already exists';
                    }
                }
                
                $flags['error'] = $error;
                
                $rows[] = [
                    'original' => $rowData,
                    'flags' => $flags
                ];
            }
            
            return [
                'success' => true,
                'rows' => $rows
            ];
            
        } catch (\Exception $e) {
            throw new \Exception("Preview failed: " . $e->getMessage());
        }
    }

    /**
     * Import terminals from file
     */
    public function import($file, $merchantId = null)
    {
        $importedCount = 0;
        $errors = [];
        
        try {
            $extension = $file->getClientOriginalExtension();
            
            if ($extension === 'csv') {
                $data = $this->readCsvFile($file);
            } else {
                $data = $this->readExcelFile($file);
            }
            
            foreach ($data as $index => $row) {
                try {
                    $this->importTerminal($row, $merchantId);
                    $importedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }
            }
            
            return [
                'success' => true,
                'message' => "Successfully imported {$importedCount} terminals" . (count($errors) > 0 ? " with " . count($errors) . " errors" : ""),
                'imported_count' => $importedCount,
                'errors' => $errors
            ];
            
        } catch (\Exception $e) {
            throw new \Exception("Import failed: " . $e->getMessage());
        }
    }

    /**
     * Export template for terminals import
     */
    public function exportTemplate($merchantId = null)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Terminals Template');

        // Headers (removed Merchant ID as it's automatically set from logged-in user)
        $headers = ['Name', 'Terminal ID', 'Brand', 'Model', 'Manufacturer', 'Serial No', 'SDK ID', 'SDK Version', 'Android OS', 'Add Type', 'Is Active'];
        $sheet->fromArray([$headers], null, 'A1');
        
        // Style the header row
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
        ];
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

        // Example row
        $sheet->fromArray([
            ['Sample Terminal', 'TERM12345678', 'Verifone', 'Verifone VX520', 'Verifone', 'SN123456789', 'SDK001', '1.0.0', 'Android 11', 'static', '1']
        ], null, 'A2');

        // Auto-size columns
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Stream as XLSX
        $filename = 'terminals_template.xlsx';
        $tempPath = storage_path('app/' . $filename);
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Export terminals data
     */
    public function export(Request $request)
    {
        try {
            $search = $request->get('search', '');
            $status = $request->get('status', '');
            
            // Get terminals query - removed with('merchant') since merchant() returns null
            $terminals = Terminal::query();
            
            // Apply search filter
            if (!empty($search)) {
                $terminals->where(function($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                          ->orWhere('terminal_id', 'like', '%' . $search . '%')
                          ->orWhere('model', 'like', '%' . $search . '%')
                          ->orWhere('manufacturer', 'like', '%' . $search . '%')
                          ->orWhere('brand', 'like', '%' . $search . '%')
                          ->orWhere('serial_no', 'like', '%' . $search . '%');
                    // Removed orWhereHas('merchant') since merchant relationship returns null
                });
            }
            
            // Apply status filter
            if (!empty($status)) {
                $terminals->where('is_active', $status === 'active');
            }
            
            // Apply merchant filter
            if ($request->has('merchant_id') && !empty($request->merchant_id)) {
                $terminals->where('merchant_id', $request->merchant_id);
            }
            
            // Apply branch filter
            if ($request->has('branch_id') && !empty($request->branch_id)) {
                $terminals->where('branch_id', $request->branch_id);
            }
            
            // Apply country filter
            if ($request->has('country_id') && !empty($request->country_id)) {
                $terminals->where('country_id', $request->country_id);
            }
            
            // Apply terminal status filter
            if ($request->has('terminal_status') && !empty($request->terminal_status)) {
                $terminals->where('terminal_status', $request->terminal_status);
            }
            
            // Apply date filters
            if ($request->has('from_date') && !empty($request->from_date)) {
                $terminals->whereDate('created_at', '>=', $request->from_date);
            }
            
            if ($request->has('to_date') && !empty($request->to_date)) {
                $terminals->whereDate('created_at', '<=', $request->to_date);
            }
            
            $terminals = $terminals->orderBy('created_at', 'desc')->get();
            
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
                    'Merchant',
                    'Brand',
                    'Model',
                    'Manufacturer',
                    'Serial No',
                    'SDK ID',
                    'SDK Version',
                    'Android OS',
                    'Add Type',
                    'Status',
                    'Terminal Status',
                    'Created At',
                    'Updated At'
                ]);
                
                // Add data
                foreach ($terminals as $terminal) {
                    fputcsv($file, [
                        $terminal->id,
                        $terminal->name,
                        $terminal->terminal_id,
                        $terminal->merchant_id ?? 'N/A', // Merchant relationship returns null, so use merchant_id directly
                        $terminal->brand ?? 'N/A',
                        $terminal->model ?? 'N/A',
                        $terminal->manufacturer ?? 'N/A',
                        $terminal->serial_no ?? 'N/A',
                        $terminal->sdk_id ?? 'N/A',
                        $terminal->sdk_version ?? 'N/A',
                        $terminal->android_os ?? 'N/A',
                        $terminal->add_type ?? 'N/A',
                        $terminal->is_active ? 'Active' : 'Inactive',
                        $terminal->terminal_status ?? 'N/A',
                        $terminal->created_at ? $terminal->created_at->format('Y-m-d H:i:s') : 'N/A',
                        $terminal->updated_at ? $terminal->updated_at->format('Y-m-d H:i:s') : 'N/A'
                    ]);
                }
                
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
            
        } catch (\Exception $e) {
            throw new \Exception('Failed to export data: ' . $e->getMessage());
        }
    }

    /**
     * Read CSV file
     */
    private function readCsvFile($file)
    {
        $data = [];
        $handle = fopen($file->getPathname(), 'r');
        
        // Skip header row
        fgetcsv($handle);
        
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 11) {
                $data[] = [
                    'name' => trim($row[0]),
                    'terminal_id' => trim($row[1]),
                    'brand' => trim($row[2]),
                    'model' => trim($row[3]),
                    'manufacturer' => trim($row[4]),
                    'serial_no' => trim($row[5]),
                    'sdk_id' => trim($row[6]),
                    'sdk_version' => trim($row[7]),
                    'android_os' => trim($row[8]),
                    'add_type' => trim($row[9]),
                    'is_active' => trim($row[10])
                ];
            }
        }
        
        fclose($handle);
        return $data;
    }

    /**
     * Read Excel file using simple approach
     */
    private function readExcelFile($file)
    {
        $data = [];
        
        try {
            // For Excel files, we'll use a simple CSV approach
            // This works for most Excel files that can be opened as CSV
            $handle = fopen($file->getPathname(), 'r');
            
            // Skip header row
            fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= 11) {
                    $data[] = [
                        'name' => trim($row[0] ?? ''),
                        'terminal_id' => trim($row[1] ?? ''),
                        'brand' => trim($row[2] ?? ''),
                        'model' => trim($row[3] ?? ''),
                        'manufacturer' => trim($row[4] ?? ''),
                        'serial_no' => trim($row[5] ?? ''),
                        'sdk_id' => trim($row[6] ?? ''),
                        'sdk_version' => trim($row[7] ?? ''),
                        'android_os' => trim($row[8] ?? ''),
                        'add_type' => trim($row[9] ?? 'static'),
                        'is_active' => trim($row[10] ?? '1')
                    ];
                }
            }
            
            fclose($handle);
        } catch (\Exception $e) {
            throw new \Exception("Error reading Excel file: " . $e->getMessage());
        }
        
        return $data;
    }

    /**
     * Import single terminal
     */
    private function importTerminal($data, $merchantId = null)
    {
        // Validate required fields
        if (empty($data['name'])) {
            throw new \Exception("Terminal name is required");
        }

        // Check if terminal already exists by terminal_id
        if (!empty($data['terminal_id']) && Terminal::where('terminal_id', $data['terminal_id'])->exists()) {
            throw new \Exception("Terminal with ID {$data['terminal_id']} already exists");
        }

        // Prepare terminal data
        $terminalData = [
            'name' => $data['name'],
            'terminal_id' => $data['terminal_id'] ?? Terminal::generateTerminalId(),
            'merchant_id' => $merchantId, // Always use the logged-in merchant's ID
            'brand' => $data['brand'] ?? null,
            'model' => $data['model'] ?? null,
            'manufacturer' => $data['manufacturer'] ?? null,
            'serial_no' => $data['serial_no'] ?? null,
            'sdk_id' => $data['sdk_id'] ?? null,
            'sdk_version' => $data['sdk_version'] ?? null,
            'android_os' => $data['android_os'] ?? null,
            'add_type' => $data['add_type'] ?? 'static',
            'is_active' => filter_var($data['is_active'] ?? '1', FILTER_VALIDATE_BOOLEAN),
        ];

        // Create terminal
        $this->terminalRepository->create($terminalData);
    }

    /**
     * Register or retrieve terminal by device information
     * 
     * @param array $deviceData
     * @return array
     */
    public function registerOrRetrieveTerminal(array $deviceData)
    {
        // dd($deviceData);
        try {
            // Validate required fields
            if (empty($deviceData['device_id'])) {
                throw new \Exception("Device ID is required");
            }

            // Check if terminal already exists by device_id
            $existingTerminal = Terminal::where('device_id', $deviceData['device_id'])->first();

            if ($existingTerminal) {
                // Log the terminal retrieval
                $existingTerminal->logs()->create([
                    'action' => 'retrieved',
                    'metadata' => [
                        'type' => 'retrieval',
                        'event' => 'Terminal retrieved',
                        'message' => 'Existing terminal retrieved via device registration',
                        'retrieved_at' => now(),
                        'device_id' => $deviceData['device_id'],
                        'terminal_id' => $existingTerminal->terminal_id,
                        'retrieval_method' => 'auto'
                    ],
                    'user_id' => null, // Auto-retrieval, no specific user
                    'user_type' => null
                ]);

                // Terminal exists, return existing terminal_id
                return [
                    'success' => true,
                    'message' => 'Terminal already registered',
                    'terminal_id' => $existingTerminal->terminal_id,
                    'is_new' => false,
                    'terminal' => $existingTerminal
                ];
            }

            // Generate unique terminal ID
            $terminalId = Terminal::generateTerminalId();

            // Prepare terminal data
            $terminalData = [
                'name' => $deviceData['manufacturer'] . ' ' . $deviceData['model'] . ' (' . $deviceData['device_id'] . ')',
                'terminal_id' => $terminalId,
                'brand' => $deviceData['brand'] ?? null,
                'model' => $deviceData['model'] ?? null,
                'manufacturer' => $deviceData['manufacturer'] ?? null,
                'serial_no' => $deviceData['serial_no'] ?? null,
                'sdk_id' => $deviceData['sdk_id'] ?? null,
                'sdk_version' => $deviceData['sdk_version'] ?? null,
                'android_os' => $deviceData['android_os'] ?? null,
                'device_id' => $deviceData['device_id'],
                'add_type' => 'auto', // Mark as auto-registered
                'is_active' => true,
                'termainl_status' => 'testing'
            ];

            // Create new terminal
            $newTerminal = $this->createTerminal($terminalData);

            // Log the terminal registration
            $newTerminal->logs()->create([
                'action' => 'registered',
                'metadata' => [
                    'type' => 'registration',
                    'event' => 'Terminal auto-registered',
                    'message' => 'Terminal registered automatically via device registration',
                    'registered_at' => now(),
                    'device_id' => $deviceData['device_id'],
                    'terminal_id' => $newTerminal->terminal_id,
                    'registration_method' => 'auto'
                ],
                'user_id' => null, // Auto-registration, no specific user
                'user_type' => null
            ]);

            return [
                'success' => true,
                'message' => 'Terminal registered successfully',
                'terminal_id' => $newTerminal->terminal_id,
                'is_new' => true,
                'terminal' => $newTerminal
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to register terminal: ' . $e->getMessage(),
                'terminal_id' => null,
                'is_new' => false,
                'terminal' => null
            ];
        }
    }

    /**
     * Normalize is_active input to boolean.
     */
    private function normalizeIsActive(array &$data): void
    {
        if (!array_key_exists('is_active', $data)) {
            return;
        }

        $value = $data['is_active'];

        if (is_bool($value)) {
            return;
        }

        if (is_int($value)) {
            $data['is_active'] = $value === 1;
            return;
        }

        if (is_string($value)) {
            $normalizedValue = strtolower(trim($value));
            $truthyValues = ['active', '1', 'true'];
            $falsyValues = ['inactive', '0', 'false'];

            if (in_array($normalizedValue, $truthyValues, true)) {
                $data['is_active'] = true;
                return;
            }

            if (in_array($normalizedValue, $falsyValues, true)) {
                $data['is_active'] = false;
                return;
            }
        }

        $data['is_active'] = (bool) $value;
    }
} 

