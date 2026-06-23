<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    use ApiResponse;

    /**
     * Get all roles for current merchant
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            $query = Role::where('guard_name', 'web')
                ->where('merchant_id', $user->merchant_id);

            // Filter by module if provided
            if ($request->has('module') && $request->module) {
                $query->where('module', $request->module);
            }

            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Search
            if ($request->has('search') && $request->search) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            // Pagination
            $perPage = $request->get('per_page', 10);
            $roles = $query->withCount('permissions')->paginate($perPage);

            return $this->SuccessMessage([
                'data' => $roles->items(),
                'meta' => [
                    'current_page' => $roles->currentPage(),
                    'per_page' => $roles->perPage(),
                    'total' => $roles->total(),
                    'last_page' => $roles->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch roles: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Create new role
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
            'module' => 'nullable|in:pos,sales',
        ]);

        if ($validator->fails()) {
            return $this->ErrorMessage($validator->errors(), null, 422);
        }

        try {
            $user = $request->user();
            
            $role = Role::create([
                'name' => $request->name,
                'guard_name' => 'web',
                'merchant_id' => $user->merchant_id,
                'module' => $request->module, // Store module (pos, sales, or null for all)
            ]);

            // Sync permissions
            $permissions = Permission::whereIn('id', $request->permissions)
                ->where('guard_name', 'web')
                ->pluck('name');

            $role->syncPermissions($permissions);

            return $this->SuccessMessage([
                'message' => 'Role created successfully',
                'role' => $role->load('permissions')
            ], 201);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create role: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get single role
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $role = Role::where('id', $id)
                ->where('merchant_id', $user->merchant_id)
                ->with('permissions')
                ->firstOrFail();

            return $this->SuccessMessage($role);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Role not found', null, 404);
        }
    }

    /**
     * Update role
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name,' . $id,
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
            'module' => 'nullable|in:pos,sales',
        ]);

        if ($validator->fails()) {
            return $this->ErrorMessage($validator->errors(), null, 422);
        }

        try {
            $user = $request->user();
            
            $role = Role::where('id', $id)
                ->where('merchant_id', $user->merchant_id)
                ->firstOrFail();

            $role->update([
                'name' => $request->name,
                'module' => $request->module, // Update module
            ]);

            // Sync permissions
            $permissions = Permission::whereIn('id', $request->permissions)
                ->where('guard_name', 'web')
                ->pluck('name');

            $role->syncPermissions($permissions);

            return $this->SuccessMessage([
                'message' => 'Role updated successfully',
                'role' => $role->load('permissions')
            ]);

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update role: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Delete role
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $role = Role::where('id', $id)
                ->where('merchant_id', $user->merchant_id)
                ->firstOrFail();

            $role->delete();

            return $this->SuccessMessage('Role deleted successfully');

        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete role: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get all available permissions (old method - from database)
     */
    public function permissions(Request $request)
    {
        try {
            $permissions = Permission::where('guard_name', 'web')
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
                ->groupBy(function($permission) {
                    // Group by permission prefix (e.g., users, roles, etc.)
                    $parts = explode('_', $permission->name);
                    return count($parts) > 1 ? $parts[1] : 'general';
                });

            return $this->SuccessMessage($permissions);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch permissions: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get permissions from database based on module header
     */
    public function getPermissionsList(Request $request)
    {
        try {
            // Get module from header or default to 'merchant' (both pos and sales)
            $module = $request->header('X-Module', 'merchant');
            
            \Log::info('Fetching permissions for module: ' . $module);

            // Fetch permissions from database
            $query = Permission::where('guard_name', 'web');

            // Filter by module
            if ($module === 'merchant') {
                // Get both POS and Sales permissions
                $query->where(function($q) {
                    $q->where('name', 'like', 'pos.%')
                      ->orWhere('name', 'like', 'sales.%');
                });
            } elseif ($module === 'pos') {
                // Get only POS permissions
                $query->where('name', 'like', 'pos.%');
            } elseif ($module === 'sales') {
                // Get only Sales permissions
                $query->where('name', 'like', 'sales.%');
            } else {
                // Get permissions for specific module
                $query->where('name', 'like', $module . '.%');
            }

            $permissions = $query->select('id', 'name', 'display_name', 'guard_name')
                ->orderBy('name')
                ->get();

            \Log::info('Found ' . $permissions->count() . ' permissions for module: ' . $module);

            return $this->SuccessMessage([
                'module' => $module,
                'permissions' => $permissions,
                'total' => $permissions->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching permissions: ' . $e->getMessage());
            return $this->ErrorMessage('Failed to fetch permissions: ' . $e->getMessage(), null, 500);
        }
    }
}

