<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\Attachments;
use App\Models\MerchantRejection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ChangeRequest;

class MerchantProfileController extends Controller
{
    /**
     * Get merchant profile
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $merchant = $user->merchant;

            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant profile not found',
                ], 404);
            }

            // Load relationships
            $merchant->load(['attachments', 'country', 'city', 'user']);

            // Get attachments with coreservice_asset URLs
            $getAttachmentUrl = function($url) {
                if (!$url) return null;
                return function_exists('coreservice_asset') ? coreservice_asset($url) : asset($url);
            };
            
            $attachments = [
                'logo' => $getAttachmentUrl($merchant->attachments->firstWhere('url_type', 'company_logo')?->url ?? null),
                'tax_certificate' => $getAttachmentUrl($merchant->attachments->firstWhere('url_type', 'tax_certification')?->url ?? null),
                'trade_license' => $getAttachmentUrl($merchant->attachments->firstWhere('url_type', 'trade_license')?->url ?? null),
                'user_id' => $getAttachmentUrl($merchant->attachments->firstWhere('url_type', 'user_id_document')?->url ?? null),
            ];

            // Check for pending change requests
            $hasPendingChange = ChangeRequest::where('changeable_type', Merchant::class)
                ->where('changeable_id', $merchant->id)
                ->where('status', 'pending')
                ->exists();

            // Get rejection info if rejected
            $rejection = null;
            if ($merchant->status === 'rejected') {
                $rejection = MerchantRejection::where('merchant_id', $merchant->id)
                    ->latest()
                    ->first();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'merchant' => $merchant,
                    'attachments' => $attachments,
                    'has_pending_change' => $hasPendingChange,
                    'rejection' => $rejection,
                    'profile_completion' => Merchant::calculateProfileCompletion($merchant),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve merchant profile', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve merchant profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update merchant profile (creates change request if not rejected)
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'business_type' => 'required|string',
            'address' => 'required|string',
            'trade_license_number' => 'nullable|string|max:255',
            'tax_certified_number' => 'nullable|string|max:255',
            'country_id' => 'nullable|exists:countries,id',
            'city_id' => 'nullable|exists:cities,id',
        ]);

        try {
            DB::beginTransaction();

            $merchant = Auth::user()->merchant;
            $payload = $request->only([
                'name', 'owner_name', 'email', 'phone', 
                'business_type', 'address', 'trade_license_number', 
                'tax_certified_number', 'country_id', 'city_id'
            ]);

            // Block if last status is pending review (change request in process)
            $hasPendingChange = ChangeRequest::where('changeable_type', Merchant::class)
                ->where('changeable_id', $merchant->id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPendingChange) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'A change request is already pending for this merchant',
                ], 400);
            }

            if ($merchant->status === 'rejected') {
                // Direct update when rejected
                $merchant->update($payload);
                $merchant->update(['status' => 'pending']);

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Profile updated successfully',
                    'data' => $merchant->fresh(),
                ]);
            } else {
                // Create change request
                $previousStatus = $merchant->status;
                $payload['__meta'] = [
                    'previous_status' => $previousStatus,
                ];

                $changeRequest = ChangeRequest::create([
                    'changeable_type' => Merchant::class,
                    'changeable_id' => $merchant->id,
                    'requester_type' => get_class(Auth::user()),
                    'requester_id' => Auth::id(),
                    'payload' => $payload,
                    'reason' => null,
                    'status' => 'pending',
                    'has_file' => false,
                ]);

                $merchant->update(['status' => 'requesting_updated']);

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Change request submitted successfully',
                    'data' => [
                        'merchant' => $merchant->fresh(),
                        'change_request' => $changeRequest,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update merchant profile', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update merchant profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update merchant attachments (creates change request if not rejected)
     */
    public function updateAttachments(Request $request): JsonResponse
    {
        $request->validate([
            'company_logo' => 'nullable|file|image|max:2048',
            'tax_certification' => 'nullable|file|image|max:2048',
            'trade_license' => 'nullable|file|image|max:2048',
            'user_id_document' => 'nullable|file|image|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $merchant = Auth::user()->merchant;

            // Block if last status is pending review
            $hasPendingChange = ChangeRequest::where('changeable_type', Merchant::class)
                ->where('changeable_id', $merchant->id)
                ->where('status', 'pending')
                ->exists();

            if ($hasPendingChange) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'A change request is already pending for this merchant',
                ], 400);
            }

