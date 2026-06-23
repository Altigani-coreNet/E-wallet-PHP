<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Log;
use App\Repositories\AdminRepository;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AdminService
{
    use HasFiles;
    protected $adminRepository;

    public function __construct(AdminRepository $adminRepository)
    {
        $this->adminRepository = $adminRepository;
    }

    /**
     * Create a new admin
     */
    public function create(array $data): Admin
    {
        // Hash the password
        $data['password'] = Hash::make($data['password']);
        // Normalize custom_region (checkbox may be missing when unchecked)
        $data['custom_region'] = isset($data['custom_region']) && (bool)$data['custom_region'] ? 1 : 0;

        // dd($data);
        $regions = isset($data['regions']) ? (array)$data['regions'] : [];
        $roles = isset($data['roles']) ? (array)$data['roles'] : [];

        unset($data['regions']);
        unset($data['roles']);

        $admin = $this->adminRepository->create($data);

        if ($data['custom_region'] == 1 && !empty($regions)) {
            $this->syncCountriesWithUuids($admin, $regions);
        }
        
        if (!empty($roles)) {
            $roles = Role::whereIn('id', $roles)->where('guard_name', 'admin')->get();
             $admin->syncRoles($roles); 
            }

        // logs
        $this->logAction($admin, 'created', null, $admin->toArray(), [
            'message' => 'Admin created',
        ]);
        if (!empty($roles)) {
            $this->logAction($admin, 'roles_updated', null, ['roles' => $roles->pluck('id')], [
                'message' => 'Roles assigned on create',
            ]);
        }
        if ($data['custom_region'] == 1 && !empty($regions)) {
            $this->logAction($admin, 'regions_updated', null, ['regions' => $regions], [
                'message' => 'Regions assigned on create',
            ]);
        }

        return $admin;
    }

    /**
     * Update an existing admin
     */
    public function update(Admin $admin, array $data): Admin
    {
        $beforeUpdate = $admin->toArray();
        // Hash the password if provided
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

       

        // Normalize custom_region on update as well (unchecked => 0)
        $data['custom_region'] = isset($data['custom_region']) && (bool)$data['custom_region'] ? 1 : 0;

        $regions = isset($data['regions']) ? (array)$data['regions'] : null;
        $roles = isset($data['roles']) ? (array)$data['roles'] : null;
        unset($data['regions']);
        unset($data['roles']);

        $admin = $this->adminRepository->update($admin, $data);

        if ($data['custom_region'] == 1 && is_array($regions)) {
            $this->syncCountriesWithUuids($admin, $regions);
        } elseif ($data['custom_region'] == 0) {
            DB::table('admin_countries')->where('admin_id', $admin->id)->delete();
        }
        if (is_array($roles)) {
            $roles = Role::whereIn('id', $roles)->where('guard_name', 'admin')->get();
            $admin->syncRoles($roles); 
        }

        // logs
        $afterUpdate = $admin->toArray();
        $this->logAction($admin, 'updated', $beforeUpdate, $afterUpdate, [
            'message' => 'Admin updated',
        ]);
        if (array_key_exists('status', $beforeUpdate) && $beforeUpdate['status'] != $afterUpdate['status']) {
            $this->logAction($admin, 'status_changed', ['status' => $beforeUpdate['status']], ['status' => $afterUpdate['status']], [
                'message' => 'Status changed',
            ]);
        }
        if (isset($roles)) {
            $this->logAction($admin, 'roles_updated', null, ['roles' => $roles->pluck('id')], [
                'message' => 'Roles updated',
            ]);
        }
        if ($data['custom_region'] == 1 && is_array($regions)) {
            $this->logAction($admin, 'regions_updated', null, ['regions' => $regions], [
                'message' => 'Regions updated',
            ]);
        }

        return $admin;
    }

    /**
     * Delete an admin
     */
    public function delete(Admin $admin): bool
    {
        // Delete profile image if exists
        if ($admin->profile_image) {
            Storage::delete($admin->profile_image);
        }

        // log before delete
        $this->logAction($admin, 'deleted', $admin->toArray(), null, [
            'message' => 'Admin deleted',
        ]);

        return $this->adminRepository->delete($admin);
    }

    /**
     * Bulk delete admins
     */
    public function bulkDelete(array $ids): bool
    {
        $admins = Admin::whereIn('id', $ids)->get();
        
        foreach ($admins as $admin) {
            // Delete profile image if exists
            if ($admin->profile_image) {
                Storage::delete($admin->profile_image);
            }
            
            // Delete the admin
            $admin->delete();
        }
        
        return true;
    }

    /**
     * Get all admins for API
     */
    public function getAllAdmins(Request $request)
    {
        $query = Admin::with('roles');

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchValue = $request->search;
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhere('email', 'like', "%{$searchValue}%")
                  ->orWhere('phone', 'like', "%{$searchValue}%");
            });
        }

        // Status filter
        if ($request->has('status')) {
            $status = $request->status === 'active' ? 1 : 0;
            $query->where('status', $status);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        return $query->paginate($perPage);
    }

    /**
     * Get admins for select dropdown
     */
    public function getAdminsForSelect(Request $request)
    {
        $query = Admin::select('id', 'name', 'email');

        // Search functionality for select2
        if ($request->has('search') && !empty($request->search)) {
            $searchValue = $request->search;
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhere('email', 'like', "%{$searchValue}%");
            });
        }

        // Only active admins
        $query->where('status', 1);

        return $query->get();
    }

    /**
     * Get DataTable data for admins
     */
    public function getDataTableData(Request $request)
    {
        $query = Admin::withCountry()->with(['roles', 'countries']);

        if ($request->has('search') && !is_array($request->search) && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%")
                    ->orWhere('name', 'like', "%$search%")
                    ->orWhere('id', 'like', "$search%");
            });
        }

        if ($request->has('status') && !empty($request->status) && $request->status != null) {
            $status = match ($request->status) {
                'active' => 1,
                default => 0,
            };
            $query->where('status', $status);
        }

        if ($request->has('country_id') && !is_null($request->country_id)) {
            $query->where('country_id', $request->country_id);
        }

        if ($request->has('from_date') && $request->from_date && !is_null($request->from_date)) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date && !is_null($request->to_date)) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $orderBy = $request->get('order_by', 'created_at');
        $orderDirection = strtolower($request->get('order_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedOrderColumns = ['name', 'email', 'custom_region', 'status', 'created_at'];

        if (!in_array($orderBy, $allowedOrderColumns, true)) {
            $orderBy = 'created_at';
        }

        $query->orderBy($orderBy, $orderDirection);

        $perPage = (int) $request->get('per_page', 15);
        if ($perPage <= 0) {
            $perPage = 15;
        }

        return $query->paginate($perPage);
    }

    /**
     * Upload profile image
     */
    private function uploadProfileImage($image): string
    {
        $path = $image->store('admin-profiles', 'public');
        return 'storage/' . $path;
    }

    /**
     * Get action buttons for DataTable
     */
    private function getActionButtons(Admin $admin): string
    {
        $buttons = '<div class="btn-group" role="group">';
        
        // Show button
        $buttons .= '<a href="' . route('admins.show', $admin->id) . '" class="btn btn-sm btn-info" title="View"><i class="fas fa-eye"></i></a>';
        
        // Edit button
        $buttons .= '<a href="' . route('admins.edit', $admin->id) . '" class="btn btn-sm btn-primary" title="Edit"><i class="fas fa-edit"></i></a>';
        
        // Delete button
        $buttons .= '<form method="POST" action="' . route('admins.destroy', $admin->id) . '" style="display:inline;" onsubmit="return confirm(\'Are you sure?\')">';
        $buttons .= csrf_field();
        $buttons .= method_field('DELETE');
        $buttons .= '<button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>';
        $buttons .= '</form>';
        
        $buttons .= '</div>';
        
        return $buttons;
    }

    /**
     * Sync countries with UUID generation for pivot table.
     */
    private function syncCountriesWithUuids(Admin $admin, array $countryIds): void
    {
        DB::table('admin_countries')->where('admin_id', $admin->id)->delete();

        if (!empty($countryIds)) {
            $now = now();
            $insertData = array_map(function ($countryId) use ($admin, $now) {
                return [
                    'id' => (string) Str::uuid(),
                    'admin_id' => $admin->id,
                    'country_id' => $countryId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $countryIds);

            DB::table('admin_countries')->insert($insertData);
        }
    }

    private function logAction(Admin $admin, string $action, $old = null, $new = null, array $metadata = []): void
    {
        try {
            $user = Auth::user();
            Log::create([
                'action' => $action,
                'old_values' => $old,
                'new_values' => $new,
                'metadata' => $metadata,
                'loggable_id' => $admin->id,
                'loggable_type' => Admin::class,
                'user_id' => $user?->id,
                'user_type' => $user ? get_class($user) : null,
            ]);
        } catch (\Throwable $e) {
            // ignore logging failures
        }
    }
} 