<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminCustomerStoreRequest;
use App\Http\Requests\AdminCustomerUpdateRequest;
use App\Models\Customer;
use App\Models\Merchant;
use App\Services\CustomerService;
use App\Traits\Select2Trait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class AdminCustomerController extends Controller
{
    use Select2Trait;

    public function __construct(private readonly CustomerService $customerService)
    {
    }

    /**
     * Return customers for select2 dropdown (filtered by merchant if specified)
     */
    public function select(Request $request): JsonResponse
    {
        $query = Customer::withCountry();
        
        // If merchant_id is provided, filter by it
        if ($request->has('merchant_id') && $request->merchant_id) {
            $query->where('merchant_id', $request->merchant_id);
        }
        
        return $this->getSelect2DataInNormalSearch($request, $query, ['name', 'email']);
    }

    /**
     * Store a new customer via AJAX
     */
    public function storeAjax(AdminCustomerStoreRequest $request): JsonResponse
    {
        try {
            $customer = $this->customerService->create($request->validated());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
                'data' => null
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'id' => $customer->id,
            'text' => $customer->name . ' (' . $customer->email . ')'
        ]);
    }

    /**
     * Get customers list for admin
     */
    public function index(): View
    {
        $merchants = Merchant::where('is_active', true)->get();
        return view('admin.customers.index', compact('merchants'));
    }

    /**
     * Show the form for creating a new customer
     */
    public function create(): View
    {
        return view('admin.customers.create');
    }

    /**
     * Show the form for editing the specified customer
     */
    public function edit(Customer $customer): View
    {
        $merchants = Merchant::where('is_active', true)->get();
        return view('admin.customers.edit', compact('customer', 'merchants'));
    }

    /**
     * Store a newly created customer in storage
     */
    public function store(AdminCustomerStoreRequest $request): RedirectResponse
    {
        try {
            $this->customerService->create($request->validated());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('admin.customers.index')->with('success', 'Customer created successfully');
    }

    /**
     * Get customers data for DataTable
     */
    public function data(Request $request)
    {
        $query = Customer::with('merchant')->withCountry();

        // Filter by merchant if specified
        if ($request->filled('merchant_id')) {
            $query->where('merchant_id', $request->merchant_id);
        }

        // Country filter
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
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

        // Search functionality (support both custom "search" string and DataTables search[value])
        $textSearch = is_array($request->search) ? ($request->search['value'] ?? null) : $request->get('search');
        if (!empty($textSearch)) {
            $query->where(function ($q) use ($textSearch) {
                $q->where('name', 'like', "%{$textSearch}%")
                  ->orWhere('email', 'like', "%{$textSearch}%")
                  ->orWhere('phone', 'like', "%{$textSearch}%")
                  ->orWhereHas('merchant', function ($merchantQuery) use ($textSearch) {
                      $merchantQuery->where('name', 'like', "%{$textSearch}%");
                  });
            });
        }

        return DataTables::of($query)
            ->addColumn('record_select', function ($customer) {
                return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="selected_customers[]" value="' . $customer->id . '" />
                        </div>';
            })
            ->addColumn('customer_info', function ($customer) {
                return view('admin.customers.data_table.customer_info', compact('customer'))->render();
            })
            ->addColumn('merchant_name', function ($customer) {
                return $customer->merchant ? $customer->merchant->name : 'N/A';
            })
            ->addColumn('actions', function ($customer) {
                return view('admin.customers.partials.actions', compact('customer'))->render();
            })
            ->addColumn('address_info', function ($customer) {
                $addressParts = [];
                if ($customer->address) $addressParts[] = $customer->address;
                if ($customer->city_id && $customer->city) $addressParts[] = $customer->city->name;
                elseif ($customer->city) $addressParts[] = $customer->city;
                if ($customer->state) $addressParts[] = $customer->state;
                if ($customer->zip) $addressParts[] = $customer->zip;
                
                return !empty($addressParts) ? implode(', ', $addressParts) : 'N/A';
            })
            ->addColumn('created_at', function ($customer) {
                return $customer->created_at ? $customer->created_at->format('M d, Y H:i:s') : 'N/A';
            })
            ->addColumn('country', function ($customer) {
                return $customer->country ? $customer->country->name : 'N/A';
            })
            ->rawColumns(['record_select', 'actions', 'customer_info'])
            ->make(true);
    }

    /**
     * Export customers as CSV with current filters
     */
    public function export(Request $request)
    {
        $query = Customer::with(['merchant', 'country']);

        if ($request->filled('merchant_id')) {
            $query->where('merchant_id', $request->merchant_id);
        }

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

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

        $textSearch = $request->get('search');
        if (!empty($textSearch)) {
            $query->where(function ($q) use ($textSearch) {
                $q->where('name', 'like', "%{$textSearch}%")
                  ->orWhere('email', 'like', "%{$textSearch}%")
                  ->orWhere('phone', 'like', "%{$textSearch}%")
                  ->orWhereHas('merchant', function ($merchantQuery) use ($textSearch) {
                      $merchantQuery->where('name', 'like', "%{$textSearch}%");
                  });
            });
        }

        $fileName = 'customers_export_' . now()->format('Y_m_d_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            // Header
            fputcsv($handle, ['ID', 'Name', 'Email', 'Phone', 'Merchant', 'Country', 'Created At']);
            // Rows
            $query->chunk(1000, function ($customers) use ($handle) {
                foreach ($customers as $customer) {
                    fputcsv($handle, [
                        $customer->id,
                        $customer->name,
                        $customer->email,
                        $customer->phone,
                        optional($customer->merchant)->name,
                        optional($customer->country)->name,
                        optional($customer->created_at)?->format('Y-m-d H:i:s'),
                    ]);
                }
            });
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Update customer
     */
    public function update(AdminCustomerUpdateRequest $request, Customer $customer): JsonResponse|RedirectResponse
    {
        try {
            $customer = $this->customerService->update($customer, $request->validated());
        } catch (\Illuminate\Validation\ValidationException $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                    'data' => null
                ], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        }

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully',
                'data' => $customer
            ]);
        }
        
        return redirect()->route('admin.customers.index')->with('success', 'Customer updated successfully');
    }

    /**
     * Delete customer
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $this->customerService->delete($customer);

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully'
        ]);
    }

    /**
     * Bulk delete customers
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:customers,id'
        ]);

        $deletedCount = Customer::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deletedCount} customers deleted successfully"
        ]);
    }

    /**
     * Show customer details
     */
    public function show(Customer $customer): View
    {
        return view('admin.customers.show', compact('customer'));
    }

    /**
     * Export template for customers import
     */
    public function exportTemplate()
    {
        return $this->customerService->exportTemplate();
    }

    /**
     * Preview customers import data
     */
    public function importPreview(Request $request): JsonResponse
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
            'merchant_id' => 'required|exists:merchants,id'
        ]);

        try {
            $result = $this->customerService->importPreview($request->file('import_file'), $request->input('merchant_id'));
            
            return response()->json([
                'success' => true,
                'data' => $result['data'] ?? [],
                'errors' => $result['errors'] ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Preview failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import customers from file
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
            'merchant_id' => 'required|exists:merchants,id'
        ]);

        try {
            $result = $this->customerService->import($request->file('import_file'), $request->input('merchant_id'));
            
            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Customers imported successfully',
                'imported_count' => $result['imported_count'] ?? 0,
                'skipped_count' => $result['skipped_count'] ?? 0,
                'errors' => $result['errors'] ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
