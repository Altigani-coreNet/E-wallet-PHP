<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UsersOtp;
use App\Models\Branch;
use App\Models\Role;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Mail\NewUserCredentialsMail;
use App\Services\PasswordResetLinkService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * Get all users for current merchant
     */
    public function index(Request $request)
    {
        try {
            $authUser = $request->user();
            // dd($authUser);
            $query = User::where('merchant_id', $authUser->merchant_id)
                ->with(['roles', 'branch']);

            // Search functionality
            if ($request->has('search') && !empty($request->search) && $request->search !== null) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('email', 'like', "%$search%")
                      ->orWhere('phone', 'like', "%$search%")
                      ->orWhere('name', 'like', "%$search%")
                      ->orWhere('id', '=', $search); // Exact match for UUID
                });
            }

            // Status filter
            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }

            // Module filter - filter by user's module directly
            if ($request->has('module') && $request->module) {
                $query->where('module', $request->module);
            }

            // Filter by date range
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Filter by branch_id
            if ($request->has('branch_id') && !empty($request->branch_id)) {
                $query->where('branch_id', $request->branch_id);
            }

            // Filter by role_id - filter users that have this role
            if ($request->has('role_id') && !empty($request->role_id)) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('roles.id', $request->role_id);
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            $users = $query->paginate($request->per_page ?? 15);

            return $this->SuccessMessage($users);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch users: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Create new user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            // 'password' => 'required|string|min:8|confirmed',
            'branch_id' => 'nullable|exists:branches,id',
            'module' => 'nullable|in:pos,sales',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'user_type' => 'nullable|in:admin,supervisor,cashier',
        ]);

        if ($validator->fails()) {
            return $this->ErrorMessage($validator->errors(), null, 422);
        }

        try {
            $authUser = $request->user();
            
            // Get merchant to retrieve country_id
            $merchant = \App\Models\Merchant::findOrFail($authUser->merchant_id);
            
            // Enforce plan scope & limit for users
            // Total users already created for this merchant
            $currentCount = User::where('merchant_id', $authUser->merchant_id)->count();

            // If user is not allowed to create another user, return 406
            if (!User::merchantCanCreateUser($authUser->merchant_id, $currentCount)) {
                return $this->ErrorMessage(
                    'User limit reached for your current plan.',
                    'PLAN_USERS_LIMIT_REACHED',
                    406
                );
            }
            
            // Generate secure random password if not provided
            $password = $request->password ?? $this->generateSecurePassword(14);
            
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($password),
                'merchant_id' => $authUser->merchant_id,
                'country_id' => $merchant->country_id,
                'branch_id' => $request->branch_id,
                'module' => $request->module,
                'status' => 1,
                // Map user_type from request to underlying "type" column
                'type' => $request->input('user_type'),
            ]);

            // Assign roles if provided
            if ($request->has('roles') && is_array($request->roles)) {
                // Get roles from 'web' guard
                $roles = \Spatie\Permission\Models\Role::whereIn('id', $request->roles)
                    ->where('guard_name', 'web')
                    ->get();
                
                // Detach all existing roles first
                $user->roles()->detach();
                
                // Attach new roles
                foreach ($roles as $role) {
                    $user->roles()->attach($role->id, ['model_type' => User::class]);
                }
            }

            // Send credentials email if password was auto-generated
            $emailSent = false;
            if (!$request->password && !empty($user->email)) {
                try {
                    Mail::to($user->email)->send(new NewUserCredentialsMail($user, $password));
                    $emailSent = true;
                    Log::info('Credentials email sent to: ' . $user->email);
                } catch (\Exception $e) {
                    Log::error('Failed to send credentials email: ' . $e->getMessage());
                    // Don't fail user creation if email fails
                }
            }

            return $this->SuccessMessage([
                'message' => 'User created successfully' . ($emailSent ? ' and credentials email sent' : ''),
                'user' => $user->load('roles'),
                'email_sent' => $emailSent
            ], 201);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create user: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get single user
     */
    public function show(Request $request, $id)
    {
        try {
            $authUser = $request->user();
            
            $user = User::where('id', $id)
                ->where('merchant_id', $authUser->merchant_id)
                ->with(['roles', 'branch', 'userGroups'])
                ->firstOrFail();

            return $this->SuccessMessage($user);
        } catch (\Exception $e) {
            return $this->ErrorMessage('User not found', null, 404);
        }
    }

    private function generateSecurePassword(int $length = 14): string
    {
        $length = max(8, $length);

        $lower = 'abcdefghijklmnopqrstuvwxyz';
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $digits = '0123456789';
        $symbols = '!@#$%&*()-_=+?';
        $allChars = $lower . $upper . $digits . $symbols;

        $passwordChars = [
            $lower[random_int(0, strlen($lower) - 1)],
            $upper[random_int(0, strlen($upper) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        for ($i = count($passwordChars); $i < $length; $i++) {
            $passwordChars[] = $allChars[random_int(0, strlen($allChars) - 1)];
        }

        for ($i = count($passwordChars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$passwordChars[$i], $passwordChars[$j]] = [$passwordChars[$j], $passwordChars[$i]];
        }

        return implode('', $passwordChars);
    }

    /**
     * Update user
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'phone' => 'sometimes|required|string|unique:users,phone,' . $id,
            'branch_id' => 'nullable|exists:branches,id',
            'module' => 'nullable|in:pos,sales',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
            'status' => 'nullable|in:0,1',
            'user_type' => 'nullable|in:admin,supervisor,cashier',
        ]);

        if ($validator->fails()) {
            return $this->ErrorMessage($validator->errors(), null, 422);
        }

        try {
            $authUser = $request->user();
            
            $user = User::where('id', $id)
                ->where('merchant_id', $authUser->merchant_id)
                ->firstOrFail();

            // Build update array with only provided fields
            $updateData = [];
            
            if ($request->filled('name')) {
                $updateData['name'] = $request->name;
            }
            if ($request->filled('email')) {
                $updateData['email'] = $request->email;
            }
            if ($request->filled('phone')) {
                $updateData['phone'] = $request->phone;
            }
            if ($request->has('branch_id')) {
                $updateData['branch_id'] = $request->branch_id;
            }
            if ($request->has('module')) {
                $updateData['module'] = $request->module;
            }
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            if ($request->has('user_type')) {
                $updateData['type'] = $request->input('user_type');
            }
            
            $user->update($updateData);

            // Update password if provided
            if ($request->filled('password')) {
                $user->update([
                    'password' => Hash::make($request->password)
                ]);
            }

            // Sync roles if provided
            if ($request->has('roles') && is_array($request->roles)) {
                // Get roles from 'web' guard
                $roles = \Spatie\Permission\Models\Role::whereIn('id', $request->roles)
                    ->where('guard_name', 'web')
                    ->get();
                
                // Detach all existing roles first
                $user->roles()->detach();
                
                // Attach new roles
                foreach ($roles as $role) {
                    $user->roles()->attach($role->id, ['model_type' => User::class]);
                }
            }

            return $this->SuccessMessage([
                'message' => 'User updated successfully',
                'user' => $user->fresh()->load('roles')
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update user: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Delete user
     */
    public function destroy(Request $request, $id)
    {
        try {
            $authUser = $request->user();
            
            $user = User::where('id', $id)
                ->where('merchant_id', $authUser->merchant_id)
                ->firstOrFail();

            // Prevent deleting yourself
            if ($user->id === $authUser->id) {
                return $this->ErrorMessage('Cannot delete your own account', null, 403);
            }

            $user->delete();

            return $this->SuccessMessage('User deleted successfully');

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete user: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get users for select dropdown
     */
    public function select(Request $request)
    {
        try {
            $authUser = $request->user();
            
            $query = User::where('merchant_id', $authUser->merchant_id)
                ->where('status', 1)
                ->select('id', 'name', 'email');

            if ($request->has('search') && !empty($request->search)) {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'LIKE', "%{$request->search}%")
                      ->orWhere('email', 'LIKE', "%{$request->search}%");
                });
            }

            $users = $query->limit(20)
                ->get()
                ->map(function($user) {
                    return [
                        'id' => $user->id,
                        'text' => $user->name . ' (' . $user->email . ')',
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                });

            return $this->SuccessMessage($users);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch users: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Delete test user by email (for testing purposes)
     */
    public function deleteTestUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->ErrorMessage($validator->errors(), null, 422);
        }

        try {
            $email = $request->email;
            
            $user = User::where('email', $email)->first();

            if (!$user) {
                return $this->ErrorMessage("User with email '{$email}' not found", null, 404);
            }

            // Store user info for response
            $userInfo = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ];

            // Delete user
            $user->delete();

            return $this->SuccessMessage([
                'message' => 'Test user deleted successfully',
                'deleted_user' => $userInfo
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete test user: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export users to Excel
     */
    public function export(Request $request)
    {
        try {
            $authUser = $request->user();
            
            $query = User::where('merchant_id', $authUser->merchant_id)
                ->with(['roles', 'branch']);

            // Apply filters
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('email', 'like', "%$search%")
                      ->orWhere('phone', 'like', "%$search%")
                      ->orWhere('name', 'like', "%$search%");
                });
            }

            if ($request->has('status') && $request->status !== null) {
                $query->where('status', $request->status);
            }

            // Apply date filters
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Filter by branch_id
            if ($request->has('branch_id') && !empty($request->branch_id)) {
                $query->where('branch_id', $request->branch_id);
            }

            // Filter by role_id - filter users that have this role
            if ($request->has('role_id') && !empty($request->role_id)) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('roles.id', $request->role_id);
                });
            }

            $users = $query->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Users');

            // Headers
            $headers = ['ID', 'Name', 'Email', 'Phone', 'Branch', 'Roles', 'Module', 'Status', 'Created At'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '1', $header);
                $col++;
            }

            // Style headers
            $sheet->getStyle('A1:I1')->getFont()->setBold(true);
            $sheet->getStyle('A1:I1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF4472C4');
            $sheet->getStyle('A1:I1')->getFont()->getColor()->setRGB('FFFFFF');

            // Data
            $row = 2;
            foreach ($users as $user) {
                $sheet->setCellValue('A' . $row, $user->id);
                $sheet->setCellValue('B' . $row, $user->name);
                $sheet->setCellValue('C' . $row, $user->email);
                $sheet->setCellValue('D' . $row, $user->phone);
                $sheet->setCellValue('E' . $row, $user->branch ? $user->branch->name : 'N/A');
                $sheet->setCellValue('F' . $row, $user->roles->pluck('name')->join(', ') ?: 'N/A');
                $sheet->setCellValue('G' . $row, $user->module ?? 'N/A');
                $sheet->setCellValue('H' . $row, $user->status ? 'Active' : 'Inactive');
                $sheet->setCellValue('I' . $row, $user->created_at->format('Y-m-d H:i:s'));
                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'I') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $filename = 'users_export_' . date('Y-m-d_H-i-s') . '.xlsx';
            $filepath = storage_path('app/temp/' . $filename);
            
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            return response()->download($filepath, $filename)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Export failed: ' . $e->getMessage());
            return $this->ErrorMessage('Failed to export users: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export template for bulk import (Excel with multiple sheets)
     */
    public function exportTemplate(Request $request)
    {
        try {
            $authUser = $request->user();
            
            $spreadsheet = new Spreadsheet();
            
            // Sheet 1: Users Template
            $usersSheet = $spreadsheet->getActiveSheet();
            $usersSheet->setTitle('Users');
            
            // Set headers
            $usersSheet->setCellValue('A1', 'Name *');
            $usersSheet->setCellValue('B1', 'Email *');
            $usersSheet->setCellValue('C1', 'Phone *');
            $usersSheet->setCellValue('D1', 'Branch (Optional - Select from dropdown)');
            $usersSheet->setCellValue('E1', 'Role (Optional - Select from dropdown)');
            $usersSheet->setCellValue('F1', 'Module (Optional - pos or sales)');
            $usersSheet->setCellValue('G1', 'Status * (1 = Active, 0 = Inactive)');
            
            // Style header row
            $usersSheet->getStyle('A1:G1')->getFont()->setBold(true);
            $usersSheet->getStyle('A1:G1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF4472C4');
            $usersSheet->getStyle('A1:G1')->getFont()->getColor()->setRGB('FFFFFF');
            
            // Sheet 2: Branches List (only for current merchant)
            $branchesSheet = $spreadsheet->createSheet();
            $branchesSheet->setTitle('Branches');
            
            $branchesSheet->setCellValue('A1', 'ID');
            $branchesSheet->setCellValue('B1', 'Branch Name');
            
            $branches = Branch::where('merchant_id', $authUser->merchant_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
            
            $row = 2;
            foreach ($branches as $branch) {
                $branchesSheet->setCellValue('A' . $row, $branch->id);
                $branchesSheet->setCellValue('B' . $row, $branch->name);
                $row++;
            }
            
            $branchesSheet->getStyle('A1:B1')->getFont()->setBold(true);
            $branchesSheet->getStyle('A1:B1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            
            // Sheet 3: Roles List
            $rolesSheet = $spreadsheet->createSheet();
            $rolesSheet->setTitle('Roles');
            
            $rolesSheet->setCellValue('A1', 'ID');
            $rolesSheet->setCellValue('B1', 'Role Name');
            
            $roles = Role::where('guard_name', 'web')
                ->orderBy('name')
                ->get();
            
            $row = 2;
            foreach ($roles as $role) {
                $rolesSheet->setCellValue('A' . $row, $role->id);
                $rolesSheet->setCellValue('B' . $row, $role->name);
                $row++;
            }
            
            $rolesSheet->getStyle('A1:B1')->getFont()->setBold(true);
            $rolesSheet->getStyle('A1:B1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            
            // Create dropdown validations for Users sheet
            // Branch dropdown (Column D)
            $branchCount = $branches->count();
            if ($branchCount > 0) {
                $branchValidation = $usersSheet->getCell('D2')->getDataValidation();
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
                    $usersSheet->getCell('D' . $i)->setDataValidation(clone $branchValidation);
                }
            }
            
            // Role dropdown (Column E)
            $roleCount = $roles->count();
            if ($roleCount > 0) {
                $roleValidation = $usersSheet->getCell('E2')->getDataValidation();
                $roleValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $roleValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $roleValidation->setAllowBlank(true);
                $roleValidation->setShowInputMessage(true);
                $roleValidation->setShowErrorMessage(true);
                $roleValidation->setShowDropDown(true);
                $roleValidation->setErrorTitle('Invalid Role');
                $roleValidation->setError('Please select a role from the dropdown list');
                $roleValidation->setPromptTitle('Select Role');
                $roleValidation->setPrompt('Choose a role from the Roles sheet (Optional)');
                $roleValidation->setFormula1('Roles!$B$2:$B$' . ($roleCount + 1));
                
                for ($i = 2; $i <= 1000; $i++) {
                    $usersSheet->getCell('E' . $i)->setDataValidation(clone $roleValidation);
                }
            }
            
            // Module dropdown (Column F)
            $moduleValidation = $usersSheet->getCell('F2')->getDataValidation();
            $moduleValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $moduleValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $moduleValidation->setAllowBlank(true);
            $moduleValidation->setShowInputMessage(true);
            $moduleValidation->setShowErrorMessage(true);
            $moduleValidation->setShowDropDown(true);
            $moduleValidation->setErrorTitle('Invalid Module');
            $moduleValidation->setError('Please select: pos or sales');
            $moduleValidation->setPromptTitle('Select Module');
            $moduleValidation->setPrompt('Choose module: pos or sales (Optional)');
            $moduleValidation->setFormula1('"pos,sales"');
            
            for ($i = 2; $i <= 1000; $i++) {
                $usersSheet->getCell('F' . $i)->setDataValidation(clone $moduleValidation);
            }
            
            // Status dropdown (Column G)
            $statusValidation = $usersSheet->getCell('G2')->getDataValidation();
            $statusValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $statusValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $statusValidation->setAllowBlank(false);
            $statusValidation->setShowInputMessage(true);
            $statusValidation->setShowErrorMessage(true);
            $statusValidation->setShowDropDown(true);
            $statusValidation->setErrorTitle('Invalid Status');
            $statusValidation->setError('Please select: 1 (Active) or 0 (Inactive)');
            $statusValidation->setPromptTitle('Select Status');
            $statusValidation->setPrompt('1 = Active, 0 = Inactive');
            $statusValidation->setFormula1('"1,0"');
            
            for ($i = 2; $i <= 1000; $i++) {
                $usersSheet->getCell('G' . $i)->setDataValidation(clone $statusValidation);
            }
            
            // Auto-size columns
            foreach (['Users', 'Branches', 'Roles'] as $sheetName) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                foreach (range('A', $sheet->getHighestColumn()) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
            
            $spreadsheet->setActiveSheetIndex(0);
            
            $filename = 'users_import_template_' . date('Y-m-d_H-i-s') . '.xlsx';
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
    public function importPreview(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240'
            ]);

            $authUser = $request->user();
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            
            $usersSheet = $spreadsheet->getSheetByName('Users');
            if (!$usersSheet) {
                return $this->ErrorMessage('Users sheet not found in the Excel file', null, 422);
            }

            $users = [];
            $validCount = 0;
            $invalidCount = 0;
            $highestRow = $usersSheet->getHighestRow();

            for ($row = 2; $row <= $highestRow; $row++) {
                $name = trim($usersSheet->getCell('A' . $row)->getValue());
                $email = trim($usersSheet->getCell('B' . $row)->getValue());
                $phone = trim($usersSheet->getCell('C' . $row)->getValue());
                $branchName = trim($usersSheet->getCell('D' . $row)->getValue());
                $roleName = trim($usersSheet->getCell('E' . $row)->getValue());
                $module = trim($usersSheet->getCell('F' . $row)->getValue());
                $status = trim($usersSheet->getCell('G' . $row)->getValue());

                // Skip empty rows
                if (empty($name) && empty($email) && empty($phone)) {
                    continue;
                }

                $validationErrors = [];
                $branchId = null;
                $roleId = null;

                // Validate name
                if (empty($name)) {
                    $validationErrors[] = 'Name is required';
                }

                // Validate email
                if (empty($email)) {
                    $validationErrors[] = 'Email is required';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $validationErrors[] = 'Invalid email format';
                } elseif (User::where('email', $email)->where('merchant_id', $authUser->merchant_id)->exists()) {
                    $validationErrors[] = 'Email already exists';
                }

                // Validate phone
                if (empty($phone)) {
                    $validationErrors[] = 'Phone is required';
                } elseif (User::where('phone', $phone)->where('merchant_id', $authUser->merchant_id)->exists()) {
                    $validationErrors[] = 'Phone already exists';
                }

                // Lookup branch (optional)
                if (!empty($branchName)) {
                    $branch = Branch::where('name', $branchName)
                        ->where('merchant_id', $authUser->merchant_id)
                        ->first();

                    if (!$branch) {
                        $validationErrors[] = "Branch '{$branchName}' not found";
                    } else {
                        $branchId = $branch->id;
                    }
                }

                // Lookup role (optional)
                if (!empty($roleName)) {
                    $role = Role::where('name', $roleName)
                        ->where('guard_name', 'web')
                        ->first();

                    if (!$role) {
                        $validationErrors[] = "Role '{$roleName}' not found";
                    } else {
                        $roleId = $role->id;
                    }
                }

                // Validate module
                if (!empty($module) && !in_array($module, ['pos', 'sales'])) {
                    $validationErrors[] = "Module must be 'pos' or 'sales'";
                }

                // Validate status
                if (empty($status)) {
                    $status = 1;
                } elseif (!in_array($status, ['0', '1', 0, 1])) {
                    $validationErrors[] = "Status must be 0 or 1";
                }

                $isValid = empty($validationErrors);
                if ($isValid) {
                    $validCount++;
                } else {
                    $invalidCount++;
                }

                $users[] = [
                    'row_number' => $row,
                    'row' => $row,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'branch' => $branchName ?: 'N/A',
                    'branch_name' => $branchName ?: 'N/A',
                    'branch_id' => $branchId,
                    'role' => $roleName ?: 'N/A',
                    'role_name' => $roleName ?: 'N/A',
                    'role_id' => $roleId,
                    'module' => $module ?: 'N/A',
                    'status' => $status,
                    'is_valid' => $isValid,
                    'will_be_imported' => $isValid,
                    'validation_errors' => $validationErrors,
                    'errors' => $validationErrors,
                    'row_type' => 'user'
                ];
            }

            $canImport = $validCount > 0;
            $message = $canImport 
                ? "✅ {$validCount} of " . count($users) . " users are valid and ready to import!"
                : "⚠️ All users have validation errors. Please fix them and try again.";

            if ($invalidCount > 0 && $validCount > 0) {
                $message = "⚠️ {$invalidCount} of " . count($users) . " users have validation errors. Only valid users will be imported.";
            }

            return $this->SuccessMessage([
                'message' => $message,
                'summary' => [
                    'total_rows' => count($users),
                    'valid_count' => $validCount,
                    'invalid_count' => $invalidCount,
                    'can_import' => $canImport,
                ],
                'users' => $users
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
     * Import users from Excel
     */
    public function import(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240'
            ]);

            $authUser = $request->user();
            $merchant = \App\Models\Merchant::findOrFail($authUser->merchant_id);
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            
            $usersSheet = $spreadsheet->getSheetByName('Users');
            if (!$usersSheet) {
                return $this->ErrorMessage('Users sheet not found in the Excel file', null, 422);
            }

            $imported = 0;
            $updated = 0;
            $failed = 0;
            $errors = [];

            DB::beginTransaction();

            $highestRow = $usersSheet->getHighestRow();
            for ($row = 2; $row <= $highestRow; $row++) {
                $name = trim($usersSheet->getCell('A' . $row)->getValue());
                $email = trim($usersSheet->getCell('B' . $row)->getValue());
                $phone = trim($usersSheet->getCell('C' . $row)->getValue());
                $branchName = trim($usersSheet->getCell('D' . $row)->getValue());
                $roleName = trim($usersSheet->getCell('E' . $row)->getValue());
                $module = trim($usersSheet->getCell('F' . $row)->getValue());
                $status = trim($usersSheet->getCell('G' . $row)->getValue());

                // Skip empty rows
                if (empty($name) && empty($email) && empty($phone)) {
                    continue;
                }

                try {
                    // Validate required fields
                    if (empty($name) || empty($email) || empty($phone)) {
                        $failed++;
                        $errors[] = "Row {$row}: Missing required fields (name, email, or phone)";
                        continue;
                    }

                    // Lookup branch
                    $branchId = null;
                    if (!empty($branchName)) {
                        $branch = Branch::where('name', $branchName)
                            ->where('merchant_id', $authUser->merchant_id)
                            ->first();
                        if ($branch) {
                            $branchId = $branch->id;
                        }
                    }

                    // Lookup role
                    $roleId = null;
                    if (!empty($roleName)) {
                        $role = Role::where('name', $roleName)
                            ->where('guard_name', 'web')
                            ->first();
                        if ($role) {
                            $roleId = $role->id;
                        }
                    }

                    // Check if user exists
                    $user = User::where('email', $email)
                        ->where('merchant_id', $authUser->merchant_id)
                        ->first();

                    $userData = [
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'merchant_id' => $authUser->merchant_id,
                        'country_id' => $merchant->country_id,
                        'branch_id' => $branchId,
                        'module' => !empty($module) && in_array($module, ['pos', 'sales']) ? $module : null,
                        'status' => !empty($status) ? (int)$status : 1,
                    ];

                    if ($user) {
                        $user->update($userData);
                        $updated++;
                    } else {
                        // Generate random password
                        $password = \Illuminate\Support\Str::random(12);
                        $userData['password'] = Hash::make($password);
                        $user = User::create($userData);
                        $imported++;
                    }

                    // Sync role if provided
                    if ($roleId) {
                        $role = Role::find($roleId);
                        if ($role) {
                            $user->roles()->detach();
                            $user->roles()->attach($roleId, ['model_type' => User::class]);
                        }
                    }

                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Row {$row}: " . $e->getMessage();
                    Log::error("Import error on row {$row}", [
                        'error' => $e->getMessage(),
                        'row_data' => compact('name', 'email', 'phone')
                    ]);
                }
            }

            DB::commit();

            return $this->SuccessMessage([
                'message' => "Import completed. {$imported} users imported, {$updated} updated" . ($failed > 0 ? ", {$failed} failed" : ""),
                'imported' => $imported,
                'updated' => $updated,
                'failed' => $failed,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->ErrorMessage('Failed to import users: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Batch lookup users by IDs (merchant scope)
     */
    public function lookup(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'string',
        ]);

        $authUser = $request->user();

        $ids = array_unique(array_filter($validated['user_ids'], fn ($id) => $id !== null && $id !== ''));

        if (empty($ids)) {
            return response()->json([
                'message' => 'User lookup',
                'data' => [],
            ]);
        }

        $users = User::where('merchant_id', $authUser->merchant_id)
            ->whereIn('id', $ids)
            ->get(['id', 'name', 'last_name' , 'email']);

        $result = [];
        foreach ($users as $user) {
            $fullName = trim(
                ($user->name ?? '') . ' ' .
                ($user->last_name ?? $user->surname ?? $user->family_name ?? '')
            );

            if ($fullName === '') {
                $fullName = $user->email ?? '';
            }

            $result[$user->id] = [
                'name' => $fullName,
            ];
        }

        return response()->json([
            'message' => 'User lookup',
            'data' => $result,
        ]);
    }

    /**
     * Send reset password link to a merchant user.
     */
    public function sendResetPasswordLink(Request $request, string $id)
    {
        try {
            $authUser = $request->user();

            $user = User::where('merchant_id', $authUser->merchant_id)
                ->where('id', $id)
                ->firstOrFail();

            if (empty($user->email)) {
                return $this->ErrorMessage('User does not have an email address', null, 400);
            }

            $token = Str::random(64);

            UsersOtp::create([
                'phone_number' => $user->email,
                'code' => UsersOtp::generateOtpNumber(),
                'token' => PasswordResetLinkService::hashForStorage($token),
                'is_verified' => false,
                'expires_at' => now()->addMinutes(15),
            ]);

            $resetUrl = rtrim(config('app.frontend_url', config('app.url')), '/') . '/reset-password/' . urlencode($token);

            if (view()->exists('emails.reset-password')) {
                Mail::locale(\App\Support\MailLocale::resolve())->send('emails.reset-password', [
                    'user' => $user,
                    'resetUrl' => $resetUrl,
                ], function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject(__('emails.password_reset_subject'));
                });
            } else {
                Mail::raw(
                    "Hello {$user->name},\n\nUse this secure link to reset your password:\n{$resetUrl}\n\nThis link expires in 15 minutes.",
                    function ($message) use ($user) {
                        $message->to($user->email)
                            ->subject('Reset Your Password - ' . config('app.name'));
                    }
                );
            }

            return $this->SuccessMessage([
                'message' => 'Reset password link has been sent to ' . $user->email,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->ErrorMessage('User not found for this merchant.', null, 404);
        } catch (\Throwable $e) {
            Log::error('Failed to send merchant user reset password email', [
                'error' => $e->getMessage(),
                'user_id' => $id ?? null,
                'merchant_id' => $request->user()?->merchant_id,
            ]);

            $message = config('app.debug')
                ? 'Failed to send reset password link: ' . $e->getMessage()
                : 'Failed to send reset password link. Please try again.';

            return $this->ErrorMessage($message, null, 500);
        }
    }
}

