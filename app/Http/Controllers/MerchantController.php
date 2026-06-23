<?php

namespace App\Http\Controllers;

use App\Http\Requests\MerchantRequest;
use App\Models\Merchant;
use App\Services\MerchantService;
use App\Traits\MessageManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MerchantController extends Controller
{
    use MessageManager;

    public function __construct(private MerchantService $merchantService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->merchantService->index();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return $this->merchantService->create();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MerchantRequest $request)
    {
        try {
            $this->merchantService->store($request);
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('translation.merchant_added_successfully')
                ]);
            }
            
            $this->SuccessMessage(__('translation.merchant_added_successfully'));
            return redirect()->route('merchants.index');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: __('translation.something_went_wrong')
                ], 422);
            }
            
            $this->ErrorMessage($e->getMessage() ?: __('translation.something_went_wrong'));
            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Merchant $merchant)
    {
        if (request()->has('status')) {
            return $this->merchantService->changeStatus($merchant);
        }
        $merchant->load([
            "LatestLogs" => function ($query) {
                $query->with("loggable:id", "User:id,name");
            }
        ]);
        return $this->merchantService->show($merchant);
    }

    public function sections(Merchant $merchant, string $tab = 'overview')
    {
        // dd($tab);
        $data =  $this->merchantService->sections($merchant, $tab);
        return $tab == 'overview' ? view('merchants.show', $data) : view('merchants.' . $tab, $data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Merchant $merchant)
    {
        return $this->merchantService->edit($merchant);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MerchantRequest $request, Merchant $merchant)
    {
        try {
            $this->merchantService->update($request, $merchant);
            $this->SuccessMessage("Merchant updated successfully");
            return redirect()->route('merchants.index');
        } catch (\Exception $e) {
            // dd($e->getMessage());
            $this->ErrorMessage("Something went wrong");
            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Merchant $merchant)
    {
        try {
            $this->merchantService->destroy($merchant);
            $this->SuccessMessage("Merchant deleted successfully");
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            $this->ErrorMessage("Something went wrong");
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Get DataTable data
     */
    public function data(Request $request): JsonResponse
    {
        return $this->merchantService->data($request);
    }

    /**
     * Export filtered merchants as CSV
     */
    public function export(Request $request)
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'status' => $request->get('status'),
                'country_id' => $request->get('country_id'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ];

            return $this->merchantService->export($filters);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export merchants: ' . $e->getMessage());
        }
    }

    /**
     * Download import template with foreign keys
     */
    public function exportTemplate()
    {
        try {
            return $this->merchantService->exportTemplate();
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate template: ' . $e->getMessage());
        }
    }

    /**
     * Preview import data before saving
     */
    public function importPreview(Request $request)
    {
        try {
            $request->validate([
                'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
            ]);

            $file = $request->file('import_file');
            $preview = $this->merchantService->importPreview($file);

            return response()->json([
                'success' => true,
                'data' => $preview
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Preview failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Import merchants from file
     */
    public function import(Request $request)
    {
        try {
            $request->validate([
                'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
            ]);

            $file = $request->file('import_file');
            $result = $this->merchantService->import($file);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'imported_count' => $result['imported_count'],
                    'failed_count' => $result['failed_count'],
                    'errors' => $result['errors']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete merchants
     */
    public function bulkDelete(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|string'
            ]);

            $ids = explode(',', $request->ids);
            
            foreach ($ids as $id) {
                $merchant = Merchant::find($id);
                if ($merchant) {
                    $merchant->delete();
                }
            }

            return response()->json([
                'success' => true,
                'message' => __('translation.merchants_deleted_successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete merchants: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get merchants for select dropdown
     */
    public function select(Request $request): JsonResponse
    {
        $search = $request->get('search');
        
        $query = Merchant::withCountry();
        
        // Apply search filter if provided
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', '%' . $search . '%')
                  ->orWhere('owner_name', 'like', '%' . $search . '%')
                  ->orWhere('name', 'like', '%' . $search . '%');
            });
        }
        
        // Get merchants with business name and owner name
        $merchants = $query->select('id', 'business_name', 'owner_name', 'name')
            ->orderBy('business_name', 'asc')
            ->limit(10)
            ->get();
        
        // Format the response to show "business_name - owner_name"
        $formattedMerchants = $merchants->map(function ($merchant) {
            $businessName = $merchant->business_name ?: $merchant->name;
            $ownerName = $merchant->owner_name ?: 'N/A';
            
            return [
                'id' => $merchant->id,
                'text' => $businessName . ' - ' . $ownerName,
                'business_name' => $businessName,
                'owner_name' => $ownerName
            ];
        });
        
        return response()->json($formattedMerchants);
    }

    /**
     * Reject merchant application
     */
    public function approve(Request $request, Merchant $merchant)
    {
        try {
            $this->merchantService->approve($request, $merchant);
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('translation.merchant_approved_successfully')
                ]);
            }
            
            $this->SuccessMessage(__('translation.merchant_approved_successfully'));
            return redirect()->back();
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: __('translation.something_went_wrong')
                ], 422);
            }
            
            $this->ErrorMessage($e->getMessage() ?: __('translation.something_went_wrong'));
            return redirect()->back();
        }
    }

    public function reject(Request $request, Merchant $merchant)
    {
        try {
            $request->validate([
                'rejection_reason' => 'required|string|min:10',
                'invalid_fields' => 'nullable|array'
            ]);

            // Get selected invalid fields
            $invalidFields = $request->invalid_fields ?? [];

            // Perform rejection (persists rejection record inside service)
            $this->merchantService->reject($merchant, $request->rejection_reason, $invalidFields);
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('translation.merchant_rejected_successfully')
                ]);
            }
            
            $this->SuccessMessage(__('translation.merchant_rejected_successfully'));
            return redirect()->route('merchants.index');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: __('translation.something_went_wrong')
                ], 422);
            }
            
            $this->ErrorMessage($e->getMessage() ?: __('translation.something_went_wrong'));
            return redirect()->back();
        }
    }

    /**
     * Suspend merchant
     */
    public function suspend(Request $request, Merchant $merchant)
    {
        try {
            $request->validate([
                'suspension_reason' => 'required|string|min:10'
            ]);

            $this->merchantService->suspend($merchant, $request->suspension_reason);
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('translation.merchant_suspended_successfully')
                ]);
            }
            
            $this->SuccessMessage(__('translation.merchant_suspended_successfully'));
            return redirect()->route('merchants.index');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: __('translation.something_went_wrong')
                ], 422);
            }
            
            $this->ErrorMessage($e->getMessage() ?: __('translation.something_went_wrong'));
            return redirect()->back();
        }
    }

    /**
     * Unsuspend merchant
     */
    public function unsuspend(Request $request, Merchant $merchant)
    {
        try {
            $this->merchantService->unsuspend($merchant);
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('translation.merchant_unsuspended_successfully')
                ]);
            }
            
            $this->SuccessMessage(__('translation.merchant_unsuspended_successfully'));
            return redirect()->route('merchants.index');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: __('translation.something_went_wrong')
                ], 422);
            }
            
            $this->ErrorMessage($e->getMessage() ?: __('translation.something_went_wrong'));
            return redirect()->back();
        }
    }
}