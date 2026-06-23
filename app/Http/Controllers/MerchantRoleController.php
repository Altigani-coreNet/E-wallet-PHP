<?php

namespace App\Http\Controllers;

use App\Http\Resources\Select2Response;
use App\Traits\MessageManager;
use App\Traits\Select2Trait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use DB;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;

class MerchantRoleController extends Controller
{
    use AuthorizesRequests, MessageManager, Select2Trait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    function data()
    {
        if (!auth()->user()->can('view_roles')) {
            abort(403, 'Unauthorized access to view roles.');
        }
        $query = Role::withCount("Permissions as permission_count")
            ->where('guard_name', 'web')
            ->where('merchant_id', auth()->user()->merchant_id);

        return DataTables::of($query)
            ->addColumn('record_select', 'merchant.roles.data_table.record_select')
            ->editColumn("name", fn($item) => $item->name)
            ->editColumn('permission_count' , fn($item) => "<span class='badge badge-light-success'> " . $item->permission_count.' ' . "permissions </span>" )
            ->addColumn('actions', 'merchant.roles.data_table.actions')
            ->rawColumns(['record_select', 'actions', 'permission_count','status', "image"])
            ->toJson();
    }

    public function select(Request $request)
    {
        $query = Role::where("guard_name", "web")
            ->where('merchant_id', auth()->user()->merchant_id);
        
        return $this->getSelect2DataInNormalSearch(
            $request, 
            $query, 
            ["name"]
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application
     */
    public function index(Request $request)
    {
        if (!auth()->user()->can('view_roles')) {
            abort(403, 'Unauthorized access to view roles.');
        }
        return view('merchant.roles.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('create_roles')) {
            abort(403, 'Unauthorized access to create roles.');
        }
        // Get all permissions that the current user has
        $userPermissions = auth()->user()->getAllPermissions()->pluck('name')->toArray();
        
        // Filter permissions to only show those the user has
        $permission = Permission::where('guard_name', 'web')
            ->whereIn('name', $userPermissions)
            ->get();
            
        return view('merchant.roles.create', compact('permission'));
    }

    public function getRoleName(Request $request): JsonResponse
    {
        $ids = (explode(',', $request->ids));

        $response = Role::select('name', "id")
            ->whereIn("id", $ids)
            ->where('guard_name', 'web')
            ->where('merchant_id', auth()->user()->merchant_id)
            ->get();

        return response()->json(Select2Response::collection($response));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('create_roles')) {
            abort(403, 'Unauthorized access to create roles.');
        }
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permission' => 'required|array',
        ]);

        try {
            // Get all permissions that the current user has
            $userPermissions = auth()->user()->getAllPermissions()->pluck('name')->toArray();
            
            $role = Role::create([
                'name' => $request->name, 
                "guard_name" => 'web',
                'merchant_id' => auth()->user()->merchant_id
            ]);

            // Only assign permissions that the user has
            $validPermissions = Permission::whereIn('id', $request->input('permission'))
                ->where('guard_name', 'web')
                ->whereIn('name', $userPermissions) // Only permissions the user has
                ->pluck('name')
                ->toArray();

            $role->givePermissionTo($validPermissions);

            return redirect()->route('merchant.roles.index')
                ->with('success', 'Role created successfully');
        } catch (\Exception $exception) {
            $this->ErrorMessage($exception->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application
     */
    public function show($id)
    {
        if (!auth()->user()->can('view_roles')) {
            abort(403, 'Unauthorized access to view roles.');
        }
        $role = Role::where('guard_name', 'web')
            ->where('merchant_id', auth()->user()->merchant_id)
            ->findOrFail($id);
        
        // Get all permissions that the current user has
        $userPermissions = auth()->user()->getAllPermissions()->pluck('name')->toArray();
        
        // Filter role permissions to only show those the user has
        $rolePermissions = Permission::join("role_has_permissions", "role_has_permissions.permission_id", "=", "permissions.id")
            ->where("role_has_permissions.role_id", $id)
            ->where('permissions.guard_name', 'web')
            ->whereIn('permissions.name', $userPermissions)
            ->get();

        return view('merchant.roles.show', compact('role', 'rolePermissions'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application
     */
    public function edit($id)
    {
        if (!auth()->user()->can('edit_roles')) {
            abort(403, 'Unauthorized access to edit roles.');
        }
        $role = Role::where('guard_name', 'web')
            ->where('merchant_id', auth()->user()->merchant_id)
            ->findOrFail($id);
            
        // Get all permissions that the current user has
        $userPermissions = auth()->user()->getAllPermissions()->pluck('name')->toArray();
        
        // Filter permissions to only show those the user has
        $permission = Permission::where('guard_name', 'web')
            ->whereIn('name', $userPermissions)
            ->get();
        
        $rolePermissions = DB::table("role_has_permissions")
            ->where("role_has_permissions.role_id", $id)
            ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->all();

        return view('merchant.roles.edit', compact('role', 'permission', 'rolePermissions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('edit_roles')) {
            abort(403, 'Unauthorized access to edit roles.');
        }
        $request->validate([
            'name' => 'required',
            'permission' => 'required',
        ]);

        $role = Role::where('guard_name', 'web')
            ->where('merchant_id', auth()->user()->merchant_id)
            ->findOrFail($id);
        $role->name = $request->input('name');
        $role->save();

        // Get all permissions that the current user has
        $userPermissions = auth()->user()->getAllPermissions()->pluck('name')->toArray();

        // Only assign permissions that the user has
        $validPermissions = Permission::whereIn('id', $request->input('permission'))
            ->where('guard_name', 'web')
            ->whereIn('name', $userPermissions) // Only permissions the user has
            ->pluck('name')
            ->toArray();

        $role->syncPermissions($validPermissions);

        return redirect()->route('merchant.roles.index')
            ->with('success', 'Role updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('delete_roles')) {
            abort(403, 'Unauthorized access to delete roles.');
        }
        DB::table("roles")
            ->where('id', $id)
            ->where('guard_name', 'web')
            ->where('merchant_id', auth()->user()->merchant_id)
            ->delete();
            
        return redirect()->route('merchant.roles.index')
            ->with('success', 'Role deleted successfully');
    }
}
