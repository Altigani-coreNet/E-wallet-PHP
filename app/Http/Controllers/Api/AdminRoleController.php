<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Services\RoleService;
use App\Traits\ApiResponse;
use App\Traits\Select2Trait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminRoleController extends Controller
{
    use ApiResponse, Select2Trait;
    
    protected $roleService;
    protected $guardName = 'admin';

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Display a listing of roles
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $roles = $this->roleService->getAllRoles($request);
            return $this->SuccessMessage($roles);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch roles: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get roles data for DataTable
     */
    public function data(Request $request)
    {
        try {
            return $this->roleService->getDataTableData($request);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch roles data: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get roles for select dropdown.
     * With merchant_id: merchant staff roles (guard web). Otherwise admin roles.
     */
    public function select(Request $request): JsonResponse
    {
        try {
            if ($request->filled('merchant_id')) {
                $request->validate([
                    'merchant_id' => 'required|uuid|exists:merchants,id',
                ]);

                $query = \App\Models\Role::query()
                    ->where('guard_name', 'web')
                    ->where('merchant_id', $request->input('merchant_id'));

                if ($search = $request->input('search')) {
                    $query->where('name', 'like', '%' . $search . '%');
                }

                $records = $query
                    ->select('id', 'name as text')
                    ->orderBy('name')
                    ->limit(100)
                    ->get();

                return response()->json($records);
            }

            return $this->getSelect2DataInNormalSearch(
                $request,
                Role::where("guard_name", $this->guardName),
                ["name"]
            );
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch roles for select: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Store a newly created role
     */
    public function store(RoleRequest $request): JsonResponse
    {
        try {
            $role = $this->roleService->create($request->validated());
            return $this->SuccessMessage($role,  201);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create role: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Display the specified role
     */
    public function show($id): JsonResponse
    {
        try {
            $role = Role::where('guard_name', $this->guardName)->findOrFail($id);
            $rolePermissions = $this->roleService->getRolePermissions($id);
            
            return $this->SuccessMessage([
                'role' => $role,
                'permissions' => $rolePermissions,
            ]);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch role: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update the specified role
     */
    public function update(RoleRequest $request, $id): JsonResponse
    {
        try {
            $role = Role::where('guard_name', $this->guardName)->findOrFail($id);
            $role = $this->roleService->update($role, $request->validated());
            
            return $this->SuccessMessage($role,  200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update role: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified role
     */
    public function destroy($id): JsonResponse
    {
        try {
            $role = Role::where('guard_name', $this->guardName)->findOrFail($id);
            $this->roleService->delete($role);
            
            return $this->SuccessMessage( 'Role deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete role: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk delete roles
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $ids = explode(',', $request->ids);
            $this->roleService->bulkDelete($ids);
            
            return $this->SuccessMessage( 'Roles deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete roles: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get all permissions
     */
    public function permissions(Request $request): JsonResponse
    {
        try {
            $permissions = Permission::where('guard_name', $this->guardName)->get();
            return $this->SuccessMessage($permissions, 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch permissions: ' . $e->getMessage(), null, 500);
        }
    }
}

