<?php

namespace App\Services;

use App\Models\Branch;
use App\Repositories\BranchRepository;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\DB;

class AdminBranchService
{
    protected $branchRepository;

    public function __construct(BranchRepository $branchRepository)
    {
        $this->branchRepository = $branchRepository;
    }

    public function index(Request $request)
    {
        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'merchant_id' => $request->input('merchant_id'),
            'country_id' => $request->input('country_id'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $perPage = $request->input('per_page', 15);
        return $this->branchRepository->paginate($filters, $perPage);
    }

    public function show(string $id)
    {
        $branch = $this->branchRepository->find($id);
        
        $stats = [
            'total_users' => 0, // Implement if Branch has users relationship
            'total_terminals' => 0, // Implement if Branch has terminals relationship
        ];

        return [
            'branch' => $branch,
            'statistics' => $stats,
        ];
    }

    public function store(Request $request): Branch
    {
        $validated = $request->validate([
            'merchant_id' => 'required|exists:merchants,id',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'country_id' => 'required|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'is_active' => 'boolean',
        ]);

        $validated['status'] = 'pending'; // Default status for new branches

        return $this->branchRepository->create($validated);
    }

    public function update(Request $request, string $id): Branch
    {
        $branch = $this->branchRepository->find($id);

        $validated = $request->validate([
            'merchant_id' => 'sometimes|exists:merchants,id',
            'name' => 'sometimes|string|max:255',
            'address' => 'nullable|string',
            'country_id' => 'sometimes|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
            'is_active' => 'boolean',
        ]);

        return $this->branchRepository->update($id, $validated);
    }

    public function destroy(string $id): bool
    {
        return $this->branchRepository->delete($id);
    }

    public function statistics(): array
    {
        return [
            'total_branches' => Branch::count(),
            'active_branches' => Branch::where('status', 'approved')->where('is_active', true)->count(),
            'inactive_branches' => Branch::where('is_active', false)->count(),
            'pending_branches' => Branch::where('status', 'pending')->count(),
            'rejected_branches' => Branch::where('status', 'rejected')->count(),
            'suspended_branches' => Branch::where('status', 'suspended')->count(),
            'branches_this_month' => Branch::whereMonth('created_at', now()->month)->count(),
            'branches_today' => Branch::whereDate('created_at', now())->count(),
        ];
    }

    public function export(Request $request): array
    {
        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'merchant_id' => $request->input('merchant_id'),
            'country_id' => $request->input('country_id'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $branches = $this->branchRepository->all($filters);

        $exportData = $branches->map(function ($branch) {
            return [
                'ID' => $branch->id,
                'Name' => $branch->name,
                'Merchant' => $branch->merchant->business_name ?? 'N/A',
                'Address' => $branch->address,
                'Country' => $branch->country->getTranslation('name', 'en') ?? 'N/A',
                'City' => $branch->city ? ($branch->city->getTranslation('name', 'en') ?? 'N/A') : 'N/A',
                'Is Active' => $branch->is_active ? 'Yes' : 'No',
                'Status' => $branch->status,
                'Created At' => $branch->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return [
            'data' => $exportData,
            'filename' => 'branches_export_' . date('Y-m-d_H-i-s') . '.csv'
        ];
    }

    public function bulkDelete(array $ids): array
    {
        $count = $this->branchRepository->bulkDelete($ids);
        return [
            'message' => "{$count} branch(es) deleted successfully",
            'count' => $count
        ];
    }

    public function exportTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Branches');

        $headers = [
            'Name*', 'Merchant*', 'Address', 'Country*', 'City', 'Is Active'
        ];

        $sheet->fromArray([$headers], null, 'A1');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:F1')->getFont()->getColor()->setRGB('FFFFFF');

        $locale = app()->getLocale() ?: 'en';

        // Create Merchants reference sheet
        $merchantsSheet = $spreadsheet->createSheet();
        $merchantsSheet->setTitle('Merchants');
        $merchantsSheet->setCellValue('A1', 'Merchant Name');
        $merchantsSheet->setCellValue('B1', 'Merchant ID');
        $merchantsSheet->getStyle('A1:B1')->getFont()->setBold(true);

        $merchants = \App\Models\Merchant::orderBy('business_name')->get();
        $row = 2;
        foreach ($merchants as $merchant) {
            $merchantsSheet->setCellValue('A' . $row, $merchant->business_name);
            $merchantsSheet->setCellValue('B' . $row, $merchant->id);
            $row++;
        }
        $merchantListRange = "'Merchants'!\$A\$2:\$A\$" . ($row - 1);

        // Create Countries reference sheet
        $countriesSheet = $spreadsheet->createSheet();
        $countriesSheet->setTitle('Countries');
        $countriesSheet->setCellValue('A1', 'Country Name');
        $countriesSheet->setCellValue('B1', 'Country Code');
        $countriesSheet->getStyle('A1:B1')->getFont()->setBold(true);

        $countries = \App\Models\Country::orderBy('name->en')->get();
        $row = 2;
        foreach ($countries as $country) {
            $countryName = $country->getTranslation('name', $locale, false) ?: 
                          ($country->getTranslation('name', 'en', false) ?: 
                          (is_array($country->name) ? reset($country->name) : (string)$country->name));
            
            $countriesSheet->setCellValue('A' . $row, $countryName);
            $countriesSheet->setCellValue('B' . $row, $country->short_name);
            $row++;
        }
        $countryListRange = "'Countries'!\$A\$2:\$A\$" . ($row - 1);

        // Apply data validation dropdown to Merchant column (B)
        for ($r = 2; $r <= 1000; $r++) {
            $validation = $sheet->getCell('B' . $r)->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
            $validation->setAllowBlank(false);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setError('Invalid merchant. Please select from the list.');
            $validation->setPromptTitle('Merchant Selection');
            $validation->setPrompt('Please select a merchant from the dropdown list.');
            $validation->setFormula1($merchantListRange);
        }

        // Apply data validation dropdown to Country column (D)
        for ($r = 2; $r <= 1000; $r++) {
            $validation = $sheet->getCell('D' . $r)->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
            $validation->setAllowBlank(false);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setError('Invalid country. Please select from the list.');
            $validation->setPromptTitle('Country Selection');
            $validation->setPrompt('Please select a country from the dropdown list.');
            $validation->setFormula1($countryListRange);
        }

        // Example row
        $sheet->fromArray([
            [
                'Main Branch', 
                'ABC Trading Company', 
                '123 Business Street, Dubai', 
                'United Arab Emirates', 
                'Dubai', 
                '1'
            ]
        ], null, 'A2');

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        foreach (range('A', 'B') as $col) {
            $countriesSheet->getColumnDimension($col)->setAutoSize(true);
            $merchantsSheet->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'branches_import_template_' . date('Y-m-d') . '.xlsx';
        $tempPath = storage_path('app/' . $filename);
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    public function importPreview($file): array
    {
        $rows = [];
        
        $filePath = $file->getRealPath();
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        $headers = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $value = trim((string) $sheet->getCell($colLetter . '1')->getValue());
            $value = str_replace('*', '', $value);
            $headers[$col] = \Illuminate\Support\Str::of($value)->lower()->trim()->toString();
        }

        $headerAliasMap = [
            'name' => ['name', 'branch name'],
            'merchant' => ['merchant', 'merchant name'],
            'address' => ['address', 'location'],
            'country' => ['country', 'country name'],
            'city' => ['city', 'city name'],
            'is_active' => ['is active', 'is_active', 'active', 'status'],
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

        $locale = app()->getLocale() ?: 'en';

        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                if (!isset($columnKeyByIndex[$col])) continue;
                $key = $columnKeyByIndex[$col];
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $value = $sheet->getCell($colLetter . $row)->getFormattedValue();
                $rowData[$key] = is_string($value) ? trim($value) : $value;
            }

            if (!array_filter($rowData)) continue;

            $errors = [];
            $warnings = [];
            
            if (empty($rowData['name'])) { $errors[] = 'Name is required'; }
            
            if (empty($rowData['merchant'])) {
                $errors[] = 'Merchant is required';
            } else {
                $merchantInput = trim((string) $rowData['merchant']);
                $merchant = \App\Models\Merchant::where('business_name', 'like', "%{$merchantInput}%")->first();
                
                if ($merchant) {
                    $rowData['merchant_id'] = $merchant->id;
                } else {
                    $errors[] = 'Merchant not found in database';
                }
            }
            
            if (empty($rowData['country'])) {
                $errors[] = 'Country is required';
            } else {
                $countryInput = trim((string) $rowData['country']);
                $country = \App\Models\Country::query()
                    ->where('short_name', strtoupper($countryInput))
                    ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$." . $locale . "'))) = ?", [strtolower($countryInput)])
                    ->first();
                
                if ($country) {
                    $rowData['country_id'] = $country->id;
                } else {
                    $errors[] = 'Country not found in database';
                }
            }
            
            if (!empty($rowData['city']) && isset($rowData['country_id'])) {
                $cityInput = trim((string) $rowData['city']);
                $city = \App\Models\City::query()
                    ->where('country_id', $rowData['country_id'])
                    ->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$." . $locale . "'))) = ?", [strtolower($cityInput)])
                    ->first();
                
                if ($city) {
                    $rowData['city_id'] = $city->id;
                } else {
                    $warnings[] = 'City not found in database';
                }
            }

            $rows[] = [
                'row_number' => $row,
                'data' => $rowData,
                'errors' => $errors,
                'warnings' => $warnings,
                'valid' => empty($errors)
            ];
        }

        $validCount = count(array_filter($rows, fn($r) => $r['valid']));
        $invalidCount = count($rows) - $validCount;

        return [
            'rows' => $rows,
            'summary' => [
                'total' => count($rows),
                'valid' => $validCount,
                'invalid' => $invalidCount
            ]
        ];
    }

    public function import(Request $request): array
    {
        $file = $request->file('import_file');
        $importedCount = 0;
        $failedCount = 0;
        $errors = [];

        $filePath = $file->getRealPath();
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        $headers = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $value = trim((string) $sheet->getCell($colLetter . '1')->getValue());
            $value = str_replace('*', '', $value);
            $headers[$col] = \Illuminate\Support\Str::of($value)->lower()->trim()->toString();
        }

        $headerAliasMap = [
            'name' => ['name', 'branch name'],
            'merchant' => ['merchant', 'merchant name'],
            'address' => ['address', 'location'],
            'country' => ['country', 'country name'],
            'city' => ['city', 'city name'],
            'is_active' => ['is active', 'is_active', 'active'],
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

        $locale = app()->getLocale() ?: 'en';

        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                if (!isset($columnKeyByIndex[$col])) continue;
                $key = $columnKeyByIndex[$col];
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $value = $sheet->getCell($colLetter . $row)->getFormattedValue();
                $rowData[$key] = is_string($value) ? trim($value) : $value;
            }

            if (!array_filter($rowData)) continue;

            try {
                DB::beginTransaction();

                if (!empty($rowData['merchant'])) {
                    $merchant = \App\Models\Merchant::where('business_name', 'like', "%{$rowData['merchant']}%")->first();
                    
                    if ($merchant) {
                        $rowData['merchant_id'] = $merchant->id;
                    } else {
                        throw new \Exception("Merchant '{$rowData['merchant']}' not found.");
                    }
                } else {
                    throw new \Exception("Merchant is required.");
                }

                if (!empty($rowData['country'])) {
                    $country = \App\Models\Country::query()
                        ->where('short_name', strtoupper($rowData['country']))
                        ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$." . $locale . "'))) = ?", [strtolower($rowData['country'])])
                        ->first();
                    
                    if ($country) {
                        $rowData['country_id'] = $country->id;
                    } else {
                        throw new \Exception("Country '{$rowData['country']}' not found.");
                    }
                } else {
                    throw new \Exception("Country is required.");
                }

                if (!empty($rowData['city']) && isset($rowData['country_id'])) {
                    $city = \App\Models\City::query()
                        ->where('country_id', $rowData['country_id'])
                        ->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$." . $locale . "'))) = ?", [strtolower($rowData['city'])])
                        ->first();
                    
                    if ($city) {
                        $rowData['city_id'] = $city->id;
                    }
                }

                $rowData['status'] = 'pending';
                $rowData['is_active'] = isset($rowData['is_active']) ? (bool)$rowData['is_active'] : true;

                $this->branchRepository->create($rowData);
                DB::commit();
                $importedCount++;

            } catch (\Exception $e) {
                DB::rollback();
                $failedCount++;
                $errors[] = "Row {$row}: " . $e->getMessage();
            }
        }

        return [
            'message' => "Import completed: {$importedCount} imported, {$failedCount} failed",
            'imported_count' => $importedCount,
            'failed_count' => $failedCount,
            'errors' => $errors
        ];
    }

    public function approve(string $id): array
    {
        DB::beginTransaction();
        try {
            $branch = $this->branchRepository->find($id);
            $branch->update([
                'status' => 'approved',
                'is_active' => true
            ]);
            DB::commit();
            return [
                'message' => 'Branch approved successfully',
                'branch' => $branch
            ];
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function reject(string $id, string $rejectionReason = '', array $invalidFields = []): array
    {
        DB::beginTransaction();
        try {
            $branch = $this->branchRepository->find($id);
            $branch->update(['status' => 'rejected']);
            DB::commit();
            return [
                'message' => 'Branch rejected successfully',
                'branch' => $branch
            ];
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function suspend(string $id, string $suspensionReason = ''): array
    {
        DB::beginTransaction();
        try {
            $branch = $this->branchRepository->find($id);
            $branch->update([
                'status' => 'suspended',
                'is_active' => false
            ]);
            DB::commit();
            return [
                'message' => 'Branch suspended successfully',
                'branch' => $branch
            ];
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function unsuspend(string $id): array
    {
        DB::beginTransaction();
        try {
            $branch = $this->branchRepository->find($id);
            $branch->update([
                'status' => 'approved',
                'is_active' => true
            ]);
            DB::commit();
            return [
                'message' => 'Branch unsuspended successfully',
                'branch' => $branch
            ];
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}