            if ($merchant->status === 'rejected') {
                // Direct upload when rejected
                $this->uploadAttachments($request, $merchant);
                $merchant->update(['status' => 'pending']);

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Attachments updated successfully',
                    'data' => $merchant->fresh()->load('attachments'),
                ]);
            } else {
                // Create change request with file uploads
                $files = $request->allFiles();
                $payload = [];

                foreach ($files as $key => $file) {
                    if (!$file) continue;
                    $path = $file->store('merchant_documents/' . $merchant->id, 'public');
                    $payload[$key] = $path;
                }

                $previousStatus = $merchant->status;
                $payload['__meta'] = [
                    'previous_status' => $previousStatus,
                ];

                $changeRequest = ChangeRequest::create([
                    'changeable_type' => Merchant::class,
                    'changeable_id' => $merchant->id,
                    'requester_type' => get_class(Auth::user()),
                    'requester_id' => Auth::id(),
                    'payload' => $payload,
                    'reason' => null,
                    'status' => 'pending',
                    'has_file' => true,
                ]);

                $merchant->update(['status' => 'requesting_updated']);

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Change request submitted successfully',
                    'data' => [
                        'merchant' => $merchant->fresh(),
                        'change_request' => $changeRequest,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update merchant attachments', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update merchant attachments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get rejected fields for editing
     */
    public function getRejectedFields(Request $request): JsonResponse
    {
        try {
            $merchant = Auth::user()->merchant;

            if ($merchant->status !== 'rejected') {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant is not in rejected status',
                ], 400);
            }

            $rejection = MerchantRejection::where('merchant_id', $merchant->id)
                ->latest()
                ->first();

            if (!$rejection) {
                return response()->json([
                    'success' => false,
                    'message' => 'No rejection information found',
                ], 404);
            }

            // Get attachments with coreservice_asset URLs
            $getAttachmentUrl = function($url) {
                if (!$url) return null;
                return function_exists('coreservice_asset') ? coreservice_asset($url) : asset($url);
            };
            
            $attachments = [
                'logo' => $getAttachmentUrl($merchant->attachments->firstWhere('url_type', 'company_logo')?->url ?? null),
                'tax_certificate' => $getAttachmentUrl($merchant->attachments->firstWhere('url_type', 'tax_certification')?->url ?? null),
                'trade_license' => $getAttachmentUrl($merchant->attachments->firstWhere('url_type', 'trade_license')?->url ?? null),
                'user_id' => $getAttachmentUrl($merchant->attachments->firstWhere('url_type', 'user_id_document')?->url ?? null),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'merchant' => $merchant->load(['country', 'city']),
                    'rejection' => $rejection,
                    'attachments' => $attachments,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get rejected fields', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve rejected fields',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update only rejected fields
     */
    public function updateRejectedFields(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $merchant = Auth::user()->merchant;

            // Get the latest rejection
            $rejection = MerchantRejection::where('merchant_id', $merchant->id)
                ->latest()
                ->first();

            if (!$rejection) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No rejection information found',
                ], 404);
            }

            // Build validation rules based on rejected fields
            $validationRules = $this->buildValidationRules($rejection);
            $request->validate($validationRules);

            // Get only the rejected fields from the request
            $payload = [];
            $invalidFields = $rejection->invalid_fields ?? [];
            
            foreach ($invalidFields as $field) {
                if ($request->has($field)) {
                    $payload[$field] = $request->input($field);
                }
            }

            // Update the merchant with only the rejected fields
            if (!empty($payload)) {
                $merchant->update($payload);
            }

            // Update attachments if there are missing attachments
            $missingAttachments = $rejection->missing_attachments ?? [];
            if (!empty($missingAttachments)) {
                $this->uploadAttachments($request, $merchant);
                
                // Update merchant logo field if company_logo was uploaded
                if ($request->hasFile('company_logo')) {
                    $lastLogo = $merchant->attachments()
                        ->where('url_type', 'company_logo')
                        ->latest()
                        ->first();
                    
                    if ($lastLogo) {
                        $merchant->update(['logo' => $lastLogo->url]);
                    }
                }
            }

            $merchant->update(['status' => 'pending']);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Profile and attachments updated successfully',
                'data' => $merchant->fresh()->load(['attachments', 'country', 'city']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update rejected fields', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update rejected fields',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload merchant attachments
     */
    private function uploadAttachments(Request $request, Merchant $merchant): void
    {
        $fileTypes = [
            'company_logo' => 'company_logo',
            'tax_certification' => 'tax_certification',
            'trade_license' => 'trade_license',
            'user_id_document' => 'user_id_document',
        ];

        foreach ($fileTypes as $requestKey => $urlType) {
            if ($request->hasFile($requestKey)) {
                $file = $request->file($requestKey);
                $path = $file->store('merchant_documents/' . $merchant->id, 'public');

                // Create or update attachment
                $merchant->attachments()->create([
                    'url' => $path,
                    'url_type' => $urlType,
                    'type' => $file->getClientOriginalExtension(),
                ]);
            }
        }
    }

    /**
     * Build validation rules based on rejected fields
     */
    private function buildValidationRules(MerchantRejection $rejection): array
    {
        $validationRules = [];
        $invalidFields = $rejection->invalid_fields ?? [];
        $missingAttachments = $rejection->missing_attachments ?? [];

        $fieldRules = [
            'name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'business_type' => 'required|string',
            'address' => 'required|string',
            'trade_license_number' => 'required|string|max:255',
            'tax_certified_number' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'city_id' => 'required|exists:cities,id',
        ];

        // Add validation rules for rejected fields
        foreach ($invalidFields as $field) {
            if (isset($fieldRules[$field])) {
                $validationRules[$field] = $fieldRules[$field];
            }
        }

        // Add validation rules for missing attachments
        foreach ($missingAttachments as $attachment) {
            $validationRules[$attachment] = 'nullable|file|image|max:2048';
        }

        return $validationRules;
    }
}

