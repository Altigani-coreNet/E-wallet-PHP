<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminRequest;
use App\Http\Resources\AdminAdminResponse;
use App\Models\Admin;
use App\Services\AdminService;
use App\Traits\ApiResponse;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminAdminController extends Controller
{
    use ApiResponse, HasFiles;
    
    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Display a listing of admins
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $admins = $this->adminService->getAllAdmins($request);
            return $this->SuccessMessage($admins);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch admins: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get admins data for DataTable
     */
    public function data(Request $request)
    {
        try {
            $admins = $this->adminService->getDataTableData($request);
            return $this->SuccessMessage(AdminAdminResponse::collection($admins));
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch admins data: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get admins for select dropdown
     */
    public function select(Request $request): JsonResponse
    {
        try {
            $admins = Admin::where('name', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%')
                ->limit(10)
                ->get(['id', 'name', 'email']);

            return $this->SuccessMessage($admins);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch admins for select: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Store a newly created admin
     */
    public function store(AdminRequest $request): JsonResponse
    {
        try {
            \DB::beginTransaction();
            
            $requestData = $request->validated();
            
            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $requestData['profile_image'] = $this->uploadImageAndGetFileName($request, "profile_image", "admin-profiles");
            }
            
            // dd('here');
            $admin = $this->adminService->create($requestData);
            
            \DB::commit();
            return $this->SuccessMessage($admin, 201);
        } catch (\Exception $e) {
            \DB::rollBack();
            return $this->ErrorMessage('Failed to create admin: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Display the specified admin
     */
    public function show($id): JsonResponse
    {
        try {
            $admin = Admin::with(['roles', 'countries', 'country'])->findOrFail($id);
            return $this->SuccessMessage(new AdminAdminResponse($admin));
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch admin: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Update the specified admin
     */
    public function update(AdminRequest $request, $id): JsonResponse
    {
        try {
            \DB::beginTransaction();
            
            $admin = Admin::findOrFail($id);
            $requestData = $request->validated();
            
            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $requestData['profile_image'] = $this->uploadImageAndGetFileName($request, "profile_image", "admin-profiles");
            }
            
            $admin = $this->adminService->update($admin, $requestData);
            
            \DB::commit();
            return $this->SuccessMessage($admin, 200);
        } catch (\Exception $e) {
            \DB::rollBack();
            return $this->ErrorMessage('Failed to update admin: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified admin
     */
    public function destroy($id): JsonResponse
    {
        try {
            $admin = Admin::findOrFail($id);
            $this->adminService->delete($admin);
            
            return $this->SuccessMessage('Admin deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete admin: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk delete admins
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $ids = explode(',', $request->ids);
            $this->adminService->bulkDelete($ids);
            
            return $this->SuccessMessage('Admins deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete admins: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Change admin status
     */
    public function changeStatus($id): JsonResponse
    {
        try {
            $admin = Admin::findOrFail($id);
            $currentStatus = strtolower(trim((string) $admin->status));
            $admin->status = $currentStatus === 'active' ? 'inactive' : 'active';
            $admin->save();
            
            return $this->SuccessMessage($admin->fresh(), 200);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to change admin status: ' . $e->getMessage(), null, 500);
        }
    }
}

