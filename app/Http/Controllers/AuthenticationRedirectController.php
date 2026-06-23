<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class AuthenticationRedirectController extends Controller
{
    /**
     * Redirect to POS Dashboard with encrypted user ID
     */
    public function redirectToPOSDashboard()
    {
        try {
            // Get the authenticated user ID
            $userId = Auth::id();
            
            // Encrypt the user ID
            $encryptedUserId = Crypt::encryptString($userId);
            
            // Get the POS service URL from config
            $posServiceUrl = config('services.pos_service_url');
            
            // Ensure the URL has a protocol (http:// or https://)
            if (!preg_match('/^https?:\/\//', $posServiceUrl)) {
                $posServiceUrl = 'http://' . $posServiceUrl;
            }
            
            // Build the redirect URL
            $redirectUrl = rtrim($posServiceUrl, '/') . '/authenticate/' . urlencode($encryptedUserId);
            
            // Redirect to the POS service
            return redirect()->away($redirectUrl);
            
        } catch (\Exception $e) {
            // Handle any errors
            return redirect()->back()->with('error', 'Unable to connect to POS Dashboard. Please try again.');
        }
    }
    
    /**
     * Redirect to POS with encrypted user ID
     */
    public function redirectToPOS()
    {
        try {
            // Get the authenticated user ID
            $userId = Auth::id();
            
            // Encrypt the user ID
            $encryptedUserId = Crypt::encryptString($userId);
            
            // Get the POS service URL from config
            $posServiceUrl = config('services.pos_service_url');
            
            // Ensure the URL has a protocol (http:// or https://)
            if (!preg_match('/^https?:\/\//', $posServiceUrl)) {
                $posServiceUrl = 'http://' . $posServiceUrl;
            }
            
            // Build the redirect URL
            $redirectUrl = rtrim($posServiceUrl, '/') . '/authenticate/' . urlencode($encryptedUserId);
            
            // Redirect to the POS service
            return redirect()->away($redirectUrl);
            
        } catch (\Exception $e) {
            // Handle any errors
            return redirect()->back()->with('error', 'Unable to connect to POS. Please try again.');
        }
    }
}

