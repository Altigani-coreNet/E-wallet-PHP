<?php

if (!function_exists('___')) {
    /**
     * Translation helper function that wraps Laravel's __ function
     *
     * @param string|null $key
     * @param array $replace
     * @param string|null $locale
     * @return string|array
     */
    function ___($key = null, $replace = [], $locale = null)
    {
        return __($key, $replace, $locale);
    }
} 

if (!function_exists('coreservice_asset')) {
    /**
     * Generate asset URL with /api/core prefix for API gateway routing
     * Used for serving images and files from AuthService
     * 
     * @param string $path
     * @return string
     */
    function coreservice_asset($path)
    {
        if(config('app.url') == 'http://localhost:8000' || config('app.url') == 'http://localhost'){
            $baseUrl = 'http://localhost:8000';
        }else{
            $baseUrl = rtrim(config('app.url')  , '/');
        }

        // $baseUrl = rtrim( . '/api/softpos' , '/');
        $assetPath = ltrim($path, '/');
        
        // If path   already starts with http, return as is

        if (str_starts_with($assetPath, 'http://') || str_starts_with($assetPath, 'https://')) {
            return $assetPath;
        }
        
        return $baseUrl .'/' . $assetPath;
    }
}

if (!function_exists('customer_attachment_public_url')) {
    /**
     * Build a public URL for customer KYC files (profile photo, passport, etc.).
     *
     * Profile images are stored under public/. Passport documents use the public disk
     * (storage/app/public) and must be served via /storage/...
     */
    function customer_attachment_public_url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        if (str_starts_with($normalized, 'storage/')) {
            $assetPath = $normalized;
        } elseif (str_starts_with($normalized, 'customer_documents/')) {
            $assetPath = 'storage/'.$normalized;
        } else {
            $assetPath = $normalized;
        }

        return function_exists('coreservice_asset')
            ? coreservice_asset($assetPath)
            : asset($assetPath);
    }
}