<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChangeRequest;
use App\Models\Merchant;
use App\Services\ChangeRequestService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ChangeRequestController extends Controller
{
    protected $changeRequestService;

    public function __construct(ChangeRequestService $changeRequestService)
    {
        $this->changeRequestService = $changeRequestService;
    }

    /**
     * Get change requests for a merchant or all change requests (admin)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = ChangeRequest::with(['requester', 'approver', 'changeable']);

            // Filter by merchant if provided
            if ($request->has('merchant_id')) {
                $query->where('changeable_type', Merchant::class)
                      ->where('changeable_id', $request->merchant_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by changeable type
            if ($request->has('changeable_type')) {
                $query->where('changeable_type', $request->changeable_type);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $changeRequests = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $changeRequests,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve change requests',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific change request by ID
     */
    public function show(ChangeRequest $changeRequest): JsonResponse
    {
        try {
            $changeRequest->load(['requester', 'approver', 'changeable']);

            $payload = $changeRequest->payload ?? [];
            $mappedPayload = $this->mapPayloadFieldsWithComparison($payload, $changeRequest->changeable);

            $data = [
                'id' => $changeRequest->id,
                'request_type' => $this->getRequestType($payload),
                'reason' => $changeRequest->reason,
                'status' => $changeRequest->status,
                'requester' => $changeRequest->requester ? 
                    ($changeRequest->requester->name ?? $changeRequest->requester->email ?? 'Unknown') : 
                    'System',
                'approver' => $changeRequest->approver ? 
                    ($changeRequest->approver->name ?? $changeRequest->approver->email ?? 'Unknown') : 
                    null,
                'moderation_note' => $changeRequest->moderation_note,
                'created_at' => $changeRequest->created_at->toISOString(),
                'approved_at' => $changeRequest->approved_at?->toISOString(),
                'rejected_at' => $changeRequest->rejected_at?->toISOString(),
                'changes' => $mappedPayload,
                'has_file' => $changeRequest->has_file,
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve change request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve a change request
     */
    public function approve(Request $request, ChangeRequest $changeRequest): JsonResponse
    {
        try {
            $approver = Auth::user();
            $moderationNote = $request->get('moderation_note');

            $this->changeRequestService->approve($changeRequest, $approver, $moderationNote);

            return response()->json([
                'success' => true,
                'message' => 'Change request approved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve change request',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reject a change request
     */
    public function reject(Request $request, ChangeRequest $changeRequest): JsonResponse
    {
        try {
            $approver = Auth::user();
            $moderationNote = $request->get('moderation_note');

            $this->changeRequestService->reject($changeRequest, $approver, $moderationNote);

            return response()->json([
                'success' => true,
                'message' => 'Change request rejected successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject change request',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get change history for a merchant
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $merchantId = $request->get('merchant_id');
            
            if (!$merchantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant ID is required',
                ], 400);
            }

            $merchant = Merchant::findOrFail($merchantId);
            $history = $this->changeRequestService->getChangeHistory($merchant);

            return response()->json([
                'success' => true,
                'data' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve change history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Map payload fields with comparison between current and requested values
     */
    private function mapPayloadFieldsWithComparison(array $payload, $model): array
    {
        $mappedPayload = [];
        
        foreach ($payload as $key => $requestedValue) {
            $mappedKey = $this->mapFieldName($key);
            
            // Get current value from the model
            $currentValue = $model ? $model->getAttribute($key) : null;
            
            // Map both values
            $currentMappedValue = $this->mapFieldValue($key, $currentValue);
            $requestedMappedValue = $this->mapFieldValue($key, $requestedValue);
            
            // Only include fields that are actually changing
            if ($currentMappedValue !== $requestedMappedValue) {
                $mappedPayload[$mappedKey] = [
                    'current' => $currentMappedValue,
                    'requested' => $requestedMappedValue,
                    'field' => $key
                ];
            }
        }
        
        return $mappedPayload;
    }

    /**
     * Map field names to human-readable format
     */
    private function mapFieldName(string $fieldName): string
    {
        $fieldMappings = [
            'name' => 'Merchant Name',
            'owner_name' => 'Owner Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'address' => 'Address',
            'business_type' => 'Business Type',
            'country' => 'Country',
            'city' => 'City',
            'country_id' => 'Country',
            'city_id' => 'City',
            'trade_license_number' => 'Trade License Number',
            'tax_certified_number' => 'Tax Number',
            'tax_number' => 'Tax Number',
            'trade_license_start_date' => 'Trade License Start Date',
            'trade_license_expired_date' => 'Trade License Expiry Date',
            'logo' => 'Logo',
            'merchant_code' => 'Merchant Code',
            'user_id' => 'User',
            'status' => 'Status',
            'is_active' => 'Active Status',
            'company_logo' => 'Company Logo',
            'tax_certification' => 'Tax Certificate',
            'trade_license' => 'Trade License',
            'user_id_document' => 'ID Document',
        ];

        return $fieldMappings[$fieldName] ?? ucfirst(str_replace('_', ' ', $fieldName));
    }

    /**
     * Map field values to human-readable format
     */
    private function mapFieldValue(string $fieldName, $value)
    {
        if (is_null($value)) {
            return 'Not provided';
        }

        // Handle file fields
        if (in_array($fieldName, ['company_logo', 'tax_certification', 'trade_license', 'user_id_document', 'logo'])) {
            return $value ? 'File uploaded' : 'No file';
        }

        // Handle boolean fields
        if ($fieldName === 'is_active') {
            return $value ? 'Yes' : 'No';
        }

        // Handle status
        if ($fieldName === 'status') {
            $statusLabels = [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'suspended' => 'Suspended',
                'viewed' => 'Viewed',
                'deleted' => 'Deleted',
                'requesting_updated' => 'Requesting Update',
            ];
            return $statusLabels[$value] ?? $value;
        }

        return $value;
    }

    /**
     * Get request type based on payload
     */
    private function getRequestType(array $payload): string
    {
        if (isset($payload['company_logo']) || isset($payload['tax_certification']) || 
            isset($payload['trade_license']) || isset($payload['user_id_document'])) {
            return 'Attachments';
        } else {
            return 'Profile';
        }
    }
}

