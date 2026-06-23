<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Log as LaravelLog;
use App\Models\Attachments;

trait HandlesMerchantFiles
{
    /**
     * Attach cached files to merchant
     */
    protected function attachCachedFilesToMerchant($merchant, string $merchantCode)
    {
        try {
            // Get cached files
            $cacheKey = "merchant_files_{$merchantCode}";
            $files = Cache::get($cacheKey, []);
            
            if (empty($files)) {
                return;
            }

            $attachments = [];
            
            foreach ($files as $fileType => $filePath) {
                if (!file_exists(public_path($filePath))) {
                    continue;
                }

                // Handle company logo separately
                if ($fileType === 'company_logo_image') {
                    $merchant->update(['logo' => $filePath]);
                    continue;
                }

                $attachments[] = new Attachments([
                    'url' => $filePath,
                    'url_type' => $fileType,
                    'type' => $this->checkFileType($filePath),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Save attachments using polymorphic relationship
            if (!empty($attachments)) {
                $merchant->attachments()->saveMany($attachments);
            }

            // Move files from temp to permanent location
            foreach ($files as $fileType => $filePath) {
                $oldPath = public_path($filePath);
                if (!file_exists($oldPath)) {
                    continue;
                }

                // Create new path under merchant's permanent directory
                $newDirectory = "uploads/merchants/permanent/{$merchant->id}/{$fileType}";
                $newPath = public_path($newDirectory);
                
                // Create directory if it doesn't exist
                if (!is_dir($newPath)) {
                    mkdir($newPath, 0755, true);
                }

                // Move file to new location
                $fileName = basename($filePath);
                rename($oldPath, $newPath . '/' . $fileName);

                // Update attachment URL in database
                $merchant->attachments()
                    ->where('url', $filePath)
                    ->update(['url' => $newDirectory . '/' . $fileName]);
            }

            // Clean up the temp directory
            $tempDir = public_path("uploads/merchants/{$merchantCode}");
            if (is_dir($tempDir)) {
                $this->deleteDirectory($tempDir);
            }

            // Clear the cache
            Cache::forget($cacheKey);
            $cacheKeys = Cache::get('merchant_file_keys', []);
            if (($key = array_search($cacheKey, $cacheKeys)) !== false) {
                unset($cacheKeys[$key]);
                Cache::put('merchant_file_keys', $cacheKeys, now()->addMinutes(10));
            }

        } catch (\Exception $e) {
            LaravelLog::error('Error attaching files to merchant: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a directory and its contents
     */
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

    /**
     * Check file type
     */
    /**
     * Handle a single merchant file upload
     */
    protected function handleMerchantFile($merchant, $file, $type)
    {
        try {
            // Store the new file
            $path = $file->store('uploads/merchants/' . $merchant->id . '/' . $type, 'public');

            // Update or create attachment
            return Attachments::updateOrCreate(
                [
                    'attachable_type' => get_class($merchant),
                    'attachable_id' => $merchant->id,
                    'url_type' => $type,
                ],
                [
                    'url' => $path,
                    'type' => $this->checkMerchantFileType($path),
                ]
            );
        } catch (\Exception $e) {
            LaravelLog::error('Error handling merchant file: ' . $e->getMessage());
            throw $e;
        }
    }

    private function checkMerchantFileType($filepath): string
    {
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
        $documentExtensions = ['pdf', 'doc', 'docx'];

        if (in_array($extension, $imageExtensions)) {
            return 'image';
        } elseif (in_array($extension, $documentExtensions)) {
            return 'document';
        }

        return 'other';
    }
}
