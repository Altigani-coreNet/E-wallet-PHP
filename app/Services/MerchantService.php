<?php

namespace App\Services;

use App\Mail\MerchantApprovalMail;
use App\Mail\MerchantRejectionMail;
use App\Mail\WelcomeMail;
use App\Models\Merchant;
use App\Models\MerchantRejection;
use App\Models\Role;
use App\Models\User;
use App\Repositories\MerchantRepository;
use App\Traits\HasFiles;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Spatie\Permission\Models\Permission;

class MerchantService
{
    use HasFiles;

    public function __construct(protected MerchantRepository $merchantRepository)
    {
    }

    /**
     * Get paginated merchants list with filters.
     */
    public function index(Request $request): LengthAwarePaginator
    {
        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'country_id' => $request->input('country_id'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $perPage = (int) $request->input('per_page', 15);

        return $this->merchantRepository->getPaginated($filters, $perPage);
    }

    /**
     * Get merchant details with aggregated data.
     */
    public function show(string $id): array
    {
        $merchant = $this->merchantRepository->findOrFail($id);

        $merchant->load([
            'logs' => function ($query) {
                $query->latest()->limit(10);
            },
            'user',
            'country',
            'city',
            'attachments',
            'plan',
        ]);

        $statistics = [
            'total_users' => $merchant->users()->count(),
            'total_branches' => $merchant->branches()->count(),
            'total_terminals' => $merchant->terminals()->count(),
        ];

        $pendingChangeRequests = 0;
        if (class_exists(\App\Models\ChangeRequest::class)) {
            $pendingChangeRequests = \App\Models\ChangeRequest::where('changeable_type', Merchant::class)
                ->where('changeable_id', $merchant->id)
                ->where('status', 'pending')
                ->count();
        }

        $profileCompletion = method_exists(Merchant::class, 'calculateProfileCompletion')
            ? Merchant::calculateProfileCompletion($merchant)
            : null;

        return [
            'merchant' => $merchant,
            'statistics' => $statistics,
            'pending_change_requests' => $pendingChangeRequests,
            'profile_completion' => $profileCompletion,
        ];
    }

    /**
     * Create a new merchant with onboarding workflow.
     */
    public function store(Request $request): Merchant
    {
        $data = $this->validateStore($request);

        return DB::transaction(function () use ($data, $request) {
            $logoPath = $this->storeLogoIfPresent($data['logo'] ?? null);
            if ($logoPath) {
                $data['logo'] = $logoPath;
            } else {
                unset($data['logo']);
            }

            // Keep explicit tax_number if sent; only map from tax_certified_number when present
            if (array_key_exists('tax_certified_number', $data)) {
                $data['tax_number'] = $data['tax_certified_number'] ?: ($data['tax_number'] ?? null);
                unset($data['tax_certified_number']);
            }

            $data['merchant_code'] = Merchant::generateMerchantCode();
            $data['status'] = $data['status'] ?? 'pending';
            $data['is_active'] = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true;

            $merchant = $this->merchantRepository->create($data);

            $this->uploadAdditionalImages($request, $merchant);

            [$user, $plainPassword] = $this->createMerchantUser($merchant, $data);

            if ($user) {
                $merchant->update(['user_id' => $user->id]);
                $this->assignMerchantRole($merchant, $user);
            }

            $this->logMerchantAction($merchant, 'created', null, $merchant->getAttributes(), [
                'type' => 'creation',
                'event' => 'Admin created new merchant',
                'message' => 'New merchant profile created by Admin',
            ]);

            $freshMerchant = $merchant->fresh(['user', 'country', 'city']);

            // dd($user,$plainPassword);
            if ($user && $plainPassword) {
                $this->sendWelcomeEmail($user, $plainPassword, $freshMerchant);
            }

            return $freshMerchant;
        });
    }

    /**
     * Update merchant profile.
     */
    public function update(Request $request, string $id): Merchant
    {
        $merchant = $this->merchantRepository->findOrFail($id);
        $data = $this->validateUpdate($request, $merchant);
        $oldValues = $merchant->getAttributes();

        if (array_key_exists('logo', $data)) {
            $newLogoPath = $this->storeLogoIfPresent($data['logo']);
            if ($newLogoPath) {
                $this->deleteStoredFile($merchant->logo);
                $data['logo'] = $newLogoPath;
            } else {
                unset($data['logo']);
            }
        }

        if (array_key_exists('tax_certified_number', $data)) {
            $data['tax_number'] = $data['tax_certified_number'];
            unset($data['tax_certified_number']);
        }

        if (array_key_exists('scopes', $data)) {
            $data['scopes'] = $this->normalizeScopes($data['scopes']);
        }

        $updatedMerchant = $this->merchantRepository->update($merchant, $data);

        $this->uploadAdditionalImages($request, $merchant);

        $this->logMerchantAction($merchant, 'updated', $oldValues, $updatedMerchant->getAttributes(), [
            'type' => 'update',
            'event' => 'Admin updated merchant information',
            'message' => 'Merchant information updated by Admin',
        ]);

        return $updatedMerchant->load(['user', 'country', 'city', 'plan']);
    }

    /**
     * Delete a merchant.
     */
    public function destroy(string $id): bool
    {
        $merchant = $this->merchantRepository->findOrFail($id);
        $merchantData = $merchant->getAttributes();

        $this->deleteStoredFile($merchant->logo);

        $this->logMerchantAction($merchant, 'deleted', $merchantData, null, [
            'type' => 'deletion',
            'event' => 'Admin deleted merchant',
            'message' => 'Merchant was deleted by Admin',
        ]);

        return (bool) $this->merchantRepository->delete($merchant);
    }

    /**
     * Merchant statistics summary.
     */
    public function statistics(): array
    {
        return [
            'total_merchants' => Merchant::count(),
            'active_merchants' => Merchant::where('is_active', true)->count(),
            'inactive_merchants' => Merchant::where('is_active', false)->count(),
            'pending_merchants' => Merchant::where('status', 'pending')->count(),
            'approved_merchants' => Merchant::where('status', 'approved')->count(),
            'rejected_merchants' => Merchant::where('status', 'rejected')->count(),
            'suspended_merchants' => Merchant::where('status', 'suspended')->count(),
            'merchants_this_month' => Merchant::whereMonth('created_at', now()->month)->count(),
            'merchants_today' => Merchant::whereDate('created_at', now())->count(),
        ];
    }

    /**
     * Export merchants for CSV downloads.
     */
    public function export(Request $request): array
    {
        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'country_id' => $request->input('country_id'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $query = Merchant::query()->with(['country', 'city', 'currency']);

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('merchant_code', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['country_id'])) {
            $query->where('country_id', $filters['country_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $merchants = $query->get();

        $exportData = $merchants->map(function (Merchant $merchant) {
            return [
                'ID' => $merchant->id,
                'Business Name' => $merchant->business_name,
                'Owner Name' => $merchant->owner_name,
                'Email' => $merchant->email,
                'Phone' => $merchant->phone,
                'Country' => $merchant->country->name->en ?? $merchant->country->name ?? 'N/A',
                'City' => $merchant->city->name->en ?? $merchant->city->name ?? 'N/A',
                'Status' => $merchant->status,
                'Is Active' => $merchant->is_active ? 'Yes' : 'No',
                'Created At' => optional($merchant->created_at)->format('Y-m-d H:i:s'),
            ];
        });

        return [
            'data' => $exportData,
            'filename' => 'merchants_export_' . date('Y-m-d_H-i-s') . '.csv',
        ];
    }

    /**
     * Bulk delete merchants.
     */
    public function bulkDelete(array $ids): array
    {
        $deleted = $this->merchantRepository->bulkDelete($ids);

        return [
            'message' => "{$deleted} merchant(s) deleted successfully",
            'count' => $deleted,
        ];
    }

    /**
     * Approve merchant.
     */
    public function approve(string $id): array
    {
        $merchant = $this->merchantRepository->findOrFail($id);

        return DB::transaction(function () use ($merchant) {
            $this->merchantRepository->update($merchant, [
                'status' => 'approved',
                'is_active' => true,
            ]);

            $fresh = $merchant->fresh();

            $this->logMerchantAction($fresh, 'approved', null, $fresh->getAttributes(), [
                'type' => 'approval',
                'event' => 'Admin approved merchant profile',
                'message' => 'Merchant profile approved by Admin',
            ]);

            $this->sendApprovalEmail($fresh);

            return [
                'message' => 'Merchant approved successfully',
                'merchant' => $fresh,
            ];
        });
    }

    /**
     * Reject merchant.
     */
    public function reject(string $id, string $rejectionReason, array $invalidFields = []): array
    {
        $merchant = $this->merchantRepository->findOrFail($id);

        return DB::transaction(function () use ($merchant, $rejectionReason, $invalidFields) {
            $this->merchantRepository->update($merchant, ['status' => 'rejected']);

            if (class_exists(MerchantRejection::class)) {
                $rejectedById = $this->resolveRejectionActorId();

                MerchantRejection::create([
                    'merchant_id' => $merchant->id,
                    'rejection_reason' => $rejectionReason,
                    'invalid_fields' => $invalidFields,
                    'missing_attachments' => null,
                    'rejected_by' => $rejectedById,
                ]);
            }

            $fresh = $merchant->fresh();

            $this->logMerchantAction($fresh, 'rejected', null, $fresh->getAttributes(), [
                'type' => 'rejection',
                'reason' => $rejectionReason,
                'event' => 'Admin rejected merchant profile',
                'message' => 'Merchant profile rejected by Admin: ' . $rejectionReason,
            ]);

            $this->sendRejectionEmail($fresh, $rejectionReason);

            return [
                'message' => 'Merchant rejected successfully',
                'merchant' => $fresh,
            ];
        });
    }

    /**
     * Suspend merchant.
     */
    public function suspend(string $id, string $suspensionReason): array
    {
        $merchant = $this->merchantRepository->findOrFail($id);

        return DB::transaction(function () use ($merchant, $suspensionReason) {
            $oldStatus = $merchant->status;

            $this->merchantRepository->update($merchant, [
                'status' => 'suspended',
                'is_active' => false,
            ]);

            $fresh = $merchant->fresh();

            $this->logMerchantAction($fresh, 'suspended', ['status' => $oldStatus], ['status' => 'suspended'], [
                'type' => 'suspension',
                'reason' => $suspensionReason,
                'event' => 'Admin suspended merchant',
                'message' => 'Merchant suspended by Admin: ' . $suspensionReason,
            ]);

            return [
                'message' => 'Merchant suspended successfully',
                'merchant' => $fresh,
            ];
        });
    }

    /**
     * Unsuspend merchant.
     */
    public function unsuspend(string $id): array
    {
        $merchant = $this->merchantRepository->findOrFail($id);

        return DB::transaction(function () use ($merchant) {
            $oldStatus = $merchant->status;

            $this->merchantRepository->update($merchant, [
                'status' => 'approved',
                'is_active' => true,
            ]);

            $fresh = $merchant->fresh();

            $this->logMerchantAction($fresh, 'activated', ['status' => $oldStatus], ['status' => 'approved'], [
                'type' => 'activation',
                'event' => 'Admin activated suspended merchant',
                'message' => 'Merchant activated by Admin after suspension',
            ]);

            return [
                'message' => 'Merchant unsuspended successfully',
                'merchant' => $fresh,
            ];
        });
    }

    /**
     * Toggle merchant active status.
     */
    public function changeStatus(Merchant $merchant)
    {
        $oldStatus = $merchant->is_active;
        $newStatus = !$oldStatus;

        $merchant->update(['is_active' => $newStatus]);

        $this->logMerchantAction($merchant, 'status_changed', ['is_active' => $oldStatus], ['is_active' => $newStatus], [
            'type' => 'status_change',
            'event' => 'Admin changed merchant status',
            'message' => sprintf(
                'Merchant status changed from %s to %s by Admin',
                $oldStatus ? 'active' : 'inactive',
                $newStatus ? 'active' : 'inactive'
            ),
        ]);

        return $merchant->fresh();
    }

    /**
     * Get all merchants for API index endpoints.
     */
    public function getAllMerchants(Request $request): LengthAwarePaginator
    {
        $query = Merchant::with('user');

        if ($request->has('search') && !empty($request->search)) {
            $searchValue = $request->search;
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('merchant_code', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhere('phone', 'like', "%{$searchValue}%");
            });
        }

        if ($request->has('status')) {
            $query->where('is_active', $request->status);
        }

        if ($request->has('business_type')) {
            $query->where('business_type', $request->business_type);
        }

        $perPage = $request->get('per_page', 15);

        return $query->paginate($perPage);
    }

    /**
     * Get merchants for select inputs.
     */
    public function getMerchantsForSelect(Request $request): Collection
    {
        $query = Merchant::select('id', 'name', 'merchant_code');

        if ($request->has('search') && !empty($request->search)) {
            $searchValue = $request->search;
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('merchant_code', 'like', "%{$searchValue}%");
            });
        }

        $query->where('is_active', 1);

        return $query->get();
    }

    /**
     * Generate merchants import template workbook.
     */
    public function exportTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Merchants');

        $headers = [
            'Name*', 'Owner Name*', 'Business Name', 'Email*', 'Phone*',
            'Address', 'Business Type*', 'Country*', 'City',
            'Trade License Number', 'Tax Certified Number',
            'Trade License Start Date', 'Trade License Expired Date', 'Is Active',
        ];

        $sheet->fromArray([$headers], null, 'A1');
        $sheet->getStyle('A1:N1')->getFont()->setBold(true);
        $sheet->getStyle('A1:N1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:N1')->getFont()->getColor()->setRGB('FFFFFF');

        $locale = app()->getLocale() ?: 'en';

        $countriesSheet = $spreadsheet->createSheet();
        $countriesSheet->setTitle('Countries');
        $countriesSheet->setCellValue('A1', 'Country Name');
        $countriesSheet->setCellValue('B1', 'Country Code');
        $countriesSheet->getStyle('A1:B1')->getFont()->setBold(true);

        $countries = \App\Models\Country::orderBy('name->en')->get();
        $row = 2;
        foreach ($countries as $country) {
            $countryName = $country->getTranslation('name', $locale, false)
                ?: ($country->getTranslation('name', 'en', false)
                    ?: (is_array($country->name) ? reset($country->name) : (string) $country->name));

            $countriesSheet->setCellValue('A' . $row, $countryName);
            $countriesSheet->setCellValue('B' . $row, $country->short_name);
            $row++;
        }
        $countryListRange = "'Countries'!\$A\$2:\$A\$" . ($row - 1);

        $businessTypesSheet = $spreadsheet->createSheet();
        $businessTypesSheet->setTitle('Business Types');
        $businessTypesSheet->setCellValue('A1', 'Business Type');
        $businessTypesSheet->getStyle('A1')->getFont()->setBold(true);

        $businessTypes = class_exists(\App\Enums\BusinessType::class) && method_exists(\App\Enums\BusinessType::class, 'toArray')
            ? array_keys(\App\Enums\BusinessType::toArray())
            : ['Retail', 'Wholesale', 'Service', 'Restaurant', 'Ecommerce', 'Other'];

        $row = 2;
        foreach ($businessTypes as $type) {
            $businessTypesSheet->setCellValue('A' . $row, is_string($type) ? ucfirst($type) : $type);
            $row++;
        }

        $citiesSheet = $spreadsheet->createSheet();
        $citiesSheet->setTitle('Cities');
        $citiesSheet->setCellValue('A1', 'City Name');
        $citiesSheet->setCellValue('B1', 'Country');
        $citiesSheet->getStyle('A1:B1')->getFont()->setBold(true);

        $cities = \App\Models\City::with('country')->orderBy('name->en')->get();
        $row = 2;
        foreach ($cities as $city) {
            $cityName = $city->getTranslation('name', $locale, false)
                ?: ($city->getTranslation('name', 'en', false)
                    ?: (is_array($city->name) ? reset($city->name) : (string) $city->name));

            $countryName = '';
            if ($city->country) {
                $countryName = $city->country->getTranslation('name', $locale, false)
                    ?: ($city->country->getTranslation('name', 'en', false)
                        ?: (is_array($city->country->name) ? reset($city->country->name) : (string) $city->country->name));
            }

            $citiesSheet->setCellValue('A' . $row, $cityName);
            $citiesSheet->setCellValue('B' . $row, $countryName);
            $row++;
        }

        $businessTypeListRange = "'Business Types'!\$A\$2:\$A\$" . ($businessTypesSheet->getHighestRow());

        for ($r = 2; $r <= 1000; $r++) {
            $validation = $sheet->getCell('G' . $r)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(false);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setError('Invalid business type. Please select from the list.');
            $validation->setPromptTitle('Business Type Selection');
            $validation->setPrompt('Please select a business type from the dropdown list.');
            $validation->setFormula1($businessTypeListRange);
        }

        for ($r = 2; $r <= 1000; $r++) {
            $validation = $sheet->getCell('H' . $r)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setError('Invalid country. Please select from the list.');
            $validation->setPromptTitle('Country Selection');
            $validation->setPrompt('Please select a country from the dropdown list.');
            $validation->setFormula1($countryListRange);
        }

        $sheet->fromArray([
            [
                'ABC Trading Company',
                'John Doe',
                'ABC Trading Co. LLC',
                'john@example.com',
                '+971501234567',
                '123 Business Street, Dubai',
                'Retail',
                'United Arab Emirates',
                'Dubai',
                'TL123456',
                'TC789012',
                '2023-01-01',
                '2025-12-31',
                '1',
            ],
        ], null, 'A2');

        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        foreach (range('A', 'B') as $col) {
            $countriesSheet->getColumnDimension($col)->setAutoSize(true);
            $citiesSheet->getColumnDimension($col)->setAutoSize(true);
            $businessTypesSheet->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'merchants_import_template_' . date('Y-m-d') . '.xlsx';
        $tempPath = storage_path('app/' . $filename);
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Preview merchants import file.
     */
    public function importPreview($file): array
    {
        $rows = [];

        try {
            $filePath = $file->getRealPath();
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();
            $highestColumn = $sheet->getHighestDataColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

            $headers = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $value = trim((string) $sheet->getCell($colLetter . '1')->getValue());
                $value = str_replace('*', '', $value);
                $headers[$col] = Str::of($value)->lower()->trim()->toString();
            }

            $headerAliasMap = [
                'name' => ['name', 'merchant name'],
                'owner_name' => ['owner name', 'owner_name', 'ownername'],
                'business_name' => ['business name', 'business_name', 'businessname'],
                'email' => ['email', 'e-mail'],
                'phone' => ['phone', 'phone number', 'mobile'],
                'address' => ['address', 'location'],
                'business_type' => ['business type', 'business_type', 'businesstype', 'type'],
                'country' => ['country', 'country name'],
                'city' => ['city', 'city name'],
                'trade_license_number' => ['trade license number', 'trade_license_number', 'license number'],
                'tax_certified_number' => ['tax certified number', 'tax_certified_number', 'tax number'],
                'trade_license_start_date' => ['trade license start date', 'trade_license_start_date', 'license start date'],
                'trade_license_expired_date' => ['trade license expired date', 'trade_license_expired_date', 'license expiry date'],
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

            for ($rowIndex = 2; $rowIndex <= $highestRow; $rowIndex++) {
                $rowData = [];
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    if (!isset($columnKeyByIndex[$col])) {
                        continue;
                    }
                    $key = $columnKeyByIndex[$col];
                    $colLetter = Coordinate::stringFromColumnIndex($col);
                    $value = $sheet->getCell($colLetter . $rowIndex)->getFormattedValue();
                    $rowData[$key] = is_string($value) ? trim($value) : $value;
                }

                if (!array_filter($rowData)) {
                    continue;
                }

                $errors = [];
                $warnings = [];

                if (empty($rowData['name'])) {
                    $errors[] = 'Name is required';
                }
                if (empty($rowData['owner_name'])) {
                    $errors[] = 'Owner Name is required';
                }
                if (empty($rowData['email'])) {
                    $errors[] = 'Email is required';
                } elseif (!filter_var($rowData['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid email format';
                } else {
                    if (Merchant::where('email', $rowData['email'])->exists()) {
                        $errors[] = 'Email already exists';
                    }
                }
                if (empty($rowData['phone'])) {
                    $errors[] = 'Phone is required';
                }
                if (empty($rowData['business_type'])) {
                    $errors[] = 'Business Type is required';
                } else {
                    $validTypes = class_exists(\App\Enums\BusinessType::class) && method_exists(\App\Enums\BusinessType::class, 'toArray')
                        ? array_keys(\App\Enums\BusinessType::toArray())
                        : ['retail', 'wholesale', 'service', 'restaurant', 'ecommerce', 'other'];
                    if (!in_array(strtolower($rowData['business_type']), array_map('strtolower', $validTypes), true)) {
                        $errors[] = 'Invalid Business Type';
                    }
                }

                if (empty($rowData['country'])) {
                    $errors[] = 'Country is required';
                } else {
                    $countryInput = trim((string) $rowData['country']);
                    $country = null;

                    if (ctype_digit($countryInput)) {
                        $country = \App\Models\Country::find((int) $countryInput);
                    } else {
                        $country = \App\Models\Country::query()
                            ->where('short_name', strtoupper($countryInput))
                            ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"{$locale}\"'))) = ?", [strtolower($countryInput)])
                            ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"en\"'))) = ?", [strtolower($countryInput)])
                            ->first();
                    }

                    if ($country) {
                        $rowData['country_id'] = $country->id;
                    } else {
                        $errors[] = 'Country not found in database';
                    }
                }

                if (!empty($rowData['city'])) {
                    $cityInput = trim((string) $rowData['city']);
                    $cityQuery = \App\Models\City::query();

                    if (isset($rowData['country_id'])) {
                        $cityQuery->where('country_id', $rowData['country_id']);
                    }

                    if (ctype_digit($cityInput)) {
                        $city = $cityQuery->where('id', (int) $cityInput)->first();
                    } else {
                        $city = $cityQuery
                            ->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"{$locale}\"'))) = ?", [strtolower($cityInput)])
                            ->orWhereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"en\"'))) = ?", [strtolower($cityInput)])
                            ->first();
                    }

                    if ($city) {
                        $rowData['city_id'] = $city->id;
                    } else {
                        $warnings[] = 'City not found, will be left empty';
                    }
                }

                $rows[] = [
                    'original' => $rowData,
                    'errors' => $errors,
                    'warnings' => $warnings,
                    'valid' => empty($errors),
                ];
            }

            return [
                'success' => true,
                'rows' => $rows,
                'total' => count($rows),
                'valid' => collect($rows)->where('valid', true)->count(),
                'invalid' => collect($rows)->where('valid', false)->count(),
            ];
        } catch (\Throwable $e) {
            throw new \RuntimeException("Preview failed: " . $e->getMessage(), previous: $e);
        }
    }

    /**
     * Import merchants from file.
     */
    public function import($file): array
    {
        $importedCount = 0;
        $errors = [];

        try {
            DB::beginTransaction();

            $preview = $this->importPreview($file);

            if (!$preview['success']) {
                throw new \RuntimeException('Preview validation failed');
            }

            foreach ($preview['rows'] as $index => $rowInfo) {
                if (!$rowInfo['valid']) {
                    $errors[] = "Row " . ($index + 2) . ": " . implode(', ', $rowInfo['errors']);
                    continue;
                }

                try {
                    $row = $rowInfo['original'];

                    $merchant = Merchant::create([
                        'name' => $row['name'],
                        'owner_name' => $row['owner_name'],
                        'business_name' => $row['business_name'] ?? $row['name'],
                        'email' => $row['email'],
                        'phone' => $row['phone'],
                        'address' => $row['address'] ?? null,
                        'business_type' => strtolower($row['business_type']),
                        'country_id' => $row['country_id'],
                        'city_id' => $row['city_id'] ?? null,
                        'trade_license_number' => $row['trade_license_number'] ?? null,
                        'tax_certified_number' => $row['tax_certified_number'] ?? null,
                        'tax_number' => $row['tax_certified_number'] ?? null,
                        'trade_license_start_date' => $row['trade_license_start_date'] ?? null,
                        'trade_license_expired_date' => $row['trade_license_expired_date'] ?? null,
                        'is_active' => isset($row['is_active']) ? (bool) $row['is_active'] : true,
                        'merchant_code' => Merchant::generateMerchantCode(),
                        'status' => 'pending',
                        'add_type' => 'imported',
                    ]);

                    $importedCount++;

                    $this->logMerchantAction($merchant, 'created', null, $merchant->getAttributes(), [
                        'type' => 'import',
                        'event' => 'Merchant imported via template',
                        'message' => 'Merchant profile imported by Admin',
                    ]);
                } catch (\Throwable $e) {
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Successfully imported {$importedCount} merchants"
                    . (count($errors) > 0 ? " with " . count($errors) . " errors" : ""),
                'imported_count' => $importedCount,
                'failed_count' => count($errors),
                'errors' => $errors,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new \RuntimeException("Import failed: " . $e->getMessage(), previous: $e);
        }
    }

    /**
     * Validation rules for optional merchant scopes on update only.
     */
    protected function scopeUpdateValidationRules(): array
    {
        return [
            'scopes' => 'sometimes|nullable|array',
            'scopes.*' => 'nullable|string',
        ];
    }

    /**
     * Normalize scope values and drop unknown entries when configured.
     */
    protected function normalizeScopes(?array $scopes): array
    {
        if ($scopes === null) {
            return [];
        }

        $scopes = array_values(array_filter(
            $scopes,
            static fn ($scope) => is_string($scope) && $scope !== ''
        ));

        $availableKeys = array_keys(config('merchant_scopes.available_scopes', []));
        if (empty($availableKeys)) {
            return $scopes;
        }

        return array_values(array_intersect($scopes, $availableKeys));
    }

    /**
     * Validate incoming data for creation.
     */
    protected function validateStore(Request $request): array
    {
        $businessTypeRule = 'required|string|max:255';
        if (class_exists(\App\Enums\BusinessType::class) && method_exists(\App\Enums\BusinessType::class, 'toArray')) {
            $businessTypeRule = 'required|in:' . implode(',', array_keys(\App\Enums\BusinessType::toArray()));
        }

        return $request->validate([
            'name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'business_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:merchants,email',
                'unique:users,email',
            ],
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'address' => 'nullable|string',
            'business_type' => $businessTypeRule,
            'country_id' => 'required|exists:countries,id',
            'city_id' => 'required|exists:cities,id',
            'trade_license_number' => 'required|string|max:255',
            'tax_certified_number' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'trade_license_start_date' => 'required|date',
            'trade_license_expired_date' => 'required|date|after_or_equal:trade_license_start_date',
            'is_active' => 'sometimes|boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'images' => 'sometimes|array',
            'images.*' => 'file|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:5120',
            'status' => 'sometimes|in:' . implode(',', Merchant::STATUS),
            'scopes' => 'sometimes|array',
            'scopes.*' => 'string|in:' . implode(',', array_keys(config('merchant_scopes.available_scopes', []))),
            'plan_id' => 'sometimes|nullable|exists:plans,id',
        ]);
    }

    /**
     * Validate incoming data for update.
     */
    protected function validateUpdate(Request $request, Merchant $merchant): array
    {
        $businessTypeRule = 'sometimes|string|max:255';
        if (class_exists(\App\Enums\BusinessType::class) && method_exists(\App\Enums\BusinessType::class, 'toArray')) {
            $businessTypeRule = 'sometimes|in:' . implode(',', array_keys(\App\Enums\BusinessType::toArray()));
        }

        return $request->validate(array_merge([
            'name' => 'sometimes|string|max:255',
            'owner_name' => 'sometimes|string|max:255',
            'business_name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('merchants', 'email')->ignore($merchant->id),
                Rule::unique('users', 'email')->ignore($merchant->user_id),
            ],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($merchant->user_id),
            ],
            'address' => 'sometimes|nullable|string',
            'business_type' => $businessTypeRule,
            'country_id' => 'sometimes|exists:countries,id',
            'city_id' => 'sometimes|exists:cities,id',
            'trade_license_number' => 'sometimes|string|max:255',
            'tax_certified_number' => 'sometimes|string|max:255',
            'tax_number' => 'sometimes|nullable|string|max:255',
            'trade_license_start_date' => 'sometimes|date',
            'trade_license_expired_date' => 'sometimes|date|after_or_equal:trade_license_start_date',
            'is_active' => 'sometimes|boolean',
            'logo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'images' => 'sometimes|array',
            'images.*' => 'file|mimes:jpeg,png,jpg,gif,pdf,doc,docx|max:5120',
            'status' => 'sometimes|in:' . implode(',', Merchant::STATUS),
            'plan_id' => 'sometimes|nullable|exists:plans,id',
        ], $this->scopeUpdateValidationRules()));
    }

    /**
     * Store logo file if present.
     */
    protected function storeLogoIfPresent($logo): ?string
    {
        if (!$logo || !method_exists($logo, 'isValid') || !$logo->isValid()) {
            return null;
        }

        $path = $logo->store('merchants/logos', 'public');

        return $path ? 'storage/' . $path : null;
    }

    /**
     * Upload additional attachments.
     */
    protected function uploadAdditionalImages(Request $request, Merchant $merchant): void
    {
        if (!$request->hasFile('images')) {
            return;
        }

        foreach ($request->file('images') as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $storedPath = $file->store('merchants/images', 'public');
            if (!$storedPath) {
                continue;
            }

            $relativePath = 'storage/' . $storedPath;
            $merchant->attachments()->create([
                'url' => $relativePath,
                'type' => $this->checkFileType($relativePath),
            ]);
        }
    }

    /**
     * Create merchant user and return user/password.
     */
    protected function createMerchantUser(Merchant $merchant, array $data): array
    {
        if (empty($data['email'])) {
            return [null, null];
        }

        $existingUser = User::where('email', $data['email'])->first();

        if ($existingUser) {
            if (!$existingUser->merchant_id) {
                $existingUser->update(['merchant_id' => $merchant->id]);
            }

            return [$existingUser, null];
        }

        $plainPassword = Str::random(10);

        // dd($data);
        $user = User::create([
            'name' => $data['owner_name'] ?? $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($plainPassword),
            'merchant_id' => $merchant->id,
            'phone' => $data['phone'] ?? null,
            'country_id' => $data['country_id'] ?? null,
        ]);

        // dd($user);
        return [$user, $plainPassword];
    }

    /**
     * Assign merchant role and permissions.
     */
    protected function assignMerchantRole(Merchant $merchant, User $user): void
    {
        if (!class_exists(Role::class) || !class_exists(Permission::class)) {
            return;
        }

        $roleName = trim('merchant ' . ($merchant->business_name ?? $merchant->name));

        $role = Role::firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'web'],
            ['merchant_id' => $merchant->id]
        );

        $role->merchant_id = $merchant->id;
        $role->save();

        $webPermissions = config('permission.merchant_permissions', []);
        $permissionNames = [];
        
        // Build permission names in the format: "pos.{category}.{permName}" or "sales.{category}.{permName}"
        foreach ($webPermissions as $group => $categories) {
            if (is_array($categories)) {
                // Determine the prefix based on the group (pos_permissions -> "pos", sales_permissions -> "sales")
                $prefix = str_replace('_permissions', '', $group);
                
                foreach ($categories as $category => $permissions) {
                    if (is_array($permissions)) {
                        foreach ($permissions as $permName) {
                            // Build permission name in the same format as PermissionsSeeder: "pos.{category}.{permName}"
                            $permissionNames[] = "{$prefix}.{$category}.{$permName}";
                        }
                    }
                }
            }
        }

        // Remove duplicates and ensure we only have strings
        $permissionNames = array_unique(array_filter($permissionNames, 'is_string'));

        // dd($permissionNames);
        if (!empty($permissionNames)) {
            $permissions = Permission::whereIn('name', $permissionNames)
                ->where('guard_name', 'web')
                ->get();

                // dd($permissions);
            if ($permissions->isNotEmpty()) {
                $role->syncPermissions($permissions);
            }
        }

        $user->syncRoles([$role]);
    }

    /**
     * Log merchant activity.
     */
    protected function logMerchantAction(Merchant $merchant, string $action, $oldValues = null, $newValues = null, array $metadata = []): void
    {
        $actor = Auth::guard('admin')->user() ?? Auth::user();

        $metadata = array_merge([
            'timestamp' => now(),
            'performed_by' => $actor?->name ?? 'System',
        ], $metadata);

        $merchant->logs()->create([
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'user_id' => $actor?->id,
            'user_type' => $actor ? get_class($actor) : null,
        ]);
    }

    /**
     * Lookup merchant names and country data for the given shop IDs.
     */
    public function lookupMerchantCountryInfo(array $shopIds): array
    {
        $normalizedIds = collect($shopIds)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(function ($id) {
                if (is_string($id)) {
                    return trim($id);
                }

                if (is_numeric($id)) {
                    return (string) (int) $id;
                }

                return (string) $id;
            })
            ->filter(fn ($id) => $id !== '')
            ->unique()
            ->values();

        if ($normalizedIds->isEmpty()) {
            return [];
        }

        $merchants = Merchant::query()
            ->select([
                'merchants.id',
                'merchants.business_name',
                'merchants.name',
                'merchants.country_id',
                'countries.name as country_name',
                'countries.short_name as country_short_name',
                'countries.code as country_code',
            ])
            ->leftJoin('countries', 'countries.id', '=', 'merchants.country_id')
            ->whereIn('merchants.id', $normalizedIds->all())
            ->get();

        $results = [];

        foreach ($merchants as $merchant) {
            $merchantId = (string) $merchant->id;
            $merchantName = $this->normalizeLocaleValue($merchant->business_name)
                ?: $this->normalizeLocaleValue($merchant->name)
                ?: 'Merchant #' . $merchantId;

            $countryName = $this->normalizeLocaleValue($merchant->country_name)
                ?: $this->normalizeLocaleValue($merchant->country_short_name)
                ?: $this->normalizeLocaleValue($merchant->country_code);

            $results[$merchantId] = [
                'id' => $merchantId,
                'shop_id' => $merchantId,
                'name' => $merchantName,
                'country_id' => $merchant->country_id,
                'country_name' => $countryName,
                'countryName' => $countryName,
            ];
        }

        foreach ($normalizedIds as $id) {
            $key = (string) $id;
            if (!array_key_exists($key, $results)) {
                $results[$key] = [
                    'id' => $key,
                    'shop_id' => $key,
                    'name' => '',
                    'country_id' => null,
                    'country_name' => '',
                    'countryName' => '',
                ];
            }
        }

        return $results;
    }

    /**
     * Normalize translated or localized values into a plain string.
     */
    protected function normalizeLocaleValue($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '') {
                return '';
            }

            if (Str::startsWith($trimmed, '{') && Str::endsWith($trimmed, '}')) {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $this->normalizeLocaleValue($decoded);
                }
            }

            return $trimmed;
        }

        if (is_array($value)) {
            $preferredLocales = ['en', 'en_US', 'en-Us', 'en-us', 'en-GB', 'ar', 'ar_SA', 'ar-SA'];

            foreach ($preferredLocales as $locale) {
                if (!empty($value[$locale])) {
                    return (string) $value[$locale];
                }
            }

            foreach ($value as $entry) {
                $normalized = $this->normalizeLocaleValue($entry);
                if ($normalized !== '') {
                    return $normalized;
                }
            }

            return '';
        }

        if (is_object($value)) {
            return $this->normalizeLocaleValue((array) $value);
        }

        return (string) $value;
    }

    /**
     * Resolve the authenticated actor responsible for a rejection.
     *
     * @throws \RuntimeException
     */
    protected function resolveRejectionActorId(): string
    {
        $guards = ['admin-api', 'admin', null];

        foreach ($guards as $guard) {
            try {
                $authGuard = $guard ? Auth::guard($guard) : Auth::guard();
            } catch (\Throwable $e) {
                continue;
            }

            if ($authGuard->check()) {
                $id = $authGuard->id();
                if (!empty($id)) {
                    return $id;
                }
            }
        }

        throw new \RuntimeException('Unable to determine the rejecting user.');
    }

    /**
     * Delete stored file if exists.
     */
    protected function deleteStoredFile(?string $path): void
    {
        if (!$path) {
            return;
        }

        $relativePath = str_starts_with($path, 'storage/') ? substr($path, 8) : $path;

        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
            return;
        }

        if (Storage::exists($path)) {
            Storage::delete($path);
        }
    }

    /**
     * Send welcome email if mailable exists.
     */
    protected function sendWelcomeEmail(?User $user, ?string $password, Merchant $merchant): void
    {
        if (!$user || !$password || !class_exists(WelcomeMail::class)) {
            return;
        }

        try {
            Mail::to($user->email)->send(new WelcomeMail($user, $password, $merchant));
        } catch (\Throwable $e) {
            Log::warning('Failed to send welcome email: ' . $e->getMessage());
        }
    }

    /**
     * Send approval email if mailable exists.
     */
    protected function sendApprovalEmail(Merchant $merchant): void
    {
        if (!class_exists(MerchantApprovalMail::class) || !$merchant->user) {
            return;
        }

        try {
            Mail::to($merchant->email)->send(new MerchantApprovalMail($merchant->user, $merchant->user->password, $merchant));
        } catch (\Throwable $e) {
            Log::warning('Failed to send approval email: ' . $e->getMessage());
        }
    }

    /**
     * Send rejection email if mailable exists.
     */
    protected function sendRejectionEmail(Merchant $merchant, string $reason): void
    {
        if (!class_exists(MerchantRejectionMail::class)) {
            return;
        }

        try {
            Mail::to($merchant->email)->send(new MerchantRejectionMail($merchant, $reason));
        } catch (\Throwable $e) {
            Log::warning('Failed to send rejection email: ' . $e->getMessage());
        }
    }
}


