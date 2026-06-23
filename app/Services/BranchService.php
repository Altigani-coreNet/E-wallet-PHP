<?php

namespace App\Services;

use App\Events\BranchStatusChanged;
use App\Models\Branch;
use App\Models\Log;
use App\Models\Merchant;
use App\Repositories\BranchRepository;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class BranchService
{
    protected $branchRepository;

    public function __construct(BranchRepository $branchRepository)
    {
        $this->branchRepository = $branchRepository;
    }

    /**
     * Display a listing of branches
     */
    public function index()
    {
        return view('branches.index');
    }

    /**
     * Show the form for creating a new branch
     */
    public function create()
    {
        return view('branches.create');
    }

    /**
     * Store a newly created branch
     */
    public function store(Request $request, $merchantId = null)
    {
        $data = $request->validated();
        
        // Set default values for new branches
        $data['status'] = 'pending';
        $data['is_active'] = false;
        
        // Set merchant_id if provided
        // dd($merchantId);
        if ($merchantId) {
            $data['merchant_id'] = $merchantId;
            // dd(Merchant::select('country_id','id')->find($merchantId));
            $data['country_id'] =Merchant::select('country_id','id')->find($merchantId)->country_id;
        }
        
        $branch = $this->branchRepository->create($data);
        
        // Log: Merchant added a branch which is pending
        try {
            Log::create([
                'loggable_type' => Merchant::class,
                'loggable_id' => $branch->merchant_id,
                'user_id' => Auth::user()->id ?? null,
                'user_type' => Auth::user() ? get_class(Auth::user()) : null,
                'action' => 'branch_created',
                'description' => 'New branch created and pending approval',
                'metadata' => json_encode([
                    'created_at' => now()->toDateTimeString(),
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'status' => $branch->status,
                ]),
            ]);
        } catch (\Throwable $e) {
            // Silently ignore logging errors to not block branch creation
        }
        
        // Dispatch event for new branch creation (status change from null to pending)
        event(new BranchStatusChanged($branch, null, 'pending'));
        
        return $branch;
    }

    /**
     * Display the specified branch
     */
    public function show(Branch $branch)
    {
        if (request()->has('status')) {
            return $this->changeStatus($branch);
        }
        return view('branches.show', compact('branch'));
    }

    /**
     * Show the form for editing the specified branch
     */
    public function edit(Branch $branch)
    {
        return view('branches.edit', compact('branch'));
    }

    /**
     * Update the specified branch
     */
    public function update(Request $request, Branch $branch)
    {
        $data = $request->validated();
        
        // Convert is_active from string to boolean
        if (isset($data['is_active'])) {
            $data['is_active'] = $data['is_active'] === 'active';
        }
        
        return $this->branchRepository->update($branch, $data);
    }

    /**
     * Remove the specified branch
     */
    public function destroy(Branch $branch)
    {
        return $this->branchRepository->delete($branch);
    }

    /**
     * Get DataTable data for branches
     */
    public function data($merchantId = null)
    {
        $query = Branch::with('merchant');
        
        // Filter by merchant if provided
        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }else{
            $query->withCountry();
        }


        // Handle search filter
        if (request()->has('search') && !empty(request()->get('search') && is_string(request()->get('search')))) {
            $search = request()->get('search');
            $query->where(function ($q) use ($search, $merchantId) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhereHas('merchant', function ($merchantQuery) use ($search) {
                      $merchantQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Handle status filter for admin users
        if (request()->has('status') && !empty(request()->get('status')) && !$merchantId) {
            $status = request()->get('status');
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Handle country filter
        if (request()->has('country_id') && !empty(request()->get('country_id')) && !$merchantId) {
            $query->where('country_id', request()->get('country_id'));
        }

        // Handle date range filters
        if (request()->has('date_from') && !empty(request()->get('date_from'))) {
            $query->whereDate('created_at', '>=', request()->get('date_from'));
        }

        if (request()->has('date_to') && !empty(request()->get('date_to'))) {
            $query->whereDate('created_at', '<=', request()->get('date_to'));
        }

        return DataTables::of($query)
            ->addColumn('record_select', function ($branch) use ($merchantId) {
                $viewPath = $merchantId ? 'merchant.branches.data_table.record_select' : 'branches.data_table.checkbox';
                return view($viewPath, ['id' => $branch->id])->render();
            })
            ->addColumn('merchant_name', function ($branch) {
                return $branch->merchant->name ?? 'N/A';
            })
            ->addColumn('created_at', function ($branch) {
                return $branch->created_at->diffForHumans();
            })
            ->addColumn('country', function ($branch) {
                return $branch->country ? $branch->country->name : 'N/A';
            })
            ->addColumn('status', function ($branch) use ($merchantId) {
                $viewPath = $merchantId ? 'merchant.branches.data_table.status' : 'branches.data_table.status';
                return view($viewPath, ['is_active' => $branch->is_active, 'branch' => $branch])->render();
            })
            ->addColumn('actions', function ($branch) use ($merchantId) {
                // dd($merchantId);
                // Use merchant actions view if merchantId is provided, otherwise use admin actions
            $viewPath = $merchantId ? 'merchant.branches.data_table.merchant_actions' : 'branches.data_table.actions';
                return view($viewPath, ['id' => $branch->id, 'status' => $branch->status])->render();
            })
            ->rawColumns(['record_select', 'status', 'actions'])
            ->make(true);
    }

    /**
     * Change branch status
     */
    public function changeStatus(Branch $branch)
    {
        $branch->update(['is_active' => !$branch->is_active]);
        return redirect()->back()->with('success', 'Branch status updated successfully');
    }

    /**
     * Bulk delete branches
     */
    public function bulkDelete(array $ids, $merchantId = null)
    {
        $query = Branch::whereIn('id', $ids);
        
        // Filter by merchant if provided
        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }
        
        $branches = $query->get();
        
        foreach ($branches as $branch) {
            $branch->delete();
        }
        
        return true;
    }

    /**
     * Get branches by merchant ID
     */
    public function getByMerchantId($merchantId)
    {
        return $this->branchRepository->getByMerchantId($merchantId);
    }

    /**
     * Get active branches by merchant ID
     */
    public function getActiveByMerchantId($merchantId)
    {
        return $this->branchRepository->getActiveByMerchantId($merchantId)->where('status', 'approved');
    }

    /**
     * Get all branches for API
     */
    public function getAllBranches(Request $request)
    {
        $query = Branch::with('merchant');

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchValue = $request->search;
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhere('address', 'like', "%{$searchValue}%")
                  ->orWhere('phone', 'like', "%{$searchValue}%");
            });
        }

        // Status filter
        if ($request->has('status')) {
            $status = $request->status === 'active' ? 1 : 0;
            $query->where('is_active', $status);
        }

        // Merchant filter
        if ($request->has('merchant_id')) {
            $query->where('merchant_id', $request->merchant_id);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        return $query->paginate($perPage);
    }

    /**
     * Get branches for select dropdown
     */
    public function getBranchesForSelect(Request $request)
    {
        $query = Branch::select('id', 'name', 'address');

        // Search functionality for select2
        if ($request->has('search') && !empty($request->search)) {
            $searchValue = $request->search;
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhere('address', 'like', "%{$searchValue}%");
            });
        }

        // Only active branches
        $query->where('is_active', 1);

        // Filter by merchant if provided
        if ($request->has('merchant_id')) {
            $query->where('merchant_id', $request->merchant_id);
        }

        return $query->get();
    }

    /**
     * Get branches by merchant
     */
    public function getByMerchant($merchantId)
    {
        return Branch::where('merchant_id', $merchantId)->get();
    }

    /**
     * Get active branches by merchant
     */
    public function getActiveByMerchant($merchantId)
    {
        return Branch::where('merchant_id', $merchantId)->where('is_active', 1)->get();
    }

    /**
     * Get DataTable data for branches
     */
    public function getDataTableData(Request $request)
    {
        return $this->data($request->get('merchant_id'));
    }

    /**
     * Delete branch
     */
    public function delete(Branch $branch)
    {
        return $this->branchRepository->delete($branch);
    }

    /**
     * Preview branches import data
     */
    public function importPreview($file, $merchantId = null)
    {
        $data = [];
        $errors = [];
        
        try {
            $extension = $file->getClientOriginalExtension();
            
            if ($extension === 'csv') {
                $rawData = $this->readCsvFile($file);
            } else {
                $rawData = $this->readExcelFile($file);
            }
            
            foreach ($rawData as $index => $row) {
                // Validate required fields
                $rowErrors = [];
                
                if (empty($row['name'])) {
                    $rowErrors[] = "Missing name";
                }
                
                if (empty($row['address'])) {
                    $rowErrors[] = "Missing address";
                }
                
                if (!empty($rowErrors)) {
                    $errors[] = "Row " . ($index + 1) . ": " . implode(", ", $rowErrors);
                }
                
                // Add to preview data
                $data[] = [
                    'name' => $row['name'] ?? '',
                    'address' => $row['address'] ?? '',
                    'status' => $row['status'] ?? 'pending',
                    'is_active' => isset($row['is_active']) && ($row['is_active'] == '1' || strtolower($row['is_active']) === 'active')
                ];
            }
            
            return [
                'data' => $data,
                'errors' => $errors
            ];
            
        } catch (\Exception $e) {
            throw new \Exception('Failed to preview file: ' . $e->getMessage());
        }
    }

    /**
     * Import branches from file
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
                    // Validate required fields
                    if (empty($row['name']) || empty($row['address'])) {
                        $errors[] = "Row " . ($index + 1) . ": Missing required fields (Name, Address)";
                        continue;
                    }
                    
                    // Use provided merchant_id or from file
                    $branchMerchantId = $merchantId ?: $row['merchant_id'] ?? null;
                    
                    if (!$branchMerchantId) {
                        $errors[] = "Row " . ($index + 1) . ": Merchant ID is required";
                        continue;
                    }
                    
                    // Get merchant to validate and get country_id
                    $merchant = \App\Models\Merchant::find($branchMerchantId);
                    if (!$merchant) {
                        $errors[] = "Row " . ($index + 1) . ": Merchant ID {$branchMerchantId} not found";
                        continue;
                    }
                    
                    // Create branch
                    $branch = Branch::create([
                        'name' => $row['name'],
                        'address' => $row['address'],
                        'merchant_id' => $branchMerchantId,
                        'country_id' => $merchant->country_id,
                        'status' => $row['status'] ?? 'pending', // Use status from CSV or default to pending
                        'is_active' => isset($row['is_active']) && ($row['is_active'] == '1' || strtolower($row['is_active']) === 'active'),
                    ]);
                    
                    // Dispatch event for new branch creation
                    event(new BranchStatusChanged($branch, null, $branch->status));
                    
                    $importedCount++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }
            
            return [
                'imported_count' => $importedCount,
                'errors' => $errors,
                'message' => "Successfully imported {$importedCount} branches" . (count($errors) > 0 ? " with " . count($errors) . " errors" : "")
            ];
            
        } catch (\Exception $e) {
            throw new \Exception('Failed to import file: ' . $e->getMessage());
        }
    }

    /**
     * Export template for branches import
     */
    public function exportTemplate($merchantId = null)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="branches_import_template.csv"',
        ];

        $callback = function() use ($merchantId) {
            $file = fopen('php://output', 'w');
            
            // Add headers (merchant_id removed - now selected via dropdown)
            fputcsv($file, [
                'name',
                'address', 
                'status',
                'is_active'
            ]);
            
            // Add sample data
            fputcsv($file, [
                'Main Branch',
                '123 Main Street, City, Country',
                'pending',
                '1'
            ]);
            
            fputcsv($file, [
                'Downtown Branch',
                '456 Second Avenue, Downtown, Country',
                'pending',
                '1'
            ]);
            
            fputcsv($file, [
                'West Branch',
                '789 West Boulevard, West District, Country',
                'approved',
                '0'
            ]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export filtered branches
     */
    public function export($filters = [], $merchantId = null)
    {
        $query = Branch::with('merchant');
        
        // Filter by merchant if provided
        if ($merchantId) {
            $query->where('merchant_id', $merchantId)
                  ->where('status', '!=', 'pending'); // Don't export pending branches for merchants
        }
        
        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search, $merchantId) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
                
                // Only search in merchant data if not filtering by specific merchant
                if (!$merchantId) {
                    $q->orWhereHas('merchant', function ($merchantQuery) use ($search) {
                        $merchantQuery->where('name', 'like', "%{$search}%")
                                     ->orWhere('email', 'like', "%{$search}%");
                    });
                }
            });
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply date range filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $branches = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . ($merchantId ? 'my_branches' : 'branches') . '_export_' . date('Y-m-d_H-i-s') . '.csv"',
        ];

        $callback = function() use ($branches, $merchantId) {
            $file = fopen('php://output', 'w');
            
            // Add headers based on whether it's merchant or admin export
            if ($merchantId) {
                fputcsv($file, [
                    'ID',
                    'Name',
                    'Address',
                    'Status',
                    'Is Active',
                    'Created At',
                    'Updated At'
                ]);
            } else {
                fputcsv($file, [
                    'ID',
                    'Name',
                    'Address',
                    'Merchant Name',
                    'Merchant Email',
                    'Status',
                    'Is Active',
                    'Created At',
                    'Updated At'
                ]);
            }
            
            // Add data rows
            foreach ($branches as $branch) {
                if ($merchantId) {
                    fputcsv($file, [
                        $branch->id,
                        $branch->name,
                        $branch->address,
                        $branch->status,
                        $branch->is_active ? 'Yes' : 'No',
                        $branch->created_at->format('Y-m-d H:i:s'),
                        $branch->updated_at->format('Y-m-d H:i:s')
                    ]);
                } else {
                    fputcsv($file, [
                        $branch->id,
                        $branch->name,
                        $branch->address,
                        $branch->merchant->name ?? 'N/A',
                        $branch->merchant->email ?? 'N/A',
                        $branch->status,
                        $branch->is_active ? 'Yes' : 'No',
                        $branch->created_at->format('Y-m-d H:i:s'),
                        $branch->updated_at->format('Y-m-d H:i:s')
                    ]);
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Read CSV file
     */
    private function readCsvFile($file)
    {
        $data = [];
        $handle = fopen($file->getPathname(), 'r');
        
        // Get header row to determine format
        $header = fgetcsv($handle);
        $isMerchantFormat = count($header) === 3; // name, address, is_active
        
        while (($row = fgetcsv($handle)) !== false) {
            if ($isMerchantFormat && count($row) >= 3) {
                $data[] = [
                    'name' => trim($row[0] ?? ''),
                    'address' => trim($row[1] ?? ''),
                    'is_active' => trim($row[2] ?? '1')
                ];
            } elseif (count($row) >= 5) {
                $data[] = [
                    'name' => trim($row[0] ?? ''),
                    'address' => trim($row[1] ?? ''),
                    'merchant_id' => trim($row[2] ?? ''),
                    'status' => trim($row[3] ?? 'pending'),
                    'is_active' => trim($row[4] ?? '0')
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
            
            // Get header row to determine format
            $header = fgetcsv($handle);
            $isMerchantFormat = count($header) === 3; // name, address, is_active
            
            while (($row = fgetcsv($handle)) !== false) {
                if ($isMerchantFormat && count($row) >= 3) {
                    $data[] = [
                        'name' => trim($row[0] ?? ''),
                        'address' => trim($row[1] ?? ''),
                        'is_active' => trim($row[2] ?? '1')
                    ];
                } elseif (count($row) >= 5) {
                    $data[] = [
                        'name' => trim($row[0] ?? ''),
                        'address' => trim($row[1] ?? ''),
                        'merchant_id' => trim($row[2] ?? ''),
                        'status' => trim($row[3] ?? 'pending'),
                        'is_active' => trim($row[4] ?? '0')
                    ];
                }
            }
            
            fclose($handle);
            return $data;
            
        } catch (\Exception $e) {
            throw new \Exception('Failed to read Excel file: ' . $e->getMessage());
        }
    }
}
