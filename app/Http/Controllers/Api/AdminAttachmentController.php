<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachments;
use App\Traits\ApiResponse;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AdminAttachmentController extends Controller
{
    use ApiResponse;

    /**
     * Get attachments with filters (index method - same as data method)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Attachments::query();

            // Filter by attachable_id
            if ($request->has("attachable_id")) {
                $query->where('attachable_id', $request->get("attachable_id"));
            }

            // Filter by attachable_type
            if ($request->has("attachable_type")) {
                $query->where('attachable_type', $request->get("attachable_type"));
            }

            // Exclude certain types
            if ($request->has("except_types")) {
                $exceptTypes = $request->get("except_types");
                if (is_array($exceptTypes)) {
                    $query->whereNotIn("url_type", $exceptTypes);
                } else {
                    $query->where("url_type", "!=", $exceptTypes);
                }
            }

            // Filter by url_type
            if ($request->has("url_type")) {
                $url_type = $request->get("url_type");
                if (is_string($url_type)) {
                    $url_type = [$url_type];
                }
                $query->whereIn("url_type", $url_type);
            }

            // Filter by merchant_id (convenience filter)
            if ($request->has("merchant_id")) {
                $query->where('attachable_id', $request->get("merchant_id"))
                     ->where('attachable_type', 'App\\Models\\Merchant');
            }

            // Handle Profile logo special case (if Profile model exists in the system)
            $attachments = $query->get();
            
            if ($request->get('attachable_type') === 'App\\Models\\Profile' && 
                $request->has('attachable_id') && 
                $request->get('url_type') !== 'subscription_contract') {
                
                try {
                    // Check if Profile class exists before using it
                    if (class_exists('App\\Models\\Profile')) {
                        $profileClass = 'App\\Models\\Profile';
                        $profile = $profileClass::with("User:id,profile_image")->find($request->get('attachable_id'));
                        if ($profile && isset($profile->User) && $profile->User->profile_image && method_exists($profile->User, 'getProfileImageApi')) {
                            $logoData = [
                                'id' => 'logo_' . $profile->id,
                                'url' => $profile->User->getProfileImageApi(),
                                'attachment_url' => $profile->User->getProfileImageApi(),
                                'file_name' => 'Profile Logo',
                                'title' => 'Profile Logo',
                                'url_type' => 'logo',
                                'type' => 'image',
                                'created_at' => now()->toDateTimeString(),
                                'updated_at' => now()->toDateTimeString(),
                                "attachable_type" => 'App\\Models\\Profile',
                                "attachable_id" => $profile->user_id,
                            ];
                            
                            $attachments->push((object)$logoData);
                        }
                    }
                } catch (\Exception $e) {
                    // Profile handling failed, continue with regular attachments
                }
            }

            // Format the response data
            $formattedData = $attachments->map(function ($attachment) {
                $attachmentUrl = $attachment->attachment_url ?? 
                                (isset($attachment->url) ? asset($attachment->url) : null);
                
                // Handle Windows path separators
                if ($attachmentUrl && Str::contains($attachmentUrl, 'mec\\')) {
                    $attachmentUrl = Str::replace('mec\\', 'mec/', $attachmentUrl);
                }

                return [
                    'id' => $attachment->id ?? null,
                    'url' => $attachment->url ?? null,
                    'path' => $attachment->url ?? null,
                    'attachment_url' => $attachmentUrl,
                    'url_type' => $attachment->url_type ?? null,
                    'type' => $attachment->type ?? 'document',
                    'title' => $attachment->title ?? $attachment->url_type ?? null,
                    'file_name' => $attachment->url ? basename($attachment->url) : ($attachment->url_type ?? 'Document'),
                    'created_at' => $attachment->created_at ? $attachment->created_at->toDateTimeString() : null,
                    'updated_at' => $attachment->updated_at ? $attachment->updated_at->toDateTimeString() : null,
                    'attachable_type' => $attachment->attachable_type ?? null,
                    'attachable_id' => $attachment->attachable_id ?? null,
                ];
            });

            return $this->SuccessMessage([
                'data' => $formattedData->values()->all(),
                'total' => $formattedData->count()
            ]);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to fetch attachments: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Show/view an attachment file
     */
    public function show(string $id)
    {
        try {
            $attachment = Attachments::findOrFail($id);
            $file_name = $attachment->url;
            
            if (Str::startsWith($file_name, 'mec\\')) {
                $file_name = Str::replaceFirst('mec\\', 'mec/', $file_name);
            }
            
            $pathToFile = public_path($file_name);
            
            if (!File::exists($pathToFile)) {
                return response()->json(['error' => 'File not found'], 404);
            }
            
            return response()->file($pathToFile);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to show attachment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download an attachment file
     */
    public function download(string $id)
    {
        try {
            $attachment = Attachments::findOrFail($id);
            $file_name = $attachment->url;
            
            if (Str::startsWith($file_name, 'mec\\')) {
                $file_name = Str::replaceFirst('mec\\', 'mec/', $file_name);
            }
            
            $pathToFile = public_path($file_name);
            
            if (!File::exists($pathToFile)) {
                return response()->json(['error' => 'File not found'], 404);
            }
            
            // Get the original filename or use the attachment title/url_type
            $downloadName = $attachment->title ?? 
                           $attachment->url_type ?? 
                           basename($file_name);
            
            // Add extension if not present
            if (!pathinfo($downloadName, PATHINFO_EXTENSION) && pathinfo($file_name, PATHINFO_EXTENSION)) {
                $downloadName .= '.' . pathinfo($file_name, PATHINFO_EXTENSION);
            }
            
            return response()->download($pathToFile, $downloadName);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to download attachment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete an attachment by ID
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $attachment = Attachments::findOrFail($id);
            $pathToFile = public_path($attachment->url);

            // Check if the file exists, then delete it
            if (File::exists($pathToFile)) {
                File::delete($pathToFile);
            }

            $attachment->delete();

            return $this->SuccessMessage(['message' => 'Attachment deleted successfully']);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete attachment: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Delete an attachment by file path
     */
    public function deleteByPath(Request $request): JsonResponse
    {
        try {
            $request->validate([
                "filePath" => "required",
            ]);

            $parsedUrl = parse_url($request->filePath);
            $pathWithoutSlash = ltrim($parsedUrl['path'] ?? '', '/');
            
            $attachment = Attachments::where("url", $pathWithoutSlash)->firstOrFail();
            $pathToFile = public_path($attachment->url);

            // Check if the file exists, then delete it
            if (File::exists($pathToFile)) {
                File::delete($pathToFile);
            }

            $attachment->delete();

            return $this->SuccessMessage(['message' => 'Attachment deleted successfully']);
        } catch (\Exception $e) {
            return $this->ErrorMessage('Failed to delete attachment: ' . $e->getMessage(), null, 500);
        }
    }
}

