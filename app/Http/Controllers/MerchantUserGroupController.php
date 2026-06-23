<?php

namespace App\Http\Controllers;

use App\Models\UserGroup;
use App\Models\Branch;
use App\Models\Terminal;
use App\Models\TerminalGroup;
use App\Models\User;
use App\Models\Log;
use App\Models\Merchant;
use App\Services\UserGroupService;
use App\Traits\Select2Trait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class MerchantUserGroupController extends Controller
{
    use AuthorizesRequests, Select2Trait;
    
    public function __construct(private UserGroupService $userGroupService)
    {
        //
    }

    public function select(Request $request)
    {
        if (!auth()->user()->can('users_groups') && !auth()->user()->can('view_users_groups')) {
            abort(403, 'Unauthorized access to user groups.');
        }
        $query = UserGroup::where('merchant_id', auth()->user()->merchant_id);
        
        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        
        // Filter by active status if provided
        if ($request->has('status')) {
            $status = $request->status === 'active' ? 1 : 0;
            $query->where('is_active', $status);
        }
        
        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('group_id', 'like', "%{$search}%");
            });
        }
        
        $userGroups = $query->select('id', 'name', 'group_id')
                           ->orderBy('name')
                           ->limit(10)
                           ->get()
                           ->map(function ($userGroup) {
                               return [
                                   'id' => $userGroup->id,
                                   'text' => $userGroup->name . ' (' . $userGroup->group_id . ')'
                               ];
                           });

        return response()->json($userGroups);
    }

    public function index()
    {
        if (!auth()->user()->can('users_groups') && !auth()->user()->can('view_users_groups')) {
            abort(403, 'Unauthorized access to user groups.');
        }
        return view('merchant.user_groups.index');
    }

    public function create()
    {
        if (!auth()->user()->can('users_groups') && !auth()->user()->can('create_users_groups')) {
            abort(403, 'Unauthorized access to create user groups.');
        }
        return view('merchant.user_groups.create');
    }

    public function store(Request $request)
    {
        // dd('controller');
        if (!auth()->user()->can('users_groups') && !auth()->user()->can('create_users_groups')) {
            abort(403, 'Unauthorized access to create user groups.');
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'nullable|exists:branches,id',
            'description' => 'nullable|string',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        

        try {
            DB::beginTransaction();

            $userGroup = UserGroup::create([
                'name' => $request->name,
                'group_id' => UserGroup::generateGroupId(),
                'merchant_id' => $request->merchant_id,
                'branch_id' => $request->branch_id,
                'description' => $request->description,
                'is_active' => true,
            ]);

            // Attach users to the group
            if ($request->has('user_ids')) {
                $userGroup->users()->attach($request->user_ids);
                
                // Log user assignment to group for each user
                $admin = auth()->user();
                foreach ($request->user_ids as $userId) {
                    $user = User::find($userId);
                    if ($user) {
                        $user->logUserGroupAssignment($admin, $userGroup);
                    }
                }
            }


            // Log the user group creation
            $merchant = Merchant::find($request->merchant_id);
            $userName = $admin->name;
            
            Log::create([
                'loggable_type' => Merchant::class,
                'loggable_id' => $merchant->id,
                'user_id' => Auth::user()->id,
                'user_type' => get_class(Auth::user()),
                'action' => 'user_group_created',
                'description' => "Merchant added user group '{$userGroup->name}' by user '{$userName}'",
                'metadata' => json_encode([
                    'message' => "Merchant added user group '{$userGroup->name}' by user '{$userName}'",
                    'user_group_name' => $userGroup->name,
                    'user_group_id' => $userGroup->id,
                    'created_by_user' => $userName,
                    'created_at' => now()->toDateTimeString(),
                    'terminal_mode' => $userGroup->is_single_terminal ? 'single_terminal' : 'terminal_groups',
                    'users_count' => $userGroup->users->count(),
                ]),
            ]);

            DB::commit();

            return redirect()->route('merchant.user-groups.index')
                ->with('success', 'User group created successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()
                ->with('error', 'Failed to create user group: ' . $e->getMessage());
        }
    }

    public function show(UserGroup $userGroup)
    {
        if (!auth()->user()->can('users_groups') && !auth()->user()->can('view_users_groups')) {
            abort(403, 'Unauthorized access to user groups.');
        }
        // Check if the user group belongs to the authenticated merchant
        if ($userGroup->merchant_id !== auth()->user()->merchant_id) {
            abort(403, 'Unauthorized access to this user group.');
        }

        $userGroup->load(['merchant', 'branch', 'users']);
        return view('merchant.user_groups.show', compact('userGroup'));
    }

    public function edit(UserGroup $userGroup)
    {
        if (!auth()->user()->can('users_groups') && !auth()->user()->can('edit_users_groups')) {
            abort(403, 'Unauthorized access to edit user groups.');
        }
        // Check if the user group belongs to the authenticated merchant
        if ($userGroup->merchant_id !== auth()->user()->merchant_id) {
            abort(403, 'Unauthorized access to this user group.');
        }

        $userGroup->load(['merchant', 'branch', 'users']);
        
        // Get users for the authenticated merchant
        $merchantUsers = User::where('merchant_id', auth()->user()->merchant_id)
            ->active()
            ->get();

        // Get branches for the authenticated merchant
        $merchantBranches = Branch::where('merchant_id', auth()->user()->merchant_id)
            ->active()
            ->get();

        return view('merchant.user_groups.edit', compact('userGroup', 'merchantUsers', 'merchantBranches'));
    }

    public function update(Request $request, UserGroup $userGroup)
    {
        if (!auth()->user()->can('users_groups') && !auth()->user()->can('edit_users_groups')) {
            abort(403, 'Unauthorized access to edit user groups.');
        }
        // Check if the user group belongs to the authenticated merchant
        if ($userGroup->merchant_id !== auth()->user()->merchant_id) {
            abort(403, 'Unauthorized access to this user group.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'nullable|exists:branches,id',
            'description' => 'nullable|string',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        try {
            DB::beginTransaction();

            // Update the user group
            $userGroup->update([
                'name' => $request->name,
                'branch_id' => $request->branch_id,
                'description' => $request->description,
            ]);

            // Sync users to the group
            if ($request->has('user_ids')) {
                $userGroup->users()->sync($request->user_ids);
            }

            DB::commit();

            // Log the user group update
            $merchant = Merchant::find(auth()->user()->merchant_id);
            $userName = auth()->user()->name;
            
            Log::create([
                'loggable_type' => Merchant::class,
                'loggable_id' => $merchant->id,
                'user_id' => Auth::user()->id,
                'user_type' => get_class(Auth::user()),
                'action' => 'user_group_updated',
                'description' => "Merchant updated user group '{$userGroup->name}' by user '{$userName}'",
                'metadata' => json_encode([
                    'message' => "Merchant updated user group '{$userGroup->name}' by user '{$userName}'",
                    'user_group_name' => $userGroup->name,
                    'user_group_id' => $userGroup->id,
                    'updated_by_user' => $userName,
                    'updated_at' => now()->toDateTimeString(),
                    'users_count' => $userGroup->users->count(),
                ]),
            ]);

            return redirect()->route('merchant.user-groups.index')
                ->with('success', 'User group updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()
                ->with('error', 'Failed to update user group: ' . $e->getMessage());
        }
    }

    public function destroy(UserGroup $userGroup)
    {
        if (!auth()->user()->can('users_groups') && !auth()->user()->can('delete_users_groups')) {
            abort(403, 'Unauthorized access to delete user groups.');
        }
        // Check if the user group belongs to the authenticated merchant
        if ($userGroup->merchant_id !== auth()->user()->merchant_id) {
            abort(403, 'Unauthorized access to this user group.');
        }

        try {
            // Store user group info before deletion for logging
            $userGroupName = $userGroup->name;
            $userGroupId = $userGroup->id;
            
            $userGroup->delete();
            
            // Log the user group deletion
            $merchant = Merchant::find(auth()->user()->merchant_id);
            $userName = auth()->user()->name;
            
            Log::create([
                'loggable_type' => Merchant::class,
                'loggable_id' => $merchant->id,
                'user_id' => Auth::user()->id,
                'user_type' => get_class(Auth::user()),
                'action' => 'user_group_deleted',
                'description' => "Merchant deleted user group '{$userGroupName}' by user '{$userName}'",
                'metadata' => json_encode([
                    'message' => "Merchant deleted user group '{$userGroupName}' by user '{$userName}'",
                    'user_group_name' => $userGroupName,
                    'user_group_id' => $userGroupId,
                    'deleted_by_user' => $userName,
                    'deleted_at' => now()->toDateTimeString(),
                ]),
            ]);
            
            return redirect()->route('merchant.user-groups.index')
                ->with('success', 'User group deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete user group: ' . $e->getMessage());
        }
    }

    public function getMerchantUsers(Request $request)
    {
        $users = User::where('merchant_id', auth()->user()->merchant_id)
            ->active()
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    public function getMerchantTerminalGroups(Request $request)
    {
        $terminalGroups = TerminalGroup::where('merchant_id', auth()->user()->merchant_id)
            ->active()
            ->get(['id', 'name', 'group_id']);

        return response()->json($terminalGroups);
    }

    public function getMerchantTerminals(Request $request)
    {
        $terminals = Terminal::where('merchant_id', auth()->user()->merchant_id)
            ->active()
            ->get(['id', 'name', 'terminal_id']);

        return response()->json($terminals);
    }

    public function getMerchantBranches(Request $request)
    {
        $branches = Branch::where('merchant_id', auth()->user()->merchant_id)
            ->active()
            ->get(['id', 'name']);

        return response()->json($branches);
    }

    public function toggleStatus(UserGroup $userGroup)
    {
        // Check if the user group belongs to the authenticated merchant
        if ($userGroup->merchant_id !== auth()->user()->merchant_id) {
            abort(403, 'Unauthorized access to this user group.');
        }

        try {
            $userGroup->update([
                'is_active' => !$userGroup->is_active
            ]);

            $status = $userGroup->is_active ? 'activated' : 'deactivated';
            return redirect()->route('merchant.user-groups.index')
                ->with('success', "User group {$status} successfully.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update user group status: ' . $e->getMessage());
        }
    }

    public function data(Request $request): JsonResponse
    {
        if (!auth()->user()->can('users_groups') && !auth()->user()->can('view_users_groups')) {
            abort(403, 'Unauthorized access to user groups.');
        }
        $query = UserGroup::with(['merchant', 'branch', 'users'])
            ->where('merchant_id', auth()->user()->merchant_id);

        if ($request->has('search') && !is_array($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('group_id', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            });
        }

        if ($request->has('status')) {
            $status = match ($request->status) {
                "active" => 1,
                default => 0
            };

            $query->where('is_active', $status);
        }

        return DataTables::of($query)
            ->addColumn('record_select', function ($group) {
                return view('merchant.user_groups.data_table.record_select', compact('group'));
            })
            ->addColumn('actions', function ($group) {
                return view('merchant.user_groups.data_table.actions', compact('group'));
            })
            ->editColumn("status", fn($item) => $item->getStatusWithSpan())
            ->editColumn("name", fn($item) => "<div> $item->name <br/> {$item->group_id}</div>")
            ->editColumn("branch_id", fn($item) => $item->branch ? $item->branch->name : 'N/A')
            // ->editColumn("terminal_mode", fn($item) => $item->getTerminalModeDisplayAttribute())
            ->editColumn("users_count", fn($item) => "<span class='badge badge-light-primary badge-sm'>{$item->users->count()}</span>")
            // ->editColumn("terminal_groups_count", fn($item) => $item->is_single_terminal ? 
            //     "<span class='badge badge-light-info badge-sm'>Single Terminal</span>" : 
            //     "<span class='badge badge-light-warning badge-sm'>{$item->terminalGroups->count()}</span>")
            ->editColumn("created_at", fn($item) => $item->created_at->format('M d, Y H:i'))
            ->rawColumns(['record_select', 'actions', 'name', 'status', 'branch_id', 'terminal_mode', 'users_count', 'terminal_groups_count', 'created_at'])
            ->toJson();
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        if (!auth()->user()->can('users_groups') && !auth()->user()->can('delete_users_groups')) {
            abort(403, 'Unauthorized access to delete user groups.');
        }
        try {
            $request->validate([
                'ids' => 'required|string'
            ]);

            $ids = explode(',', $request->ids);
            $ids = array_filter($ids); // Remove empty values

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No user groups selected for deletion.'
                ], 400);
            }

            DB::beginTransaction();

            // Delete user groups that belong to the authenticated merchant
            $deletedCount = UserGroup::whereIn('id', $ids)
                ->where('merchant_id', auth()->user()->merchant_id)
                ->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} user group(s)."
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user groups: ' . $e->getMessage()
            ], 500);
        }
    }

    public function import(Request $request): JsonResponse
    {
        if (!auth()->user()->can('users_groups') && !auth()->user()->can('create_users_groups')) {
            abort(403, 'Unauthorized access to import user groups.');
        }
        try {
            $request->validate([
                'import_file' => 'required|file|mimes:xlsx,xls,csv|max:2048'
            ]);

            $result = $this->userGroupService->import($request->file('import_file'));
            
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'imported_count' => $result['imported_count'],
                'errors' => $result['errors'] ?? []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportTemplate()
    {
        if (!auth()->user()->can('users_groups') && !auth()->user()->can('create_users_groups')) {
            abort(403, 'Unauthorized access to export template.');
        }
        try {
            return $this->userGroupService->exportTemplate();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to export template: ' . $e->getMessage());
        }
    }

    /**
     * Remove a user from the user group
     */
    public function removeUser(UserGroup $userGroup, User $user)
    {
        try {
            // Check if user group belongs to the authenticated merchant
            if ($userGroup->merchant_id !== auth()->user()->merchant_id) {
                return redirect()->back()->with('error', 'Unauthorized access to user group.');
            }

            // Check if user belongs to the authenticated merchant
            if ($user->merchant_id !== auth()->user()->merchant_id) {
                return redirect()->back()->with('error', 'Unauthorized access to user.');
            }

            $userGroup->users()->detach($user->id);
            
            // Log user removal from group
            $admin = auth()->user();
            $merchant = Merchant::find($admin->merchant_id);
            $userName = $admin->name;
            
            Log::create([
                'loggable_type' => Merchant::class,
                'loggable_id' => $merchant->id,
                'user_id' => Auth::user()->id,
                'user_type' => get_class(Auth::user()),
                'action' => 'user_removed_from_group',
                'description' => "Merchant removed user '{$user->name}' from group '{$userGroup->name}' by user '{$userName}'",
                'metadata' => json_encode([
                    'message' => "Merchant removed user '{$user->name}' from group '{$userGroup->name}' by user '{$userName}'",
                    'user_group_name' => $userGroup->name,
                    'user_group_id' => $userGroup->id,
                    'removed_user_name' => $user->name,
                    'removed_user_id' => $user->id,
                    'removed_by_user' => $userName,
                    'removed_at' => now()->toDateTimeString(),
                ]),
            ]);
            
            return redirect()
                ->route('merchant.user-groups.show', $userGroup)
                ->with('success', 'User removed from group successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to remove user from group: ' . $e->getMessage());
        }
    }
} 