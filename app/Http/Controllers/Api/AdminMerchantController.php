<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminMerchantDetailsResponse;
use App\Http\Resources\AdminMerchantResponse;
use App\Http\Resources\AdminUserResponse;
use App\Mail\MerchantStatusUpdateMail;
use App\Models\Merchant;
use App\Services\MerchantService;
use App\Traits\ApiResponse;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;

class AdminMerchantController extends Controller
{
    use ApiResponse;

    protected $merchantService;

    public function __construct(MerchantService $merchantService)
    {
        $this->merchantService = $merchantService;
    }

    /**
     * Get all merchants with pagination, search, and filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $merchants = $this->merchantService->index($request);
            $resourcePayload = AdminMerchantResponse::collection($merchants)
                ->response()
                ->getData(true);

            return $this->SuccessMessage($resourcePayload);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch merchants: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get merchants for select dropdown (simplified list)
     */
    public function select(Request $request): JsonResponse
    {
        try {
            $query = Merchant::select('id', 'business_name', 'name', 'email', 'status');

            // Add search functionality
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('business_name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('email', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Only active merchants by default
            if (!$request->has('include_inactive')) {
                $query->where('status', 'approved');
            }

            $merchants = $query->orderBy('business_name')
                ->limit(100)
                ->get()
                ->map(function($merchant) {
                    return [
                        'id' => $merchant->id,
                        'text' => $merchant->business_name ?: $merchant->name,
                        'business_name' => $merchant->business_name,
                        'name' => $merchant->name,
                        'email' => $merchant->email,
                        'status' => $merchant->status,
                    ];
                });

            return $this->SuccessMessage($merchants);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch merchants: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get merchant details
     */
    public function show(string $id): JsonResponse
    {
        try {
            $merchant = Merchant::query()
                ->select([
                    'id',
                    'user_id',
                    'merchant_code',
                    'name',
                    'owner_name',
                    'business_name',
                    'email',
                    'phone',
                    'address',
                    'logo',
                    'business_type',
                    'status',
                    'is_active',
                    'country_id',
                    'city_id',
                    'plan_id',
                    'scopes',
                    'trade_license_number',
                    'trade_license_start_date',
                    'trade_license_expired_date',
                    'tax_number',
                    'created_at',
                    'updated_at',
                ])
                ->with([
                    'country:id,name',
                    'city:id,name',
                    'plan:id,name',
                    'user:id,name,user_name,email,phone,profile_image,created_at,updated_at',
                    'logs' => function ($query) {
                        $query->latest()->limit(10);
                    },
                ])
                ->withCount(['attachments', 'users', 'terminals'])
                ->findOrFail($id);

            return $this->SuccessMessage([
                'merchant' => new AdminMerchantDetailsResponse($merchant),
            ]);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch merchant: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Create a new merchant
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // dd($request->all());
            $merchant = $this->merchantService->store($request);
            return $this->SuccessMessage($merchant, 201);
        } catch (\Exception $e) {
            // dd($e->getMessage(),$e->getLine());
            return $this->ErrorMessage('Failed to create merchant: ' . $e->getMessage(), $e->getLine(), 500);
        }
    }

    /**
     * Update merchant
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $merchant = $this->merchantService->update($request, $id);
            return $this->SuccessMessage($merchant);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update merchant: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Delete merchant
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->merchantService->destroy($id);
            return $this->SuccessMessage(['message' => 'Merchant deleted successfully']);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete merchant: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get merchant statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->merchantService->statistics();
            return $this->SuccessMessage($stats);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch statistics: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export merchants
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $exportData = $this->merchantService->export($request);
            return $this->SuccessMessage($exportData);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to export merchants: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk delete merchants
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:merchants,id'
            ]);

            $result = $this->merchantService->bulkDelete($validated['ids']);
            return $this->SuccessMessage($result);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete merchants: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export template with reference sheets for import
     */
    public function exportTemplate()
    {
        try {
            return $this->merchantService->exportTemplate();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview import data before saving
     */
    public function importPreview(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
            ]);

            $preview = $this->merchantService->importPreview($request->file('import_file'));

            return $this->SuccessMessage($preview);
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Preview failed: ' . $e->getMessage(), null, 400);
        }
    }

    /**
     * Lookup merchant and country information for the provided shop IDs.
     */
    public function countryLookup(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'shop_ids' => 'required|array|min:1',
                'shop_ids.*' => 'string',
            ]);

            $lookup = $this->merchantService->lookupMerchantCountryInfo($validated['shop_ids']);

            return $this->SuccessMessage($lookup);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to lookup merchant info: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Import merchants from file
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
            ]);

            $result = $this->merchantService->import($request->file('import_file'));

            return $this->SuccessMessage($result);
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Import failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Approve merchant application
     */
    public function approve(string $id): JsonResponse
    {
        try {
            $merchant = Merchant::findOrFail($id);
            $oldStatus = $merchant->status;

            $result = $this->merchantService->approve($id);

            $merchant->refresh();
            try {
                Mail::to($merchant->email)->send(new MerchantStatusUpdateMail($merchant, $oldStatus, $merchant->status));
            } catch (\Throwable $mailError) {
                Log::warning('Failed to send MerchantStatusUpdateMail (approve): ' . $mailError->getMessage());
            }
            return $this->SuccessMessage($result);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to approve merchant: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Reject merchant application
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'rejection_reason' => 'required|string|min:10',
                'invalid_fields' => 'nullable|array'
            ]);

            $merchant = Merchant::findOrFail($id);
            $oldStatus = $merchant->status;

            $result = $this->merchantService->reject(
                $id, 
                $validated['rejection_reason'], 
                $validated['invalid_fields'] ?? []
            );
            
            $merchant->refresh();
            try {
                Mail::to($merchant->email)->send(new MerchantStatusUpdateMail($merchant, $oldStatus, $merchant->status));
            } catch (\Throwable $mailError) {
                Log::warning('Failed to send MerchantStatusUpdateMail (reject): ' . $mailError->getMessage());
            }
            return $this->SuccessMessage($result);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to reject merchant: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Suspend merchant
     */
    public function suspend(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'suspension_reason' => 'required|string|min:10'
            ]);

            $merchant = Merchant::findOrFail($id);
            $oldStatus = $merchant->status;

            $result = $this->merchantService->suspend($id, $validated['suspension_reason']);

            $merchant->refresh();
            try {
                Mail::to($merchant->email)->send(new MerchantStatusUpdateMail($merchant, $oldStatus, $merchant->status));
            } catch (\Throwable $mailError) {
                Log::warning('Failed to send MerchantStatusUpdateMail (suspend): ' . $mailError->getMessage());
            }
            return $this->SuccessMessage($result);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to suspend merchant: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Unsuspend merchant
     */
    public function unsuspend(string $id): JsonResponse
    {
        try {
            $merchant = Merchant::findOrFail($id);
            $oldStatus = $merchant->status;

            $result = $this->merchantService->unsuspend($id);

            $merchant->refresh();
            try {
                Mail::to($merchant->email)->send(new MerchantStatusUpdateMail($merchant, $oldStatus, $merchant->status));
            } catch (\Throwable $mailError) {
                Log::warning('Failed to send MerchantStatusUpdateMail (unsuspend): ' . $mailError->getMessage());
            }
            return $this->SuccessMessage($result);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to unsuspend merchant: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get available merchant scopes
     */
    public function scopes(): JsonResponse
    {
        try {
            $scopes = config('merchant_scopes.available_scopes', []);
            
            // Format scopes for frontend (similar to permissions select format)
            $formattedScopes = array_map(function ($scope) {
                return [
                    'id' => $scope['key'],
                    'text' => $scope['label'],
                    'key' => $scope['key'],
                    'label' => $scope['label'],
                    'description' => $scope['description'] ?? '',
                ];
            }, array_values($scopes));

            return $this->SuccessMessage($formattedScopes);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch scopes: ' . $e->getMessage(), null, 500);
        }
    }
}



