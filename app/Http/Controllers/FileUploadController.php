<?php

namespace App\Http\Controllers;

use App\Traits\HasFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class FileUploadController extends Controller
{
    use HasFiles;

    public function process(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'image2' => 'required|file|mimes:jpeg,png,gif,pdf,doc,docx,xls,xlsx,ppt,pptx|max:10240', // 10MB max
                'file_type' => 'required|string',
                'merchant_code' => 'required|string|starts_with:TEMP_'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('image2');
            $fileType = $request->file_type;
            $merchantCode = $request->merchant_code;
            
            // Save directly to public directory under merchant code
            $directory = "uploads/merchants/{$merchantCode}";
            
            // Use the HasFiles trait method to upload the file
            $filepath = $this->uploadImageAndGetFileNameFromArrayInputFiles($file, $directory);
            
            // Get or initialize the cached files array for this merchant
            $cacheKey = "merchant_files_{$merchantCode}";
            $merchantFiles = Cache::get($cacheKey, []);
            
            // Add the new file to the array
            $merchantFiles[$fileType] = $filepath;
            
            // Cache the updated array for 10 minutes
            Cache::put($cacheKey, $merchantFiles, now()->addMinutes(10));
            
            // Track cache keys for cleanup
            $cacheKeys = Cache::get('merchant_file_keys', []);
            if (!in_array($cacheKey, $cacheKeys)) {
                $cacheKeys[] = $cacheKey;
                Cache::put('merchant_file_keys', $cacheKeys, now()->addMinutes(10));
            }
            
            return response()->json([
                'status' => 'success',
                'url' => asset($filepath),
                'merchant_code' => $merchantCode
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function load($id)
    {
        // This endpoint would be used to load existing files
        // Not implemented in this example since we're using direct URLs
        return response()->json(['status' => 'error', 'message' => 'Not implemented'], 501);
    }

    public function getFiles($merchantCode)
    {
        $cacheKey = "merchant_files_{$merchantCode}";
        $files = Cache::get($cacheKey, []);
        
        return response()->json([
            'status' => 'success',
            'files' => $files
        ]);
    }

    public function cleanup()
    {
        try {
            // Clean up cache and get file paths before deleting
            $cacheKeys = Cache::get('merchant_file_keys', []);
            foreach ($cacheKeys as $key) {
                // Get files from cache before deleting
                $files = Cache::get($key, []);
                
                // Delete actual files
                foreach ($files as $filePath) {
                    if (file_exists(public_path($filePath))) {
                        unlink(public_path($filePath));
                    }
                }
                
                // Get merchant code from cache key
                $merchantCode = str_replace('merchant_files_', '', $key);
                $merchantDir = public_path("uploads/merchants/{$merchantCode}");
                
                // Delete merchant directory if empty
                if (is_dir($merchantDir) && count(glob("$merchantDir/*")) === 0) {
                    rmdir($merchantDir);
                }
                
                // Clear cache
                Cache::forget($key);
            }
            Cache::forget('merchant_file_keys');
            
            return response()->json(['status' => 'success', 'message' => 'Files cleaned up successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error cleaning up files: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        return rmdir($dir);
    }
}
