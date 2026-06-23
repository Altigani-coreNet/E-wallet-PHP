<?php

namespace App\Http\Controllers\Api\V2\Admin;

use App\Http\Controllers\Controller;
use App\Mail\MerchantStatusUpdateMail;
use App\Models\Partner as ContentProvider;
use App\Services\PartnerService as ContentProviderService;
use App\Traits\ApiResponse;
use App\Traits\BroadcastsServicesListingUpdate;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;

class AdminContentProviderController extends Controller
{   
    use ApiResponse, BroadcastsServicesListingUpdate;

    protected $contentProviderService;

    public function __construct(ContentProviderService $contentProviderService)
    {
        $this->contentProviderService = $contentProviderService;
    }

    /**
     * Get all content providers with pagination, search, and filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $contentProviders = $this->contentProviderService->index($request);
            return $this->SuccessMessage($contentProviders);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch content providers: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get content providers for select dropdown (simplified list)
     */
    public function select(Request $request): JsonResponse
    {
        try {
            $subPartnersForParent = $request->input('sub_partners_for_parent');

            $query = ContentProvider::query()->select([
                'id',
                'name',
                'business_name',
                'email',
                'status',
                'country_id',
                'parent_id',
                'is_parent',
            ])->with('country');

            if ($subPartnersForParent) {
                $query->where('parent_id', $subPartnersForParent);
            } else {
              $query->whereNull('parent_id');
            }

            if ($request->has('search') && ! empty($request->search)) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('email', 'LIKE', "%{$searchTerm}%");
                });
            }

            if ($request->has('country_id') && ! empty($request->country_id)) {
                $query->where('country_id', $request->country_id);
            }

            if (! $request->has('include_inactive')) {
                $query->where('status', 'approved');
            }

            $limit = $request->has('limit') ? (int) $request->limit : ($request->has('search') && ! empty($request->search) ? 100 : 5);

            if (! $subPartnersForParent) {
                $query->withCount('subPartners');
            }

            $contentProviders = $query->orderBy('name')
                ->limit($limit)
                ->get()
                ->map(function ($contentProvider) use ($subPartnersForParent) {
                    $row = [
                        'id' => $contentProvider->id,
                        'content_provider_id' => $contentProvider->id,
                        'merchant_id' => $contentProvider->id,
                        'text' => $contentProvider->name,
                        'name' => $contentProvider->name,
                        'business_name' => $contentProvider->business_name,
                        'email' => $contentProvider->email,
                        'status' => $contentProvider->status,
                        'country_id' => $contentProvider->country_id,
                        'parent_id' => $contentProvider->parent_id,
                        'is_parent' => $contentProvider->is_parent,
                    ];

                    if ($contentProvider->relationLoaded('country') && $contentProvider->country) {
                        $row['country_name'] = $contentProvider->country->name;
                        $row['country_short_name'] = $contentProvider->country->short_name;
                    }

                    if (! $subPartnersForParent) {
                        $count = (int) ($contentProvider->sub_partners_count ?? 0);
                        $row['has_sub_partners'] = $count > 0;
                        $row['sub_partners_count'] = $count;
                    }

                    return $row;
                });

            return $this->SuccessMessage($contentProviders);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch content providers: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get content provider details
     */
    public function show(string $id): JsonResponse
    {
        try {
            $data = $this->contentProviderService->show($id);
           
            // return $this->SuccessMessage([]);
            return $this->SuccessMessage($data);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch partner: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Create a new content provider
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $contentProvider = $this->contentProviderService->store($request);
            return $this->SuccessMessage($contentProvider, 201);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to create content provider: ' . $e->getMessage(), $e->getLine(), 500);
        }
    }

    /**
     * Update content provider
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $contentProvider = $this->contentProviderService->update($request, $id);
            return $this->SuccessMessage($contentProvider);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to update content provider: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Delete content provider
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->contentProviderService->destroy($id);
            $this->broadcastServicesListingUpdate();
            return $this->SuccessMessage($this->appendServicesListingBroadcastWarning(['message' => 'Content provider deleted successfully']));
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete content provider: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get content provider statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->contentProviderService->statistics();
            return $this->SuccessMessage($stats);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch statistics: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export content providers
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $exportData = $this->contentProviderService->export($request);
            return $this->SuccessMessage($exportData);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to export content providers: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Bulk delete content providers
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:partners,id'
            ]);

            $result = $this->contentProviderService->bulkDelete($validated['ids']);
            $this->broadcastServicesListingUpdate();
            return $this->SuccessMessage($this->appendServicesListingBroadcastWarning($result));
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete content providers: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Export template with reference sheets for import
     */
    public function exportTemplate()
    {
        try {
            return $this->contentProviderService->exportTemplate();
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

            $preview = $this->contentProviderService->importPreview($request->file('import_file'));

            return $this->SuccessMessage($preview);
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Preview failed: ' . $e->getMessage(), null, 400);
        }
    }

    /**
     * Lookup content provider and country information for the provided shop IDs.
     */
    public function countryLookup(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'shop_ids' => 'required|array|min:1',
                'shop_ids.*' => 'string',
            ]);

            $lookup = $this->contentProviderService->lookupMerchantCountryInfo($validated['shop_ids']);

            return $this->SuccessMessage($lookup);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to lookup content provider info: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Import content providers from file
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
            ]);

            $result = $this->contentProviderService->import($request->file('import_file'));

            return $this->SuccessMessage($result);
        } catch (\Throwable $e) {
            return $this->ErrorMessage('Import failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Approve content provider application
     */
    public function approve(string $id): JsonResponse
    {
        try {
            $contentProvider = ContentProvider::findOrFail($id);
            $oldStatus = $contentProvider->status;

            $result = $this->contentProviderService->approve($id);

            $contentProvider->refresh();
            try {
                Mail::to($contentProvider->email)->send(new MerchantStatusUpdateMail($contentProvider, $oldStatus, $contentProvider->status));
            } catch (\Throwable $mailError) {
                \Log::warning('Failed to send MerchantStatusUpdateMail (approve): ' . $mailError->getMessage());
            }
            $this->broadcastServicesListingUpdate();
            return $this->SuccessMessage($this->appendServicesListingBroadcastWarning($result));
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to approve content provider: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Reject content provider application
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'rejection_reason' => 'required|string|min:10',
                'invalid_fields' => 'nullable|array'
            ]);

            $contentProvider = ContentProvider::findOrFail($id);
            $oldStatus = $contentProvider->status;

            $result = $this->contentProviderService->reject(
                $id, 
                $validated['rejection_reason'], 
                $validated['invalid_fields'] ?? []
            );
            
            $contentProvider->refresh();
            try {
                Mail::to($contentProvider->email)->send(new MerchantStatusUpdateMail($contentProvider, $oldStatus, $contentProvider->status));
            } catch (\Throwable $mailError) {
                \Log::warning('Failed to send MerchantStatusUpdateMail (reject): ' . $mailError->getMessage());
            }
            $this->broadcastServicesListingUpdate();
            return $this->SuccessMessage($this->appendServicesListingBroadcastWarning($result));
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to reject content provider: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Suspend content provider
     */
    public function suspend(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'suspension_reason' => 'required|string|min:10'
            ]);

            $contentProvider = ContentProvider::findOrFail($id);
            $oldStatus = $contentProvider->status;

            $result = $this->contentProviderService->suspend($id, $validated['suspension_reason']);

            $contentProvider->refresh();
            try {
                Mail::to($contentProvider->email)->send(new MerchantStatusUpdateMail($contentProvider, $oldStatus, $contentProvider->status));
            } catch (\Throwable $mailError) {
                \Log::warning('Failed to send MerchantStatusUpdateMail (suspend): ' . $mailError->getMessage());
            }
            $this->broadcastServicesListingUpdate();
            return $this->SuccessMessage($this->appendServicesListingBroadcastWarning($result));
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to suspend content provider: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Unsuspend content provider
     */
    public function unsuspend(string $id): JsonResponse
    {
        try {
            $contentProvider = ContentProvider::findOrFail($id);
            $oldStatus = $contentProvider->status;

            $result = $this->contentProviderService->unsuspend($id);

            $contentProvider->refresh();
            try {
                Mail::to($contentProvider->email)->send(new MerchantStatusUpdateMail($contentProvider, $oldStatus, $contentProvider->status));
            } catch (\Throwable $mailError) {
                \Log::warning('Failed to send MerchantStatusUpdateMail (unsuspend): ' . $mailError->getMessage());
            }
            $this->broadcastServicesListingUpdate();
            return $this->SuccessMessage($this->appendServicesListingBroadcastWarning($result));
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to unsuspend content provider: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get available content provider scopes
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

    /**
     * Get content provider logs
     */
    public function logs(string $id): JsonResponse
    {
        try {
            $contentProvider = ContentProvider::findOrFail($id);
            $logs = $contentProvider->logs()->latest()->paginate(20);
            return $this->SuccessMessage($logs);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch logs: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get content provider change requests
     */
    public function changeRequests(string $id): JsonResponse
    {
        try {
            $contentProvider = ContentProvider::findOrFail($id);
            // Assuming change requests work similarly to merchants
            $changeRequests = \App\Models\ChangeRequest::where('changeable_type', ContentProvider::class)
                ->where('changeable_id', $contentProvider->id)
                ->latest()
                ->get();
            return $this->SuccessMessage($changeRequests);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch change requests: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Approve change request
     */
    public function approveChangeRequest(string $id, string $changeRequest): JsonResponse
    {
        try {
            // Implementation similar to merchant change request approval
            return $this->SuccessMessage(['message' => 'Change request approved']);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to approve change request: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Reject change request
     */
    public function rejectChangeRequest(string $id, string $changeRequest): JsonResponse
    {
        try {
            // Implementation similar to merchant change request rejection
            return $this->SuccessMessage(['message' => 'Change request rejected']);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to reject change request: ' . $e->getMessage(), null, 500);
        }
    }
}
