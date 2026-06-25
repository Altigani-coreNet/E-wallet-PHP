<?php

namespace App\Traits;

use App\Models\Attachments;

trait HasFiles
{
    public function uploadImageAndGetFileName($request, string $property, string $filename): ?string
    {
        try {
            $imagePath = null;
            if ($request->hasFile($property)) {
                $image = $request->file($property);

                // Define a unique file name
                $imageName = uniqid('', true) . '.' . $image->getClientOriginalExtension();
                // Move the image to the public directory
                $image->move(public_path($filename), $imageName);

                // Get the image path for further use
                $imagePath = $filename . '/' . $imageName;
            }
            return $imagePath;
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    public function checkFileType($filepath): string
    {
        // Get the file extension
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

        // Define arrays of image and document extensions
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
        $documentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'odt', 'ods'];

        // Check if the file is an image
        if (in_array($extension, $imageExtensions)) {
            return 'image';
        }

        // Check if the file is a document
        if (in_array($extension, $documentExtensions)) {
            return 'document';
        }

        // Return 'image' as default
        return 'image';
    }
}

