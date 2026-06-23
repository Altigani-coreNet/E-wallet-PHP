<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserGroup;
use App\Models\User;
use App\Models\Merchant;
use App\Models\Branch;
use App\Traits\ApiResponse;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AdminUserGroupController extends Controller
{
    use ApiResponse;

    /**
     * Get all user groups with pagination, search, and filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = UserGroup::query()
                ->with(['merchant.country', 'branch', 'users'])
                ->withCount(['users']);

            // Search
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('group_id', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Status filter
            $status = $request->input('status');
            if ($status && $status !== '' && $status !== null) {
                $status = $request->input('status') === 'active' ? 1 : 0;
                $query->where('is_active', $status);
            }

            $merchantId = $request->input('merchant_id');
            // Merchant filter
            if ($merchantId && $merchantId !== '' && $merchantId !== null) {
                $query->where('merchant_id', $merchantId);
            }

            $branchId = $request->input('branch_id');
            // Branch filter
            if ($branchId && $branchId !== '' && $branchId !== null) {
                $query->where('branch_id', $branchId);
            }

            // Country filter
            $countryId = $request->input('country_id');
            if ($countryId && $countryId !== '' && $countryId !== null) {
                $query->where('country_id', $countryId);
            }

            // Date range filter
            $dateFrom = $request->input('date_from');
            if ($dateFrom && $dateFrom !== '' && $dateFrom !== null) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }

            $dateTo = $request->input('date_to');
            if ($dateTo && $dateTo !== '' && $dateTo !== null) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            // Pagination
            $perPage = $request->input('per_page', 15);
            $userGroups = $query->latest()->paginate($perPage);

            return $this->SuccessMessage($userGroups);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch user groups: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get user group details
     */
    public function show($id): JsonResponse
    {
        try {
            $userGroup = UserGroup::with(['merchant.country', 'branch', 'users'])
                ->withCount(['users'])
                ->findOrFail($id);

            return $this->SuccessMessage($userGroup);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch user group: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Create a new user group
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'merchant_id' => 'required|exists:merchants,id',
                'branch_id' => 'nullable|exists:branches,id',
                'description' => 'nullable|string',
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'exists:users,id',
            ]);

            DB::beginTransaction();

            // Get country from merchant
            $merchant = Merchant::findOrFail($validated['merchant_id']);

            $userGroup = UserGroup::create([
                'name' => $validated['name'],
                'group_id' => UserGroup::generateGroupId(),
                'merchant_id' => $validated['merchant_id'],
                'country_id' => $merchant->country_id,
                'branch_id' => $validated['branch_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_active' => true,
            ]);

            // Attach users to the group
            $userGroup->users()->attach($validated['user_ids']);

            DB::commit();

            return $this->SuccessMessage([
                'user_group' => $userGroup->load(['merchant', 'branch', 'users']),
                'message' => 'User group created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->ErrorMessage('Failed to create user group: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update user group
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $userGroup = UserGroup::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'merchant_id' => 'required|exists:merchants,id',
                'branch_id' => 'nullable|exists:branches,id',
                'description' => 'nullable|string',
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'exists:users,id',
            ]);

            DB::beginTransaction();

            // Get country from merchant
            $merchant = Merchant::findOrFail($validated['merchant_id']);

            $userGroup->update([
                'name' => $validated['name'],
                'merchant_id' => $validated['merchant_id'],
                'country_id' => $merchant->country_id,
                'branch_id' => $validated['branch_id'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);

            // Sync users
            $userGroup->users()->sync($validated['user_ids']);

            DB::commit();

            return $this->SuccessMessage([
                'user_group' => $userGroup->fresh()->load(['merchant', 'branch', 'users']),
                'message' => 'User group updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->ErrorMessage('Failed to update user group: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Delete user group
     */
    public function destroy($id): JsonResponse
    {
        try {
            $userGroup = UserGroup::findOrFail($id);
            $userGroup->delete();

            return $this->SuccessMessage(['message' => 'User group deleted successfully']);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete user group: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get user group statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_groups' => UserGroup::count(),
                'active_groups' => UserGroup::where('is_active', true)->count(),
                'inactive_groups' => UserGroup::where('is_active', false)->count(),
                'groups_this_month' => UserGroup::whereMonth('created_at', now()->month)->count(),
                'groups_today' => UserGroup::whereDate('created_at', now())->count(),
                'total_users_in_groups' => DB::table('user_user_group')->distinct('user_id')->count(),
            ];

            return $this->SuccessMessage($stats);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch statistics: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export user groups
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $query = UserGroup::query()->with(['merchant.country', 'branch', 'users']);

            // Apply same filters as index
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('group_id', 'like', "%{$search}%");
                });
            }

            if ($request->has('status') && $request->input('status') !== '') {
                $status = $request->input('status') === 'active' ? 1 : 0;
                $query->where('is_active', $status);
            }

            if ($merchantId = $request->input('merchant_id')) {
                $query->where('merchant_id', $merchantId);
            }

            $userGroups = $query->get();

            $exportData = $userGroups->map(function ($group) {
                return [
                    'ID' => $group->id,
                    'Group ID' => $group->group_id,
                    'Name' => $group->name,
                    'Description' => $group->description ?? '',
                    'Merchant' => $group->merchant->business_name ?? 'N/A',
                    'Branch' => $group->branch->name ?? 'N/A',
                    'Country' => $group->merchant->country->name ?? 'N/A',
                    'Users Count' => $group->users->count(),
                    'Status' => $group->is_active ? 'Active' : 'Inactive',
                    'Created At' => $group->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return $this->SuccessMessage([
                'data' => $exportData,
                'filename' => 'user_groups_export_' . date('Y-m-d_H-i-s') . '.csv'
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to export user groups: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk delete user groups
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:user_groups,id'
            ]);

            $count = UserGroup::whereIn('id', $validated['ids'])->delete();

            return $this->SuccessMessage([
                'message' => "{$count} user group(s) deleted successfully",
                'count' => $count
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete user groups: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Activate user group
     */
    public function activate($id): JsonResponse
    {
        try {
            $userGroup = UserGroup::findOrFail($id);
            $userGroup->update(['is_active' => true]);

            return $this->SuccessMessage([
                'message' => 'User group activated successfully',
                'user_group' => $userGroup
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to activate user group: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Deactivate user group
     */
    public function deactivate($id): JsonResponse
    {
        try {
            $userGroup = UserGroup::findOrFail($id);
            $userGroup->update(['is_active' => false]);

            return $this->SuccessMessage([
                'message' => 'User group deactivated successfully',
                'user_group' => $userGroup
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to deactivate user group: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get users for a specific merchant (for dropdown/selection)
     */
    public function getMerchantUsers(Request $request): JsonResponse
    {
        try {
            $merchantId = $request->input('merchant_id');
            
            if (!$merchantId) {
                return $this->ErrorMessage('Merchant ID is required', null, 400);
            }

            $users = User::where('merchant_id', $merchantId)
                // ->where('status', 'active')
                ->select('id', 'name', 'email')
                ->get();

            return $this->SuccessMessage($users);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch merchant users: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export template for bulk import (Excel with multiple sheets)
     */
    public function exportTemplate()
    {
        try {
            $spreadsheet = new Spreadsheet();
            
            // Sheet 1: User Groups Template
            $groupsSheet = $spreadsheet->getActiveSheet();
            $groupsSheet->setTitle('User Groups');
            
            // Set headers with instructions
            $groupsSheet->setCellValue('A1', 'Name *');
            $groupsSheet->setCellValue('B1', 'Description');
            $groupsSheet->setCellValue('C1', 'Merchant * (Select from dropdown)');
            $groupsSheet->setCellValue('D1', 'Branch (Optional - Select from dropdown)');
            $groupsSheet->setCellValue('E1', 'User IDs * (Comma-separated)');
            $groupsSheet->setCellValue('F1', 'Status * (Select from dropdown)');
            
            // Style header row
            $groupsSheet->getStyle('A1:F1')->getFont()->setBold(true);
            $groupsSheet->getStyle('A1:F1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF4472C4');
            $groupsSheet->getStyle('A1:F1')->getFont()->getColor()->setRGB('FFFFFF');
            
            // Sheet 2: Merchants List
            $merchantsSheet = $spreadsheet->createSheet();
            $merchantsSheet->setTitle('Merchants');
            
            $merchantsSheet->setCellValue('A1', 'ID');
            $merchantsSheet->setCellValue('B1', 'Merchant Name');
            $merchantsSheet->setCellValue('C1', 'Email');
            
            $merchants = Merchant::select('id', 'business_name', 'name', 'email')
                ->where('is_active', true)
                ->orderBy('business_name')
                ->get();
            
            $row = 2;
            foreach ($merchants as $merchant) {
                $merchantsSheet->setCellValue('A' . $row, $merchant->id);
                $merchantsSheet->setCellValue('B' . $row, $merchant->business_name ?: $merchant->name);
                $merchantsSheet->setCellValue('C' . $row, $merchant->email);
                $row++;
            }
            
            $merchantsSheet->getStyle('A1:C1')->getFont()->setBold(true);
            $merchantsSheet->getStyle('A1:C1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            
            // Sheet 3: Branches List
            $branchesSheet = $spreadsheet->createSheet();
            $branchesSheet->setTitle('Branches');
            
            $branchesSheet->setCellValue('A1', 'ID');
            $branchesSheet->setCellValue('B1', 'Branch Name');
            $branchesSheet->setCellValue('C1', 'Merchant');
            
            $branches = Branch::select('id', 'name', 'merchant_id')
                ->with('merchant:id,business_name,name')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
            
            $row = 2;
            foreach ($branches as $branch) {
                $branchesSheet->setCellValue('A' . $row, $branch->id);
                $branchesSheet->setCellValue('B' . $row, $branch->name);
                $branchesSheet->setCellValue('C' . $row, $branch->merchant ? ($branch->merchant->business_name ?: $branch->merchant->name) : 'N/A');
                $row++;
            }
            
            $branchesSheet->getStyle('A1:C1')->getFont()->setBold(true);
            $branchesSheet->getStyle('A1:C1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            
            // Sheet 4: Users List
            $usersSheet = $spreadsheet->createSheet();
            $usersSheet->setTitle('Users');
            
            $usersSheet->setCellValue('A1', 'ID');
            $usersSheet->setCellValue('B1', 'User Name');
            $usersSheet->setCellValue('C1', 'Email');
            $usersSheet->setCellValue('D1', 'Merchant');
            
            $users = User::select('id', 'name', 'email', 'merchant_id')
                ->with('merchant:id,business_name,name')
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
            
            $row = 2;
            foreach ($users as $user) {
                $usersSheet->setCellValue('A' . $row, $user->id);
                $usersSheet->setCellValue('B' . $row, $user->name);
                $usersSheet->setCellValue('C' . $row, $user->email);
                $usersSheet->setCellValue('D' . $row, $user->merchant ? ($user->merchant->business_name ?: $user->merchant->name) : 'N/A');
                $row++;
            }
            
            $usersSheet->getStyle('A1:D1')->getFont()->setBold(true);
            $usersSheet->getStyle('A1:D1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            
            // Create dropdown validations
            $merchantCount = $merchants->count();
            $branchCount = $branches->count();
            
            // Merchant dropdown (Column C)
            if ($merchantCount > 0) {
                $merchantValidation = $groupsSheet->getCell('C2')->getDataValidation();
                $merchantValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $merchantValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $merchantValidation->setAllowBlank(false);
                $merchantValidation->setShowInputMessage(true);
                $merchantValidation->setShowErrorMessage(true);
                $merchantValidation->setShowDropDown(true);
                $merchantValidation->setErrorTitle('Invalid Merchant');
                $merchantValidation->setError('Please select a merchant from the dropdown list');
                $merchantValidation->setPromptTitle('Select Merchant');
                $merchantValidation->setPrompt('Choose a merchant from the Merchants sheet');
                $merchantValidation->setFormula1('Merchants!$B$2:$B$' . ($merchantCount + 1));
                
                for ($i = 2; $i <= 1000; $i++) {
                    $groupsSheet->getCell('C' . $i)->setDataValidation(clone $merchantValidation);
                }
            }
            
            // Branch dropdown (Column D)
            if ($branchCount > 0) {
                $branchValidation = $groupsSheet->getCell('D2')->getDataValidation();
                $branchValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $branchValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $branchValidation->setAllowBlank(true);
                $branchValidation->setShowInputMessage(true);
                $branchValidation->setShowErrorMessage(true);
                $branchValidation->setShowDropDown(true);
                $branchValidation->setErrorTitle('Invalid Branch');
                $branchValidation->setError('Please select a branch from the dropdown list');
                $branchValidation->setPromptTitle('Select Branch');
                $branchValidation->setPrompt('Choose a branch from the Branches sheet (Optional)');
                $branchValidation->setFormula1('Branches!$B$2:$B$' . ($branchCount + 1));
                
                for ($i = 2; $i <= 1000; $i++) {
                    $groupsSheet->getCell('D' . $i)->setDataValidation(clone $branchValidation);
                }
            }
            
            // Status dropdown (Column F)
            $statusValidation = $groupsSheet->getCell('F2')->getDataValidation();
            $statusValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $statusValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $statusValidation->setAllowBlank(false);
            $statusValidation->setShowInputMessage(true);
            $statusValidation->setShowErrorMessage(true);
            $statusValidation->setShowDropDown(true);
            $statusValidation->setErrorTitle('Invalid Status');
            $statusValidation->setError('Please select: active or inactive');
            $statusValidation->setPromptTitle('Select Status');
            $statusValidation->setPrompt('Choose group status');
            $statusValidation->setFormula1('"active,inactive"');
            
            for ($i = 2; $i <= 1000; $i++) {
                $groupsSheet->getCell('F' . $i)->setDataValidation(clone $statusValidation);
            }
            
            // Auto-size columns
            foreach (['User Groups', 'Merchants', 'Branches', 'Users'] as $sheetName) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                if ($sheet) {
                    foreach (range('A', $sheet->getHighestColumn()) as $col) {
                        $sheet->getColumnDimension($col)->setAutoSize(true);
                    }
                }
            }
            
            // Set active sheet back to User Groups
            $spreadsheet->setActiveSheetIndex(0);
            
            // Save to temp file
            $filename = 'user_groups_import_template_' . date('Y-m-d_H-i-s') . '.xlsx';
            $filepath = storage_path('app/temp/' . $filename);
            
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);
            
            return response()->download($filepath, $filename)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Failed to generate template: ' . $e->getMessage());
            return $this->ErrorMessage('Failed to generate template: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Preview import data
     */
    public function importPreview(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240'
            ]);

            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            
            $groupsSheet = $spreadsheet->getSheetByName('User Groups');
            if (!$groupsSheet) {
                return $this->ErrorMessage('User Groups sheet not found in the Excel file', null, 422);
            }

            $groups = [];
            $validCount = 0;
            $invalidCount = 0;
            $highestRow = $groupsSheet->getHighestRow();

            for ($row = 2; $row <= $highestRow; $row++) {
                $name = trim($groupsSheet->getCell('A' . $row)->getValue());
                $description = trim($groupsSheet->getCell('B' . $row)->getValue());
                $merchantName = trim($groupsSheet->getCell('C' . $row)->getValue());
                $branchName = trim($groupsSheet->getCell('D' . $row)->getValue());
                $userIds = trim($groupsSheet->getCell('E' . $row)->getValue());
                $status = trim($groupsSheet->getCell('F' . $row)->getValue());

                // Skip empty rows
                if (empty($name) && empty($merchantName)) {
                    continue;
                }

                $validationErrors = [];
                $merchantId = null;
                $branchId = null;

                // Validate name
                if (empty($name)) {
                    $validationErrors[] = 'Name is required';
                }

                // Lookup merchant
                if (empty($merchantName)) {
                    $validationErrors[] = 'Merchant is required';
                } else {
                    $merchant = Merchant::where(function($q) use ($merchantName) {
                        $q->where('business_name', $merchantName)
                          ->orWhere('name', $merchantName);
                    })->first();

                    if (!$merchant) {
                        $validationErrors[] = "Merchant '{$merchantName}' not found";
                    } else {
                        $merchantId = $merchant->id;
                    }
                }

                // Lookup branch (optional)
                if (!empty($branchName) && $merchantId) {
                    $branch = Branch::where('name', $branchName)
                        ->where('merchant_id', $merchantId)
                        ->first();

                    if ($branch) {
                        $branchId = $branch->id;
                    } else {
                        $validationErrors[] = "Branch '{$branchName}' not found for this merchant";
                    }
                }

                // Validate user IDs
                if (empty($userIds)) {
                    $validationErrors[] = 'At least one user ID is required';
                } else {
                    $userIdArray = array_map('trim', explode(',', $userIds));
                    $userIdArray = array_filter($userIdArray);
                    
                    if (empty($userIdArray)) {
                        $validationErrors[] = 'Valid user IDs required';
                    }
                }

                // Validate status
                if (empty($status)) {
                    $status = 'active';
                } elseif (!in_array($status, ['active', 'inactive'])) {
                    $validationErrors[] = "Status must be 'active' or 'inactive'";
                }

                $isValid = empty($validationErrors);
                if ($isValid) {
                    $validCount++;
                } else {
                    $invalidCount++;
                }

                $groups[] = [
                    'row' => $row,
                    'name' => $name,
                    'description' => $description,
                    'merchant' => $merchantName,
                    'merchant_id' => $merchantId,
                    'branch' => $branchName ?: 'N/A',
                    'branch_id' => $branchId,
                    'user_ids' => $userIds,
                    'status' => $status,
                    'is_valid' => $isValid,
                    'errors' => $validationErrors
                ];
            }

            $canImport = $validCount > 0;
            $message = $canImport 
                ? "✅ {$validCount} of " . count($groups) . " user groups are valid and ready to import!"
                : "⚠️ All user groups have validation errors. Please fix them and try again.";

            if ($invalidCount > 0 && $validCount > 0) {
                $message = "⚠️ {$invalidCount} of " . count($groups) . " user groups have validation errors. Only valid groups will be imported.";
            }

            return $this->SuccessMessage([
                'message' => $message,
                'summary' => [
                    'total_rows' => count($groups),
                    'valid_count' => $validCount,
                    'invalid_count' => $invalidCount,
                    'can_import' => $canImport,
                ],
                'groups' => $groups
            ]);

        } catch (\Exception $e) {
            Log::error('Import preview failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->ErrorMessage('Failed to preview import: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Import user groups from Excel
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240'
            ]);

            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            
            $groupsSheet = $spreadsheet->getSheetByName('User Groups');
            if (!$groupsSheet) {
                return $this->ErrorMessage('User Groups sheet not found in the Excel file', null, 422);
            }

            $imported = 0;
            $skipped = 0;
            $errors = [];
            $highestRow = $groupsSheet->getHighestRow();

            for ($row = 2; $row <= $highestRow; $row++) {
                $name = trim($groupsSheet->getCell('A' . $row)->getValue());
                $description = trim($groupsSheet->getCell('B' . $row)->getValue());
                $merchantName = trim($groupsSheet->getCell('C' . $row)->getValue());
                $branchName = trim($groupsSheet->getCell('D' . $row)->getValue());
                $userIds = trim($groupsSheet->getCell('E' . $row)->getValue());
                $status = trim($groupsSheet->getCell('F' . $row)->getValue());

                // Skip empty rows
                if (empty($name) && empty($merchantName)) {
                    continue;
                }

                try {
                    // Validate required fields
                    if (empty($name) || empty($merchantName)) {
                        $skipped++;
                        $errors[] = "Row {$row}: Missing required fields";
                        continue;
                    }

                    // Lookup merchant
                    $merchant = Merchant::where(function($q) use ($merchantName) {
                        $q->where('business_name', $merchantName)
                          ->orWhere('name', $merchantName);
                    })->first();

                    if (!$merchant) {
                        $skipped++;
                        $errors[] = "Row {$row}: Merchant '{$merchantName}' not found";
                        continue;
                    }

                    $merchantId = $merchant->id;
                    $branchId = null;

                    // Lookup branch if provided
                    if (!empty($branchName)) {
                        $branch = Branch::where('name', $branchName)
                            ->where('merchant_id', $merchantId)
                            ->first();

                        if ($branch) {
                            $branchId = $branch->id;
                        }
                    }

                    // Parse user IDs
                    $userIdArray = array_map('trim', explode(',', $userIds));
                    $userIdArray = array_filter($userIdArray);

                    if (empty($userIdArray)) {
                        $skipped++;
                        $errors[] = "Row {$row}: At least one user ID is required";
                        continue;
                    }

                    DB::beginTransaction();

                    // Create user group
                    $userGroup = UserGroup::create([
                        'name' => $name,
                        'group_id' => UserGroup::generateGroupId(),
                        'merchant_id' => $merchantId,
                        'country_id' => $merchant->country_id,
                        'branch_id' => $branchId,
                        'description' => $description,
                        'is_active' => (!empty($status) && $status === 'active') ? true : false,
                    ]);

                    // Attach users
                    $userGroup->users()->attach($userIdArray);

                    DB::commit();
                    $imported++;

                } catch (\Exception $e) {
                    DB::rollBack();
                    $skipped++;
                    $errors[] = "Row {$row}: " . $e->getMessage();
                    Log::error("Failed to import user group at row {$row}: " . $e->getMessage());
                }
            }

            $message = "{$imported} user group(s) imported successfully";
            if ($skipped > 0) {
                $message .= ", {$skipped} row(s) skipped due to errors";
            }

            return $this->SuccessMessage([
                'message' => $message,
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('Import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->ErrorMessage('Failed to import user groups: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get user groups for select dropdown (used in terminal group form)
     */
    public function select(Request $request): JsonResponse
    {
        try {
            $query = UserGroup::query();

            // Filter by merchant if provided
            if ($merchantId = $request->input('merchant_id')) {
                $query->where('merchant_id', $merchantId);
            }

            // Search
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('group_id', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Only active user groups
            $query->where('is_active', true);

            $userGroups = $query->select('id', 'name', 'group_id', 'description', 'is_active')->get();

            return $this->SuccessMessage($userGroups);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch user groups: ' . $e->getMessage(), null, 500);
        }
    }
}

