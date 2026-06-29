<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Country;
use App\Models\City;
use App\Models\PaymentByLink;
use App\Services\CustomerService;
use App\Support\CsvExport;
use App\Repositories\CustomerRepository;
use App\Traits\Select2Trait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Http\Requests\CustomerStoreRequest;
use App\Http\Requests\CustomerUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class MerchantCustomerController extends Controller
{
    use AuthorizesRequests, Select2Trait;

    public function __construct(private readonly CustomerService $customerService, private readonly CustomerRepository $customerRepository)
    {
    }

    /**
     * Return customers for select2 dropdown (filtered by merchant)
     */
    public function select(Request $request): JsonResponse
    {
        $merchantId = Auth::guard('web')->user()->merchant_id;
        $query = $this->customerRepository->queryByMerchant($merchantId);
        return $this->getSelect2DataInNormalSearch($request, $query, ['name', 'email']);
    }

    /**
     * Store a new customer for the authenticated merchant
     */
    public function storeAjax(CustomerStoreRequest $request): JsonResponse
    {
        // dd($request->all());
        // if (!auth()->user()->can('customers') && !auth()->user()->can('create_customers')) {
        //     abort(403, 'Unauthorized access to create customers.');
        // }
        // dd($request->all());
        $request->validated();

        // dd($request->all());
        $merchantId = Auth::guard('web')->user()->merchant_id;

        try {
            $customer = $this->customerService->createForMerchant($merchantId, $request->validated());
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
     * Get customers list for the authenticated merchant
     */
    public function index()
    {
        return view('merchant.customers.index');
    }

    /**
     * Show the form for creating a new customer
     */
    public function create()
    {
        return view('merchant.customers.create');
    }

    /**
     * Show the form for editing the specified customer
     */
    public function edit(Customer $customer)
    {
        // Verify the customer belongs to the authenticated merchant
        $merchantId = Auth::guard('web')->user()->merchant_id;
        if ($customer->merchant_id !== $merchantId) {
            abort(404, 'Customer not found');
        }

        return view('merchant.customers.edit', compact('customer'));
    }

    /**
     * Display the specified customer details for the authenticated merchant
     */
    public function show(Customer $customer): View
    {
        // Verify the customer belongs to the authenticated merchant
        $merchantId = Auth::guard('web')->user()->merchant_id;
        if ($customer->merchant_id !== $merchantId) {
            abort(404, 'Customer not found');
        }

        // Fetch the latest payment link for this customer (scoped to merchant)
        $lastPaymentLink = $this->customerRepository->latestPaymentLinkForCustomer($merchantId, $customer->id);

        return view('merchant.customers.show', compact('customer', 'lastPaymentLink'));
    }

    /**
     * Store a newly created customer in storage
     */
    public function store(CustomerStoreRequest $request)
    {
        $request->validated();

        $merchantId = Auth::guard('web')->user()->merchant_id;

        try {
            $this->customerService->createForMerchant($merchantId, $request->validated());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('merchant.customers.index')->with('success', 'Customer created successfully');
    }

    /**
     * Get customers data for DataTable
     */
    public function data(Request $request)
    {
        $merchantId = Auth::guard('web')->user()->merchant_id;
        
        if (!$merchantId) {
            return response()->json(['error' => 'Merchant not found'], 403);
        }

        $query = Customer::where('merchant_id', $merchantId)
            ->with(['city', 'country']);

        // Apply filters
        if ($request->filled('country')) {
            $query->where('country_id', $request->country);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('phone', 'like', "%{$searchTerm}%");
            });
        }

        return DataTables::of($query)
            ->addColumn('actions', function ($customer) {
                return view('merchant.customers.partials.actions', compact('customer'))->render();
            })
            ->addColumn('address_info', function ($customer) {
                $addressParts = [];
                if ($customer->address) $addressParts[] = $customer->address;
                if ($customer->city) $addressParts[] = $customer->city->name;
                if ($customer->state) $addressParts[] = $customer->state;
                if ($customer->zip) $addressParts[] = $customer->zip;
                
                return !empty($addressParts) ? implode(', ', $addressParts) : 'N/A';
            })
            ->addColumn('created_at', function ($customer) {
                return $customer->created_at ? $customer->created_at->format('M d, Y H:i:s') : 'N/A';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Update customer for the authenticated merchant
     */
    public function update(CustomerUpdateRequest $request, Customer $customer): JsonResponse|RedirectResponse
    {
        $merchantId = Auth::guard('web')->user()->merchant_id;

        $request->validated();

        try {
            $this->customerService->updateForMerchant($merchantId, $customer, $request->validated());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
                'data' => null
            ], 422);
        }

        if(request()->ajax()){
            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully',
                'data' => $customer
            ]);
        }else{ 
            return redirect()->route('merchant.customers.index')->with('success', 'Customer updated successfully');
        }
        
    }

    /**
     * Delete customer for the authenticated merchant
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $merchantId = Auth::guard('web')->user()->merchant_id;

        try {
            $this->customerService->deleteForMerchant($merchantId, $customer);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully'
        ]);
    }

    /**
     * Export customers for the authenticated merchant
     */
    public function export(Request $request)
    {
        $merchantId = Auth::guard('web')->user()->merchant_id;
        
        if (!$merchantId) {
            return response()->json(['error' => 'Merchant not found'], 403);
        }

        $query = Customer::where('merchant_id', $merchantId)
            ->with(['city', 'country']);

        // Apply filters
        if ($request->filled('country')) {
            $query->where('country_id', $request->country);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('phone', 'like', "%{$searchTerm}%");
            });
        }

        $customers = $query->get();

        $filename = 'customers_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($customers) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'ID',
                'Name', 
                'Email',
                'Phone',
                'Address',
                'City',
                'State',
                'ZIP Code',
                'Country',
                'Created At'
            ]);

            // Add customer data
            foreach ($customers as $customer) {
                fputcsv($file, [
                    $customer->id,
                    $customer->name,
                    $customer->email,
                    CsvExport::asText($customer->phone),
                    $customer->address,
                    $customer->city ? $customer->city->name : 'N/A',
                    $customer->state,
                    $customer->zip,
                    $customer->country ? $customer->country->name : 'N/A',
                    $customer->created_at ? $customer->created_at->format('Y-m-d H:i:s') : 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Provide a sample CSV template for customer import
     */
    public function exportTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        // Build XLSX with country dropdown
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Customers Template');

        // Headers
        $headers = ['name', 'email', 'address', 'country', 'state', 'zip'];
        foreach ($headers as $index => $header) {
            $columnLetter = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($columnLetter . '1', $header);
        }

        // Example row
        $sheet->fromArray([
            ['John Doe', 'john@example.com', '123 Main St', 'United States', 'NY', '10001']
        ], null, 'A2');

        // Countries sheet
        $countriesSheet = $spreadsheet->createSheet();
        $countriesSheet->setTitle('Countries');
        $countriesSheet->setCellValue('A1', 'name');
        $countriesSheet->setCellValue('B1', 'code');

        $locale = app()->getLocale() ?: 'en';
        $countries = Country::select('id', 'short_name', 'name')->orderBy('name->en')->get();

        $row = 2;
        foreach ($countries as $country) {
            // Prefer localized name, fallback to EN, then raw
            $name = $country->getTranslation('name', $locale, false) ?: ($country->getTranslation('name', 'en', false) ?: (is_array($country->name) ? reset($country->name) : (string)$country->name));
            $countriesSheet->setCellValue('A' . $row, $name);
            $countriesSheet->setCellValue('B' . $row, strtoupper((string) $country->short_name));
            $row++;
        }

        $lastCountryRow = max(2, $row - 1);
        $listRange = "'Countries'!\$A\$2:\$A\$" . $lastCountryRow;

        // Apply dropdown validation to country column (D) rows 2..1000
        for ($r = 2; $r <= 1000; $r++) {
            $validation = $sheet->getCell('D' . $r)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setError('Invalid country.');
            $validation->setPromptTitle('Country');
            $validation->setPrompt('Please select a country from the list.');
            $validation->setFormula1($listRange);
        }

        // Stream as XLSX
        $filename = 'customers_template.xlsx';
        $tempPath = storage_path('app/' . $filename);
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Import customers for the authenticated merchant from CSV/XLSX.
     */
    public function import(Request $request): RedirectResponse
    {
        // dd($request->all());
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],
        ]);

        $merchantId = Auth::guard('web')->user()->merchant_id;

        $filePath = $request->file('file')->getRealPath();

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();
            $highestColumn = $sheet->getHighestDataColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

            // Read header row (1-based)
            $headers = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $value = trim((string) $sheet->getCell($colLetter . '1')->getValue());
                $headers[$col] = Str::of($value)->lower()->trim()->toString();
            }

            // Map common header aliases to internal keys
            $headerAliasMap = [
                'name' => ['name', 'customer_name', 'full_name'],
                'email' => ['email', 'e-mail'],
                'phone' => ['phone', 'mobile', 'telephone', 'phone_number'],
                'address' => ['address', 'street'],
                'country' => ['country', 'country_id', 'country_code', 'country_name'],
                'city' => ['city', 'city_id', 'city_name'],
                'state' => ['state', 'province', 'region'],
                'zip' => ['zip', 'zip_code', 'postal', 'postal_code'],
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

            // Keep minimal columns per user's latest request
            $requiredKeys = ['name', 'email'];
            foreach ($requiredKeys as $reqKey) {
                if (!in_array($reqKey, array_values($columnKeyByIndex), true)) {
                    return back()->with('error', "Missing required column: {$reqKey}");
                }
            }

            $created = 0;
            $skipped = 0;
            $errors = [];

            for ($row = 2; $row <= $highestRow; $row++) {
                // Build row data by internal keys
                $rowData = [
                    'name' => null,
                    'email' => null,
                    'phone' => null,
                    'address' => null,
                    'state' => null,
                    'zip' => null,
                    'country' => null,
                    'city' => null,
                ];

                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    if (!isset($columnKeyByIndex[$col])) {
                        continue;
                    }
                    $key = $columnKeyByIndex[$col];
                    $colLetter = Coordinate::stringFromColumnIndex($col);
                    $value = $sheet->getCell($colLetter . $row)->getFormattedValue();
                    $rowData[$key] = is_string($value) ? trim($value) : $value;
                }

                // Skip empty rows
                if (!array_filter($rowData)) {
                    continue;
                }

                try {
                    $payload = [
                        'name' => (string) ($rowData['name'] ?? ''),
                        'email' => (string) ($rowData['email'] ?? ''),
                        'phone' => (string) ($rowData['phone'] ?? ''),
                        'address' => $rowData['address'] ?? null,
                        'state' => $rowData['state'] ?? null,
                        'zip' => $rowData['zip'] ?? null,
                    ];

                    // Resolve country and city
                    [$countryId, $cityId] = $this->resolveCountryAndCity($rowData['country'] ?? null, $rowData['city'] ?? null);
                    // dd($countryId, $cityId);
                    if ($countryId) {
                        $payload['country_id'] = $countryId;
                    }
                    if ($cityId) {
                        $payload['city_id'] = $cityId;
                    }

                    $this->customerService->createForMerchant($merchantId, $payload);
                    $created++;
                } catch (ValidationException $ve) {
                    $skipped++;
                    $errors[] = "Row {$row}: " . json_encode($ve->errors());
                } catch (\Throwable $e) {
                    $skipped++;
                    Log::warning('Customer import row failed', ['row' => $row, 'error' => $e->getMessage()]);
                    $errors[] = "Row {$row}: " . $e->getMessage();
                }
            }

            $message = "Imported customers: {$created}. Skipped: {$skipped}.";
            if (!empty($errors)) {
                // Keep errors short in flash; long details in logs
                $preview = implode(' | ', array_slice($errors, 0, 3));
                return redirect()->route('merchant.customers.index')->with('success', $message)->with('warning', $preview);
            }

            return redirect()->route('merchant.customers.index')->with('success', $message);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to process the file: ' . $e->getMessage());
        }
    }

    /**
     * Parse file and return a JSON preview without inserting.
     * Flags: countryResolved, cityResolved, duplicateEmail.
     */
    public function importPreview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],
        ]);

        $merchantId = Auth::guard('web')->user()->merchant_id;
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
                'name' => ['name', 'customer_name', 'full_name'],
                'email' => ['email', 'e-mail'],
                'phone' => ['phone', 'mobile', 'telephone', 'phone_number'],
                'address' => ['address', 'street'],
                'country' => ['country', 'country_id', 'country_code', 'country_name'],
                'city' => ['city', 'city_id', 'city_name'],
                'state' => ['state', 'province', 'region'],
                'zip' => ['zip', 'zip_code', 'postal', 'postal_code'],
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

                [$countryId, $cityId] = $this->resolveCountryAndCity($rowData['country'] ?? null, $rowData['city'] ?? null);
                $duplicateEmail = isset($rowData['email']) && $this->customerRepository->emailExistsForMerchant($rowData['email'], $merchantId);

                $rows[] = [
                    'original' => $rowData,
                    'resolved' => [
                        'country_id' => $countryId,
                        'city_id' => $cityId,
                    ],
                    'flags' => [
                        'countryResolved' => (bool) $countryId,
                        'cityResolved' => (bool) $cityId,
                        'duplicateEmail' => (bool) $duplicateEmail,
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
     * Resolve country and city IDs from various inputs (id, code, name en/ar).
     */
    private function resolveCountryAndCity($countryInput, $cityInput): array
    {
        $countryId = null;
        $cityId = null;

        // Resolve country
        if ($countryInput !== null && $countryInput !== '') {
            $candidate = trim((string) $countryInput);
            if (ctype_digit($candidate)) {
                $country = Country::find((int) $candidate);
            } else {
                $country = Country::query()
                    ->where('short_name', strtoupper($candidate))
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$." . app()->getLocale() . "'))) = ?", [strtolower($candidate)])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) = ?", [strtolower($candidate)])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar'))) = ?", [strtolower($candidate)])
                    ->first();
            }
            $countryId = $country?->id;
        }

        // Resolve city
        if ($cityInput !== null && $cityInput !== '') {
            $candidate = trim((string) $cityInput);
            $cityQuery = City::query();
            if ($countryId) {
                $cityQuery->where('country_id', $countryId);
            }
            if (ctype_digit($candidate)) {
                $city = $cityQuery->where('id', (int) $candidate)->first();
            } else {
                $city = $cityQuery
                    ->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$." . app()->getLocale() . "'))) = ?", [strtolower($candidate)])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) = ?", [strtolower($candidate)])
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar'))) = ?", [strtolower($candidate)])
                    ->first();
            }
            $cityId = $city?->id;
        }

        return [$countryId, $cityId];
    }
}
