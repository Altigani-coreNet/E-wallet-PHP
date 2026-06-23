<?php

namespace App\Http\Controllers;

use App\Models\UserGroup;
use App\Models\Merchant;
use App\Models\Branch;
use App\Models\User;
use App\Services\UserGroupService;
use App\Traits\Select2Trait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class UserGroupController extends Controller
{
    use Select2Trait;
    public function __construct(private UserGroupService $userGroupService)
    {
        //
    }


    public function select(Request $request){
        $query = UserGroup::query();
        
        // Filter by merchant if provided
        if ($request->has('merchant_id') && $request->merchant_id) {
            $query->where('merchant_id', $request->merchant_id);
        } else {
            // return response()->json([]);
        }

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
        
       $query->withCountry();
    
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
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('user_groups.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('user_groups.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'merchant_id' => 'required|exists:merchants,id',
            'branch_id' => 'nullable|exists:branches,id',
            'description' => 'nullable|string',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        try {
            DB::beginTransaction();

            // Create the user group
            $userGroup = UserGroup::create([
                'name' => $request->name,
                'group_id' => UserGroup::generateGroupId(),
                'merchant_id' => $request->merchant_id,
                'country_id' => Merchant::select('country_id')->find($request->merchant_id)->country_id,
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



            DB::commit();

            return redirect()->route('user-groups.index')
                ->with('success', 'User group created successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()
                ->with('error', 'Failed to create user group: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(UserGroup $userGroup)
    {
        $userGroup->load(['merchant', 'branch', 'users']);
        return view('user_groups.show', compact('userGroup'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserGroup $userGroup)
    {
        $userGroup->load(['merchant', 'branch', 'users']);
        
        // Get users for the selected merchant
        $merchantUsers = User::where('merchant_id', $userGroup->merchant_id)
            ->active()
            ->get();

        // Get branches for the selected merchant
        $merchantBranches = Branch::where('merchant_id', $userGroup->merchant_id)
            ->active()
            ->get();

        return view('user_groups.edit', compact('userGroup', 'merchantUsers', 'merchantBranches'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserGroup $userGroup)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'merchant_id' => 'required|exists:merchants,id',
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
                'merchant_id' => $request->merchant_id,
                'branch_id' => $request->branch_id,
                'description' => $request->description,
            ]);

            // Get old and new user IDs for logging
            $oldUserIds = $userGroup->users()->pluck('id')->toArray();
            $newUserIds = $request->user_ids;
            
            $usersToRemove = array_diff($oldUserIds, $newUserIds);
            $usersToAdd = array_diff($newUserIds, $oldUserIds);

            // Sync users to the group
            if ($request->has('user_ids')) {
                $userGroup->users()->sync($request->user_ids);
            }

            // Log user removals from group
            $admin = auth()->user();
            foreach ($usersToRemove as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $user->logUserGroupRemoval($admin, $userGroup);
                }
            }

            // Log user additions to group
            foreach ($usersToAdd as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $user->logUserGroupAssignment($admin, $userGroup);
                }
            }



            DB::commit();

            return redirect()->route('user-groups.index')
                ->with('success', 'User group updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()
                ->with('error', 'Failed to update user group: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserGroup $userGroup)
    {
        try {
            $userGroup->delete();
            return redirect()->route('user-groups.index')
                ->with('success', 'User group deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete user group: ' . $e->getMessage());
        }
    }

    /**
     * Get users for a specific merchant (AJAX)
     */
    public function getMerchantUsers(Request $request)
    {
        $merchantId = $request->merchant_id;
        
        $users = User::where('merchant_id', $merchantId)
            ->active()
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }



    /**
     * Get branches for a specific merchant (AJAX)
     */
    public function getMerchantBranches(Request $request)
    {
        $merchantId = $request->merchant_id;
        
        $branches = Branch::where('merchant_id', $merchantId)
            ->active()
            ->get(['id', 'name']);

        return response()->json($branches);
    }

    /**
     * Toggle the active status of a user group
     */
    public function toggleStatus(UserGroup $userGroup)
    {
        try {
            $userGroup->update([
                'is_active' => !$userGroup->is_active
            ]);

            $status = $userGroup->is_active ? 'activated' : 'deactivated';
            return redirect()->route('user-groups.index')
                ->with('success', "User group {$status} successfully.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update user group status: ' . $e->getMessage());
        }
    }

    /**
     * Get data for DataTables
     */
    public function data(Request $request): JsonResponse
    {
        $query = UserGroup::withCountry()->with(['merchant', 'branch', 'users']);

        if ($request->has('search') && !is_array($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('group_id', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%")
                    ->orWhereHas('merchant', function ($merchantQuery) use ($search) {
                        $merchantQuery->where('name', 'like', "%$search%");
                    });
            });
        }

        if ($request->has('status')) {
            $status = match ($request->status) {
                "active" => 1,
                default => 0
            };

            $query->where('is_active', $status);
        }

        if ($request->has('merchant_id')) {
            $query->where('merchant_id', request()->merchant_id);
        }

        if ($request->has('country_id') && !empty($request->country_id)) {
            $query->where('country_id', $request->country_id);
        }

        if ($request->has('from_date') && !empty($request->from_date)) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && !empty($request->to_date)) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        return DataTables::of($query)
            // ->addColumn('record_select', 'user_groups.data_table.record_select')
            ->addColumn('record_select', function ($group) {
                return view('user_groups.data_table.record_select', compact('group'));
            })
            ->addColumn('actions', function ($group) {
                return view('user_groups.data_table.actions', compact('group'));
            })
            ->editColumn("status", fn($item) => $item->getStatusWithSpan())
            ->editColumn("name", fn($item) => "<div> $item->name <br/> {$item->group_id}</div>")
            ->editColumn("merchant_id", fn($item) => $item->merchant ? $item->merchant->name : 'N/A')
            ->editColumn("branch_id", fn($item) => $item->branch ? $item->branch->name : 'N/A')
            ->editColumn("users_count", fn($item) => "<span class='badge badge-light-primary badge-sm '>{$item->users->count()}</span>")
            ->editColumn("created_at", fn($item) => $item->created_at->format('M d, Y H:i'))
            ->editColumn("country", fn($item) => $item->country ? $item->country->name : 'N/A')
            ->rawColumns(['record_select', 'actions', 'name', 'status', 'merchant_id', 'branch_id', 'users_count', 'created_at'])
            ->toJson();
    }

    /**
     * Bulk delete user groups
     */
    public function bulkDelete(Request $request): JsonResponse
    {
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

            // Delete user groups 
            $deletedCount = UserGroup::whereIn('id', $ids)->delete();

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

    /**
     * Import user groups from file
     */
    public function import(Request $request): JsonResponse
    {
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

    /**
     * Export template for user groups import
     */
    public function exportTemplate()
    {
        try {
            return $this->userGroupService->exportTemplate();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to export template: ' . $e->getMessage());
        }
    }

    /**
     * Remove a specific user from the user group.
     */
    public function removeUser(UserGroup $userGroup, User $user)
    {
        try {
            $userGroup->users()->detach($user->id);
            
            // Log user removal from group
            $admin = auth()->user();
            $user->logUserGroupRemoval($admin, $userGroup);
            
            return redirect()
                ->route('user-groups.show', $userGroup)
                ->with('success', 'User removed from group successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to remove user from group: ' . $e->getMessage());
        }
    }
} 