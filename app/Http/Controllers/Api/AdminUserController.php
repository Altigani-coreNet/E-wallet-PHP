<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UsersOtp;
use App\Models\Merchant;
use App\Models\Branch;
use App\Models\Role;
use App\Http\Resources\AdminUserIndexResource;
use App\Services\PasswordResetLinkService;
use App\Traits\ApiResponse;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AdminUserController extends Controller
{
    use ApiResponse;

    /**
     * Batch lookup users by IDs (returns name/email)
     */
    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'string',
        ]);

        $ids = array_unique(array_filter($validated['user_ids'], fn ($id) => $id !== null && $id !== ''));

        if (empty($ids)) {
            return response()->json([
                'message' => 'User lookup',
                'data' => [],
            ]);
        }

        $users = User::whereIn('id', $ids)->get(['id', 'name', 'email']);

        $result = [];
        foreach ($users as $user) {
            $result[$user->id] = [
                'name' => $user->name ?? $user->email ?? '',
            ];
        }

        return response()->json([
            'message' => 'User lookup',
            'data' => $result,
        ]);
    }

    /**
     * Get all users with pagination, search, and filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Keep payload small: only load fields used by the frontend.
            $query = User::query()->with([
                'merchant' => function ($q) {
                    $q->select('id', 'business_name', 'name', 'email', 'country_id', 'is_active');
                },
                'merchant.country' => function ($q) {
                    $q->select('id', 'code', 'name');
                },
                'branch' => function ($q) {
                    $q->select('id', 'name');
                },
                'roles' => function ($q) {
                    $q->select('id', 'name', 'guard_name');
                },
            ]);

            // Search
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // Status filter (accept 'active'/'inactive' or 1/0)
            if ($status = $request->input('status')) {
                $statusValue = in_array(strtolower($status), ['active', '1', 1, true]) ? 1 : 0;
                $query->where('status', $statusValue);
            }

            // Merchant filter
            if ($merchantId = $request->input('merchant_id')) {
                $query->where('merchant_id', $merchantId);
            }

            // Branch filter
            if ($branchId = $request->input('branch_id')) {
                $query->where('branch_id', $branchId);
            }

            // Date range filter
            if ($dateFrom = $request->input('date_from')) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo = $request->input('date_to')) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            // Pagination
            $perPage = $request->input('per_page', 15);
            $users = $query->latest()->paginate($perPage);

            // Use a resource class instead of inline array building.
            $users->getCollection()->transform(fn ($user) => (new AdminUserIndexResource($user))->toArray($request));

            return $this->SuccessMessage($users);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch users: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get user details
     */
    public function show($id): JsonResponse
    {
        try {
            $user = User::with([
                // Keep payload small: only load what the frontend renders.
                'merchant',
                'branch',
                'roles',
                'permissions',
                'terminals',
                'terminalGroups',
                'userGroups',
                'attachments',
                'LatestLogs.user',
            ])->findOrFail($id);

            // Preload counts to avoid N+1 queries when formatting response
            $user->terminalGroups->loadCount('terminals');
            $user->userGroups->loadCount('users');

            $userData = $user->toArray();

            // Ensure we always provide a usable profile image URL
            $userData['profile_image_url'] = $user->getProfileImageApi();

            // Frontend uses both `status` and `is_active` in different places.
            $userData['is_active'] = ($user->status === 1 || $user->status === '1' || $user->status === true || strtolower((string) $user->status) === 'active');
            $userData['status_display_name'] = $userData['is_active'] ? 'Active' : 'Inactive';
            $userData['is_admin'] = ($userData['type'] ?? null) === 'admin';

            // Limit merchant fields to only what UI uses.
            $userData['merchant'] = $user->merchant ? [
                'id' => $user->merchant->id,
                'business_name' => $user->merchant->business_name,
                'name' => $user->merchant->name,
                'owner_name' => $user->merchant->owner_name,
                'email' => $user->merchant->email,
                'phone' => $user->merchant->phone,
                'business_type_display_name' => $user->merchant->business_type_display_name,
                'address' => $user->merchant->address,
                'merchant_code' => $user->merchant->merchant_code,
                'is_active' => (bool) $user->merchant->is_active,
            ] : null;

            // Limit branch fields to only what UI uses.
            $userData['branch'] = $user->branch ? [
                'id' => $user->branch->id,
                'name' => $user->branch->name,
                'address' => $user->branch->address,
                'is_active' => (bool) $user->branch->is_active,
            ] : null;

            // Normalize roles & permissions payloads
            $userData['roles'] = $user->roles
                ->map(fn ($role) => $role->only(['id', 'name', 'guard_name']))
                ->values()
                ->toArray();

            $userData['permissions'] = $user->permissions
                ->map(fn ($permission) => $permission->only(['id', 'name', 'guard_name']))
                ->values()
                ->toArray();

            // Normalize relationships to collections (avoids type ambiguity with attribute casts)
            $terminalsRelation = collect($user->getRelationValue('terminals') ?? []);
            $terminalGroupsRelation = collect($user->getRelationValue('terminalGroups') ?? []);
            $userGroupsRelation = collect($user->getRelationValue('userGroups') ?? []);
            $attachmentsRelation = collect($user->getRelationValue('attachments') ?? []);
            $latestLogsRelation = collect($user->getRelationValue('LatestLogs') ?? []);

            // Remove heavy relationships from the user payload (will be returned separately)
            unset(
                $userData['terminals'],
                $userData['terminal_groups'],
                $userData['user_groups'],
                $userData['attachments'],
                $userData['LatestLogs'],
                $userData['latest_logs']
            );

            // Derived statistics & collections
            $statistics = [
                'total_terminals' => $terminalsRelation->count(),
                'online_terminals' => $user->getOnlineTerminals()->count(),
                'offline_terminals' => $user->getOfflineTerminals()->count(),
                'terminal_groups' => $terminalGroupsRelation->count(),
                'user_groups' => $userGroupsRelation->count(),
                'attachments' => $attachmentsRelation->count(),
            ];

            $terminals = $terminalsRelation->map(function ($terminal) {
                $assignedAt = null;
                $assignedAtValue = data_get($terminal, 'pivot.created_at');

                if ($assignedAtValue) {
                    $assignedAt = Carbon::parse($assignedAtValue)->toDateTimeString();
                }

                return [
                    'id' => $terminal->id,
                    'name' => $terminal->name,
                    'terminal_id' => $terminal->terminal_id,
                    'device_id' => $terminal->device_id,
                    'serial_no' => $terminal->serial_no,
                    'model' => $terminal->model,
                    'brand' => $terminal->brand,
                    'manufacturer' => $terminal->manufacturer,
                    'terminal_status' => $terminal->terminal_status,
                    'status_display_name' => $terminal->status_display_name ?? ($terminal->is_active ? 'Active' : 'Inactive'),
                    'is_active' => (bool) $terminal->is_active,
                    'assigned_at' => $assignedAt,
                ];
            })->values()->toArray();

            $terminalGroups = $terminalGroupsRelation->map(function ($group) {
                $assignedAt = null;
                $assignedAtValue = data_get($group, 'pivot.created_at');

                if ($assignedAtValue) {
                    $assignedAt = Carbon::parse($assignedAtValue)->toDateTimeString();
                }

                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'group_id' => $group->group_id,
                    'description' => $group->description,
                    'is_active' => (bool) $group->is_active,
                    'status_display_name' => $group->status_display_name ?? ($group->is_active ? 'Active' : 'Inactive'),
                    'terminals_count' => $group->terminals_count ?? $group->terminals()->count(),
                    'assigned_at' => $assignedAt,
                ];
            })->values()->toArray();

            $userGroups = $userGroupsRelation->map(function ($group) {
                $assignedAt = null;
                $assignedAtValue = data_get($group, 'pivot.created_at');

                if ($assignedAtValue) {
                    $assignedAt = Carbon::parse($assignedAtValue)->toDateTimeString();
                }

                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'group_id' => $group->group_id,
                    'description' => $group->description,
                    'is_active' => (bool) $group->is_active,
                    'status_display_name' => $group->status_display_name ?? ($group->is_active ? 'Active' : 'Inactive'),
                    'users_count' => $group->users_count ?? $group->users()->count(),
                    'assigned_at' => $assignedAt,
                ];
            })->values()->toArray();

            $attachments = $attachmentsRelation->map(function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'title' => $attachment->title,
                    'details' => $attachment->details,
                    'type' => $attachment->type,
                    'url' => $attachment->attachment_url,
                    'created_at' => optional($attachment->created_at)?->toDateTimeString(),
                ];
            })->values()->toArray();

            $latestLogs = $latestLogsRelation->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'label' => $log->label,
                    'time' => $log->time,
                    'text' => $log->text,
                    'metadata' => $log->metadata,
                ];
            })->values()->toArray();

            return $this->SuccessMessage([
                'user' => $userData,
                'statistics' => $statistics,
                'collections' => [
                    'terminals' => $terminals,
                    'terminal_groups' => $terminalGroups,
                    'user_groups' => $userGroups,
                    'attachments' => $attachments,
                ],
                'latest_logs' => $latestLogs,
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch user: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Create a new user
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'required|string|max:20|unique:users,phone',
                'merchant_id' => 'required|exists:merchants,id',
                'branch_id' => 'nullable|exists:branches,id',
                'status' => 'nullable|in:active,inactive',
                'is_admin' => 'nullable|boolean',
                'roles' => 'nullable|array',
                'roles.*' => 'exists:roles,id',
            ]);

            $roleIds = $validated['roles'] ?? [];
            unset($validated['roles']);

            // Get merchant to retrieve country_id
            $merchant = Merchant::findOrFail($validated['merchant_id']);
            
            // Auto-generate secure random password
            $plainPassword = $this->generateSecurePassword(14);
            $validated['password'] = Hash::make($plainPassword);
            
            // Automatically set country_id from merchant
            $validated['country_id'] = $merchant->country_id;

            // Convert status string to boolean (DB stores 0/1)
            if (isset($validated['status'])) {
                $validated['status'] = strtolower($validated['status']) === 'active' ? 1 : 0;
            }

            // Map request flag to underlying user type.
            // Rule: if `is_admin` is true -> `type` must be `admin`.
            if (array_key_exists('is_admin', $validated)) {
                $validated['type'] = !empty($validated['is_admin']) ? 'admin' : 'cashier';
            }

            $user = User::create($validated);

            if (! empty($roleIds)) {
                $roles = Role::query()
                    ->where('guard_name', 'web')
                    ->where('merchant_id', $validated['merchant_id'])
                    ->whereIn('id', $roleIds)
                    ->get();
                $user->syncRoles($roles);
            }

            // Send credentials email to the newly created user
            if (!empty($user->email)) {
                try {
                    // Try to send email with credentials
                    // Note: Make sure NewUserCredentialsMail exists or create a simple notification
                    $resetUrl = config('app.frontend_url', config('app.url')) . '/reset-password?email=' . urlencode($user->email);
                    
                    Mail::locale(\App\Support\MailLocale::resolve())->send('emails.users.new_credentials', [
                        'user' => $user,
                        'password' => $plainPassword,
                        'plainPassword' => $plainPassword,
                        'resetUrl' => $resetUrl
                    ], function ($message) use ($user) {
                        $message->to($user->email)
                                ->subject(__('emails.new_credentials_subject'));
                    });
                } catch (\Exception $e) {
                    // Log email error but don't fail user creation
                    Log::error('Failed to send credentials email: ' . $e->getMessage());
                }
            }

            return $this->SuccessMessage([
                'user' => $user,
                'message' => 'User created successfully. Credentials have been sent to their email.'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create user: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update user
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'phone' => 'sometimes|string|max:20|unique:users,phone,' . $id,
                'merchant_id' => 'sometimes|exists:merchants,id',
                'branch_id' => 'nullable|exists:branches,id',
                'status' => 'sometimes|in:active,inactive',
                'is_admin' => 'sometimes|boolean',
                'roles' => 'sometimes|array',
                'roles.*' => 'exists:roles,id',
            ]);

            $roleIds = null;
            if (array_key_exists('roles', $validated)) {
                $roleIds = $validated['roles'];
                unset($validated['roles']);
            }

            // If merchant_id is being updated, automatically update country_id from the new merchant
            if (isset($validated['merchant_id']) && $validated['merchant_id'] != $user->merchant_id) {
                $merchant = Merchant::findOrFail($validated['merchant_id']);
                $validated['country_id'] = $merchant->country_id;
            }

            // Convert status string to boolean (DB stores 0/1)
            if (isset($validated['status'])) {
                $validated['status'] = strtolower($validated['status']) === 'active' ? 1 : 0;
            }

            // Map request flag to underlying user type.
            if (array_key_exists('is_admin', $validated)) {
                $validated['type'] = !empty($validated['is_admin']) ? 'admin' : 'cashier';
            }

            $user->update($validated);

            if ($roleIds !== null) {
                $merchantId = $user->fresh()->merchant_id;
                $roles = Role::query()
                    ->where('guard_name', 'web')
                    ->where('merchant_id', $merchantId)
                    ->whereIn('id', $roleIds)
                    ->get();
                $user->syncRoles($roles);
            }

            return $this->SuccessMessage($user->fresh(['roles']));

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update user: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Delete user
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            return $this->SuccessMessage(['message' => 'User deleted successfully']);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete user: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get user statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_users' => User::whereNotNull('merchant_id')->count(),
                'active_users' => User::where('status', 1)->whereNotNull('merchant_id')->count(),
                'inactive_users' => User::where('status', 0)->whereNotNull('merchant_id')->count(),
                'admin_users' => User::where('is_admin', true)->count(),
                'users_this_month' => User::whereMonth('created_at', now()->month)->whereNotNull('merchant_id')->count(),
                'users_today' => User::whereDate('created_at', now())->whereNotNull('merchant_id')->count(),
            ];

            return $this->SuccessMessage($stats);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch statistics: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export users
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $query = User::query()->with(['merchant.country', 'branch']);

            // Apply same filters as index
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($status = $request->input('status')) {
                $statusValue = in_array(strtolower($status), ['active', '1', 1, true]) ? 1 : 0;
                $query->where('status', $statusValue);
            }

            if ($merchantId = $request->input('merchant_id')) {
                $query->where('merchant_id', $merchantId);
            }

            $users = $query->get();

            $exportData = $users->map(function ($user) {
                return [
                    'ID' => $user->id,
                    'Name' => $user->name,
                    'Email' => $user->email,
                    'Phone' => $user->phone,
                    'Merchant' => $user->merchant->business_name ?? 'N/A',
                    'Branch' => $user->branch->name ?? 'N/A',
                    'Country' => $user->merchant->country->name ?? 'N/A',
                    'Status' => $user->status,
                    'Is Admin' => $user->is_admin ? 'Yes' : 'No',
                    'Created At' => $user->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return $this->SuccessMessage([
                'data' => $exportData,
                'filename' => 'users_export_' . date('Y-m-d_H-i-s') . '.csv'
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to export users: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk delete users
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:users,id'
            ]);

            $count = User::whereIn('id', $validated['ids'])->delete();

            return $this->SuccessMessage([
                'message' => "{$count} user(s) deleted successfully",
                'count' => $count
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete users: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Activate user
     * Note: users.status is boolean (1=active, 0=inactive)
     */
    public function activate($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $user->update(['status' => 1]);

            return $this->SuccessMessage([
                'message' => 'User activated successfully',
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to activate user: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Deactivate user
     * Note: users.status is boolean (1=active, 0=inactive)
     */
    public function deactivate($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $user->update(['status' => 0]);

            return $this->SuccessMessage([
                'message' => 'User deactivated successfully',
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to deactivate user: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export template for bulk import (Excel with multiple sheets)
     */
    public function exportTemplate()
    {
        try {
            $spreadsheet = new Spreadsheet();
            
            // Sheet 1: Users Template
            $usersSheet = $spreadsheet->getActiveSheet();
            $usersSheet->setTitle('Users');
            
            // Set headers with instructions
            $usersSheet->setCellValue('A1', 'Name *');
            $usersSheet->setCellValue('B1', 'Email *');
            $usersSheet->setCellValue('C1', 'Phone *');
            $usersSheet->setCellValue('D1', 'Merchant * (Select from dropdown)');
            $usersSheet->setCellValue('E1', 'Branch (Optional - Select from dropdown)');
            $usersSheet->setCellValue('F1', 'Status * (Select from dropdown)');
            $usersSheet->setCellValue('G1', 'Is Admin * (0 or 1)');
            
            // Leave sample row empty - dropdowns will guide users
            
            // Style header row
            $usersSheet->getStyle('A1:G1')->getFont()->setBold(true);
            $usersSheet->getStyle('A1:G1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF4472C4');
            $usersSheet->getStyle('A1:G1')->getFont()->getColor()->setRGB('FFFFFF');
            
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
            
            // Create dropdown validations for Users sheet
            // Merchant dropdown (Column D) - references Merchants sheet
            $merchantCount = $merchants->count();
            if ($merchantCount > 0) {
                $merchantValidation = $usersSheet->getCell('D2')->getDataValidation();
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
                
                // Apply validation to all rows (up to 1000)
                for ($i = 2; $i <= 1000; $i++) {
                    $usersSheet->getCell('D' . $i)->setDataValidation(clone $merchantValidation);
                }
            }
            
            // Branch dropdown (Column E) - references Branches sheet
            $branchCount = $branches->count();
            if ($branchCount > 0) {
                $branchValidation = $usersSheet->getCell('E2')->getDataValidation();
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
                
                // Apply validation to all rows (up to 1000)
                for ($i = 2; $i <= 1000; $i++) {
                    $usersSheet->getCell('E' . $i)->setDataValidation(clone $branchValidation);
                }
            }
            
            // Status dropdown (Column F)
            $statusValidation = $usersSheet->getCell('F2')->getDataValidation();
            $statusValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $statusValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $statusValidation->setAllowBlank(false);
            $statusValidation->setShowInputMessage(true);
            $statusValidation->setShowErrorMessage(true);
            $statusValidation->setShowDropDown(true);
            $statusValidation->setErrorTitle('Invalid Status');
            $statusValidation->setError('Please select: active or inactive');
            $statusValidation->setPromptTitle('Select Status');
            $statusValidation->setPrompt('Choose user status');
            $statusValidation->setFormula1('"active,inactive"');
            
            // Apply status validation to all rows (up to 1000)
            for ($i = 2; $i <= 1000; $i++) {
                $usersSheet->getCell('F' . $i)->setDataValidation(clone $statusValidation);
            }
            
            // Is Admin dropdown (Column G)
            $isAdminValidation = $usersSheet->getCell('G2')->getDataValidation();
            $isAdminValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $isAdminValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $isAdminValidation->setAllowBlank(false);
            $isAdminValidation->setShowInputMessage(true);
            $isAdminValidation->setShowErrorMessage(true);
            $isAdminValidation->setShowDropDown(true);
            $isAdminValidation->setErrorTitle('Invalid Value');
            $isAdminValidation->setError('Please select: 0 (No) or 1 (Yes)');
            $isAdminValidation->setPromptTitle('Is Admin?');
            $isAdminValidation->setPrompt('0 = Regular User, 1 = Admin User');
            $isAdminValidation->setFormula1('"0,1"');
            
            // Apply is_admin validation to all rows (up to 1000)
            for ($i = 2; $i <= 1000; $i++) {
                $usersSheet->getCell('G' . $i)->setDataValidation(clone $isAdminValidation);
            }
            
            // Auto-size columns for all sheets
            foreach (['Users', 'Merchants', 'Branches'] as $sheetName) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                foreach (range('A', $sheet->getHighestColumn()) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
            
            // Set active sheet back to Users
            $spreadsheet->setActiveSheetIndex(0);
            
            // Save to temp file
            $filename = 'users_import_template_' . date('Y-m-d_H-i-s') . '.xlsx';
            $filepath = storage_path('app/temp/' . $filename);
            
            // Ensure temp directory exists
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
     * Preview import data (Excel with merchant/branch lookup)
     */
    public function importPreview(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240'
            ]);

            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            
            // Read Users sheet
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
                $merchantName = trim($usersSheet->getCell('D' . $row)->getValue());
                $branchName = trim($usersSheet->getCell('E' . $row)->getValue());
                $status = trim($usersSheet->getCell('F' . $row)->getValue());
                $isAdmin = trim($usersSheet->getCell('G' . $row)->getValue());

                // Skip completely empty rows
                if (empty($name) && empty($email) && empty($phone)) {
                    continue;
                }

                $validationErrors = [];
                $merchantId = null;
                $branchId = null;

                // Validate name
                if (empty($name)) {
                    $validationErrors[] = 'Name is required';
                }

                // Validate email
                if (empty($email)) {
                    $validationErrors[] = 'Email is required';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $validationErrors[] = 'Invalid email format';
                } elseif (User::where('email', $email)->exists()) {
                    $validationErrors[] = 'Email already exists';
                }

                // Validate phone
                if (empty($phone)) {
                    $validationErrors[] = 'Phone is required';
                }

                // Lookup merchant by name
                if (empty($merchantName) || $merchantName === 'Select from Merchants sheet') {
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

                // Lookup branch by name (optional)
                if (!empty($branchName) && $branchName !== 'Select from Branches sheet' && $merchantId) {
                    $branch = Branch::where('name', $branchName)
                        ->where('merchant_id', $merchantId)
                        ->first();

                    if (!$branch) {
                        $validationErrors[] = "Branch '{$branchName}' not found for this merchant";
                    } else {
                        $branchId = $branch->id;
                    }
                }

                // Validate status
                if (empty($status)) {
                    $status = 'active';
                } elseif (!in_array($status, ['active', 'inactive'])) {
                    $validationErrors[] = "Status must be 'active' or 'inactive'";
                }

                // Validate is_admin
                if (!in_array($isAdmin, ['0', '1', '', 0, 1])) {
                    $validationErrors[] = "Is Admin must be 0 or 1";
                }

                $isValid = empty($validationErrors);
                if ($isValid) {
                    $validCount++;
                } else {
                    $invalidCount++;
                }

                $users[] = [
                    'row' => $row,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'merchant' => $merchantName,
                    'merchant_id' => $merchantId,
                    'branch' => $branchName ?: 'N/A',
                    'branch_id' => $branchId,
                    'status' => $status,
                    'is_admin' => $isAdmin == '1' || $isAdmin == 1 ? 'Yes' : 'No',
                    'is_valid' => $isValid,
                    'errors' => $validationErrors
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
    public function import(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240'
            ]);

            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            
            // Read Users sheet
            $usersSheet = $spreadsheet->getSheetByName('Users');
            if (!$usersSheet) {
                return $this->ErrorMessage('Users sheet not found in the Excel file', null, 422);
            }

            $imported = 0;
            $skipped = 0;
            $errors = [];
            $highestRow = $usersSheet->getHighestRow();

            for ($row = 2; $row <= $highestRow; $row++) {
                $name = trim($usersSheet->getCell('A' . $row)->getValue());
                $email = trim($usersSheet->getCell('B' . $row)->getValue());
                $phone = trim($usersSheet->getCell('C' . $row)->getValue());
                $merchantName = trim($usersSheet->getCell('D' . $row)->getValue());
                $branchName = trim($usersSheet->getCell('E' . $row)->getValue());
                $status = trim($usersSheet->getCell('F' . $row)->getValue());
                $isAdmin = trim($usersSheet->getCell('G' . $row)->getValue());

                // Skip completely empty rows
                if (empty($name) && empty($email) && empty($phone)) {
                    continue;
                }

                try {
                    // Validate required fields
                    if (empty($name) || empty($email) || empty($phone)) {
                        $skipped++;
                        $errors[] = "Row {$row}: Missing required fields";
                        continue;
                    }

                    // Validate email
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $skipped++;
                        $errors[] = "Row {$row}: Invalid email format";
                        continue;
                    }

                    // Check if email already exists
                    if (User::where('email', $email)->exists()) {
                        $skipped++;
                        $errors[] = "Row {$row}: Email {$email} already exists";
                        continue;
                    }

                    // Lookup merchant
                    if (empty($merchantName) || $merchantName === 'Select from Merchants sheet') {
                        $skipped++;
                        $errors[] = "Row {$row}: Merchant is required";
                        continue;
                    }

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
                    if (!empty($branchName) && $branchName !== 'Select from Branches sheet') {
                        $branch = Branch::where('name', $branchName)
                            ->where('merchant_id', $merchantId)
                            ->first();

                        if ($branch) {
                            $branchId = $branch->id;
                        }
                    }

                    // Auto-generate secure password
                    $plainPassword = $this->generateSecurePassword(14);

                    // Create user with country_id from merchant
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'password' => Hash::make($plainPassword),
                        'merchant_id' => $merchantId,
                        'country_id' => $merchant->country_id,
                        'branch_id' => $branchId,
                        'status' => !empty($status) && in_array($status, ['active', 'inactive']) ? $status : 'active',
                        'is_admin' => ($isAdmin == '1' || $isAdmin == 1) ? true : false,
                    ]);

                    // Try to send credentials email
                    try {
                        $resetUrl = config('app.frontend_url', config('app.url')) . '/reset-password?email=' . urlencode($user->email);
                        
                        Mail::locale(\App\Support\MailLocale::resolve())->send('emails.users.new_credentials', [
                            'user' => $user,
                            'password' => $plainPassword,
                            'plainPassword' => $plainPassword,
                            'resetUrl' => $resetUrl
                        ], function ($message) use ($user) {
                            $message->to($user->email)
                                    ->subject(__('emails.new_credentials_subject'));
                        });
                    } catch (\Exception $e) {
                        Log::error('Failed to send credentials email for imported user: ' . $e->getMessage());
                    }

                    $imported++;

                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Row {$row}: " . $e->getMessage();
                    Log::error("Failed to import user at row {$row}: " . $e->getMessage());
                }
            }

            $message = "{$imported} user(s) imported successfully";
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
            
            return $this->ErrorMessage('Failed to import users: ' . $e->getMessage(), null, 500);
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

        // Ensure at least one from each important character class.
        $passwordChars = [
            $lower[random_int(0, strlen($lower) - 1)],
            $upper[random_int(0, strlen($upper) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        for ($i = count($passwordChars); $i < $length; $i++) {
            $passwordChars[] = $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Fisher-Yates shuffle using random_int for cryptographic randomness.
        for ($i = count($passwordChars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$passwordChars[$i], $passwordChars[$j]] = [$passwordChars[$j], $passwordChars[$i]];
        }

        return implode('', $passwordChars);
    }

    /**
     * Send reset password link to user
     */
    public function sendResetPasswordLink($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            if (empty($user->email)) {
                return $this->ErrorMessage('User does not have an email address', null, 400);
            }

            $token = Str::random(64);

            // dd('jksa');
            // Store HMAC of token (deterministic DB lookup; plain token only in email link)
            UsersOtp::create([
                'phone_number' => $user->email, // legacy field used for email too
                'code' => UsersOtp::generateOtpNumber(),
                'token' => PasswordResetLinkService::hashForStorage($token),
                'is_verified' => false,
                'expires_at' => now()->addMinutes(15),
            ]);

            // Generate password reset link
            $resetUrl = rtrim(config('app.frontend_url', config('app.url')), '/') . '/reset-password/' . urlencode($token);
// dd($resetUrl);
            // Send reset password email
            try {
                if (view()->exists('emails.reset-password')) {
                    Mail::locale(\App\Support\MailLocale::resolve())->send('emails.reset-password', [
                        'user' => $user,
                        'resetUrl' => $resetUrl
                    ], function ($message) use ($user) {
                        $message->to($user->email)
                                ->subject(__('emails.password_reset_subject'));
                    });
                } else {
                    // dd('jksa');
                    Mail::raw(
                        "Hello {$user->name},\n\nUse this secure link to reset your password:\n{$resetUrl}\n\nThis link expires in 15 minutes.",
                        function ($message) use ($user) {
                            $message->to($user->email)
                                    ->subject('Reset Your Password - ' . config('app.name'));
                        }
                    );
                }

                return $this->SuccessMessage([
                    'message' => 'Reset password link has been sent to ' . $user->email
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send reset password email: ' . $e->getMessage());
                return $this->ErrorMessage('Failed to send reset password email. Please try again.', null, 500);
            }

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to send reset password link: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Validate reset-password token (query string — preferred; avoids path encoding issues).
     */
    public function validateResetPasswordTokenQuery(Request $request): JsonResponse
    {
        return $this->respondValidateResetToken((string) $request->query('token', ''));
    }

    /**
     * Validate reset-password token from path segment (backward compatible).
     */
    public function validateResetPasswordTokenPath(string $token): JsonResponse
    {
        return $this->respondValidateResetToken($token);
    }

    private function respondValidateResetToken(string $rawToken): JsonResponse
    {
        $otp = PasswordResetLinkService::findActiveOtpByPlainToken($rawToken);

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset token.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reset token is valid.',
        ]);
    }

    /**
     * Reset password using token sent via email.
     */
    public function resetPasswordWithToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        // Same plain token as in the email link /reset-password/{token}
        $plainToken = PasswordResetLinkService::normalizePlainToken($validated['token']);
        if ($plainToken === '') {
            return response()->json([
                'success' => false,
                'message' => 'Reset token is missing.',
            ], 422);
        }

        $otp = PasswordResetLinkService::findActiveOtpByPlainToken($plainToken);
        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset token.',
            ], 400);
        }

        $user = User::where('email', $otp->phone_number)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();
        $otp->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully.',
        ]);
    }
}



