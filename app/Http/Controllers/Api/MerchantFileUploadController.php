<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MerchantFileUploadController extends Controller
{
    use HasFiles;

    /**
     * Upload merchant file
     */
    public function upload(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'file' => 'required|file|max:10240', // 10MB max
                'field_name' => 'required|string|in:company_logo,trade_license,tax_certification,user_id_document'
            ]);

            $file = $request->file('file');
            $fieldName = $request->input('field_name');
            
            // Get merchant from authenticated user
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $merchant = $user->merchant;
            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found for this user'
                ], 404);
            }
            
            // Upload file using HasFiles trait
            $fileData = $this->uploadImageAndGetFileName($request, "file", "merchant_files");
            
            if (!$fileData) {
                return response()->json([
                    'success' => false,
                    'message' => 'File upload failed'
                ], 500);
            }
            
            // Update merchant logo if the field is company_logo
            if ($fieldName == 'company_logo') {
                $merchant->logo = $fileData;
                $merchant->save();
            }

            // Create attachment record
            $attachment = $merchant->attachments()->create([
                "url" => $fileData,
                "url_type" => $fieldName,
                "type" => $this->checkFileType($fileData),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $attachment
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('File upload failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'File upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a merchant file
     */
    public function delete(Request $request, $fileId)
    {
        try {
            Log::info('File delete request received', [
                'file_id' => $fileId,
                'user_id' => $request->user()->id ?? null
            ]);

            // Validate request
            $request->validate([
                'field_name' => 'required|string|in:company_logo,trade_license,tax_certification,user_id_document'
            ]);

            $fieldName = $request->input('field_name');

            // Find the attachment
            $attachment = \App\Models\Attachments::find($fileId);
            
            if (!$attachment) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            // Check if user has permission to delete this file
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            if ($user->merchant && $user->merchant->id !== $attachment->attachable_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this file'
                ], 403);
            }

            // Delete the file from storage
            if ($attachment->url && file_exists(public_path($attachment->url))) {
                unlink(public_path($attachment->url));
                Log::info('File deleted from storage', ['url' => $attachment->url]);
            }

            // Delete the attachment record from database
            $attachment->delete();

            Log::info('File deleted successfully', [
                'file_id' => $fileId,
                'merchant_id' => $attachment->attachable_id,
                'field_name' => $fieldName
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('File delete error', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get files for a merchant
     */
    public function getMerchantFiles(Request $request)
    {
        try {
            // Get merchant from authenticated user
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $merchant = $user->merchant;
            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found for this user'
                ], 404);
            }
            
            // Get all attachments for the merchant
            $attachments = $merchant->attachments()
                ->whereIn('url_type', ['company_logo', 'trade_license', 'tax_certification', 'user_id_document'])
                ->get()
                ->map(function ($attachment) {
                    return [
                        'field_name' => $attachment->url_type,
                        'file_path' => $attachment->url,
                        'original_name' => basename($attachment->url),
                        'attachment_id' => $attachment->id,
                        'type' => $attachment->type
                    ];
                })
                ->keyBy('field_name');

            return response()->json([
                'success' => true,
                'data' => $attachments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get merchant files: ' . $e->getMessage()
            ], 500);
        }
    }
}

