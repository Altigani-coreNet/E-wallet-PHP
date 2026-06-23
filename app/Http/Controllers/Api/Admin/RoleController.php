<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $roles = $this->getAllRoles($request);
            return $this->SuccessMessage($roles);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch roles: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:roles,name',
                'permissions' => 'array'
            ]);

            $role = Role::create([
                'name' => $request->name,
                'guard_name' => 'admin',
                'country_id' => $request->user()->country_id,
            ]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            return $this->SuccessMessage($role, 201);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create role: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role): JsonResponse
    {
        try {
            $role->load('permissions');
            return $this->SuccessMessage($role);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch role: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
                'permissions' => 'array'
            ]);

            $role->update([
                'name' => $request->name
            ]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            return $this->SuccessMessage($role);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update role: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role): JsonResponse
    {
        try {
            $role->delete();
            return $this->SuccessMessage(['message' => 'Role deleted successfully']);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete role: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get roles data for DataTables
     */
    public function data(Request $request): JsonResponse
    {
        try {
            $query = Role::withCount('permissions as permission_count')
                ->where('guard_name', 'admin');

            $data = DataTables::of($query)
                ->addColumn('permission_count', function ($role) {
                    return "<span class='badge badge-light-success'>" . $role->permission_count . ' permissions</span>';
                })
                ->rawColumns(['permission_count'])
                ->toJson();

            return $this->SuccessMessage($data);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch roles data: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get roles for select dropdown
     */
    public function select(Request $request): JsonResponse
    {
        try {
            $query = Role::where('guard_name', 'admin');

            if ($request->has('search') && !empty($request->search)) {
                $searchValue = $request->search;
                $query->where('name', 'like', "%{$searchValue}%");
            }

            $roles = $query->select('id', 'name')->get();
            return $this->SuccessMessage($roles);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch roles for select: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get all roles for API
     */
    private function getAllRoles(Request $request)
    {
        $query = Role::with('permissions')->where('guard_name', 'admin');

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchValue = $request->search;
            $query->where('name', 'like', "%{$searchValue}%");
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        return $query->paginate($perPage);
    }
} 