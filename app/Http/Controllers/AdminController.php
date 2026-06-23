<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Services\AdminService;
use App\Http\Requests\AdminRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Traits\ApiResponse;
use App\Traits\HasFiles;

class AdminController extends Controller
{
    use ApiResponse, HasFiles;

    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('admins.index');
    }

    public function changeStatus(Admin $admin){
        $admin->changeStatus();
        return redirect()->back()->with('success', 'Admin status changed successfully');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admins.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AdminRequest $request): RedirectResponse
    {
        try {
            // dd($request->all());
            \DB::beginTransaction();
            $requestData = $request->validated();
            if($request->hasFile('profile_image')){
            $requestData['profile_image'] = $this->uploadImageAndGetFileName($request, "profile_image", "admin-profiles");
            }
        // dd($requestData);
            $this->adminService->create($requestData);
            \DB::commit();
            return redirect()->route('admins.index')->with('success', 'Admin created successfully');
        } catch (\Exception $e) {
            \DB::rollBack();
            // dd($e->getMessage());
            return redirect()->back()->with('error', 'Failed to create admin: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Admin $admin): View
    {
        if(request()->has('status')){
            $this->adminService->changeStatus($admin);
        }
                   return view('admins.show', compact('admin'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Admin $admin): View
    {
        return view('admins.edit', compact('admin'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AdminRequest $request, Admin $admin): RedirectResponse
    {
        try {
            \DB::beginTransaction();
            $requestData = $request->validated();
            if($request->hasFile('profile_image')){
            $requestData['profile_image'] = $this->uploadImageAndGetFileName($request, "profile_image", "admin-profiles");
            }
            // dd($requestData);
            $this->adminService->update($admin, $requestData);
            \DB::commit();
            return redirect()->route('admins.index')->with('success', 'Admin updated successfully');
        } catch (\Exception $e) {
            \DB::rollBack();
            // dd($e->getMessage());
            return redirect()->back()->with('error', 'Failed to update admin: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Admin $admin): RedirectResponse
    {
        try {
            $this->adminService->delete($admin);
            return redirect()->route('admins.index')->with('success', 'Admin deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete admin: ' . $e->getMessage());
        }
    }

    /**
     * Get admins data for DataTables
     */
    public function data(Request $request)
    {
        // dd($request->all());
        return $this->adminService->getDataTableData($request);
    }

    /**
     * Get admins for select dropdown
     */
    public function select(Request $request)
    {
        $admins = Admin::where('name', 'like', '%' . $request->search . '%')
            ->orWhere('email', 'like', '%' . $request->search . '%')
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json($admins);
    }

    /**
     * Bulk delete admins
     */
    public function bulkDelete(Request $request)
    {
        try {
            $ids = explode(',', $request->ids);
            $this->adminService->bulkDelete($ids);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }
} 