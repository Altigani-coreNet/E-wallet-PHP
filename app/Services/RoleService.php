<?php

namespace App\Services;

use App\Repositories\RoleRepository;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class RoleService
{
    protected $roleRepository;
    protected $guardName = 'admin';

    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    /**
     * Create a new role
     */
    public function create(array $data): Role
    {
        $role = $this->roleRepository->create([
            'name' => $data['name'],
            'guard_name' => $this->guardName,
        ]);

        // Assign permissions
        if (!empty($data['permissions'])) {
            $validPermissions = Permission::whereIn('id', $data['permissions'])
                ->where('guard_name', $this->guardName)
                ->pluck('name')
                ->toArray();

            $role->givePermissionTo($validPermissions);
        }

        return $role;
    }

    /**
     * Update an existing role
     */
    public function update(Role $role, array $data): Role
    {
        $role = $this->roleRepository->update($role, [
            'name' => $data['name'],
        ]);

        // Sync permissions
        if (isset($data['permissions'])) {
            $validPermissions = Permission::whereIn('id', $data['permissions'])
                ->where('guard_name', $this->guardName)
                ->pluck('name')
                ->toArray();

            $role->syncPermissions($validPermissions);
        }

        return $role;
    }

    /**
     * Delete a role
     */
    public function delete(Role $role): bool
    {
        return $this->roleRepository->delete($role);
    }

    /**
     * Bulk delete roles
     */
    public function bulkDelete(array $ids): bool
    {
        DB::table("roles")
            ->whereIn('id', $ids)
            ->where('guard_name', $this->guardName)
            ->delete();
        
        return true;
    }

    /**
     * Get all roles for API
     */
    public function getAllRoles(Request $request)
    {
        $query = Role::withCount('permissions')
            ->where('guard_name', $this->guardName);

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchValue = $request->search;
            $query->where('name', 'like', "%{$searchValue}%");
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        return $query->paginate($perPage);
    }

    /**
     * Get DataTable data for roles
     */
    public function getDataTableData(Request $request)
    {
        $query = Role::withCount('permissions')
            ->where('guard_name', $this->guardName);

        // Search support
        if ($request->has('search') && !empty($request->search)) {
            $searchValue = is_array($request->search) ? ($request->search['value'] ?? '') : $request->search;
            if (!empty($searchValue)) {
                $query->where('name', 'like', "%{$searchValue}%");
            }
        }

        return DataTables::of($query)
            ->addColumn('id', fn($role) => $role->id)
            ->addColumn('name', fn($role) => $role->name)
            ->addColumn('permissions_count', fn($role) => $role->permissions_count)
            ->addColumn('created_at', fn($role) => $role->created_at->format('Y-m-d H:i:s'))
            ->make(true);
    }

    /**
     * Get role permissions
     */
    public function getRolePermissions(int $roleId): array
    {
        $role = Role::where('guard_name', $this->guardName)->findOrFail($roleId);
        
        $rolePermissions = Permission::join("role_has_permissions", "role_has_permissions.permission_id", "=", "permissions.id")
            ->where("role_has_permissions.role_id", $roleId)
            ->where('permissions.guard_name', $this->guardName)
            ->pluck('permissions.id')
            ->toArray();

        return $rolePermissions;
    }
}


