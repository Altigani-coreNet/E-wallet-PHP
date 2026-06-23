<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachments;
use App\Models\Merchant;
use App\Models\Partner;
use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PartnerFileUploadController extends Controller
{
    use HasFiles;

    public function upload(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|max:10240',
                'parent_id' => 'required|uuid',
                'attachable_type' => 'nullable|string',
                'field_name' => 'required|string|in:company_logo,trade_license,tax_certification,user_id_document'
            ]);

            $fileData = $this->uploadImageAndGetFileName($request, 'file', 'partner_files');
            if (!$fileData) {
                return response()->json([
                    'success' => false,
                    'message' => 'File upload failed'
                ], 500);
            }

            $fieldName = $request->input('field_name');
            $attachableType = $request->input('attachable_type', 'App\\Models\\Partner');
            $attachableId = $request->input('parent_id');

            if ($fieldName === 'company_logo') {
                Partner::where('id', $attachableId)->update(['logo' => $fileData]);
            }

            $attachment = Attachments::create([
                'url' => $fileData,
                'url_type' => $fieldName,
                'type' => $this->checkFileType($fileData),
                'attachable_type' => $attachableType,
                'attachable_id' => $attachableId,
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
            Log::error('Partner file upload failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'File upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function delete(Request $request, $fileId)
    {
        try {
            $request->validate([
                'field_name' => 'required|string|in:company_logo,trade_license,tax_certification,user_id_document'
            ]);

            $attachment = Attachments::find($fileId);
            if (!$attachment) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            // As requested: no ownership verification for partner delete.
            if ($attachment->url && file_exists(public_path($attachment->url))) {
                unlink(public_path($attachment->url));
            }

            $attachment->delete();

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Partner file delete failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPartnerFiles(Request $request)
    {
        try {
            $request->validate([
                'parent_id' => 'required|uuid',
                'attachable_type' => 'nullable|string',
            ]);

            $attachableType = $request->input('attachable_type', 'App\\Models\\Merchant');
            $attachableId = $request->input('parent_id');

            $attachments = Attachments::query()
                ->where('attachable_type', $attachableType)
                ->where('attachable_id', $attachableId)
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get partner files: ' . $e->getMessage()
            ], 500);
        }
    }
}

