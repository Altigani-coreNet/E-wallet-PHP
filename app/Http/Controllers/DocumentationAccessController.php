<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentationAccessController extends Controller
{
    public function checkAccess(Request $request)
    {
        try {
            // Get user from middleware
            $user = $request->attributes->get('auth0_user');
            
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not authenticated',
                    'Error_Code' => 'NOT_AUTHENTICATED'
                ], 401);
            }

            // Check if user has documentation access
            $hasAccess = $this->hasDocumentationAccess($user);
            
            if (!$hasAccess) {
                return response()->json([
                    'status' => false,
                    'message' => 'Access denied. You do not have permission to access documentation.',
                    'Error_Code' => 'INSUFFICIENT_PERMISSIONS'
                ], 403);
            }

            // Get user roles and permissions
            $roles = $user['https://your-namespace/roles'] ?? [];
            $permissions = $user['permissions'] ?? [];

            return response()->json([
                'hasAccess' => true,
                'user' => [
                    'id' => $user['sub'],
                    'email' => $user['email'],
                    'name' => $user['name'] ?? $user['email'],
                    'role' => $roles[0] ?? 'user'
                ],
                'permissions' => array_merge($permissions, $roles)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Internal server error',
                'Error_Code' => 'INTERNAL_ERROR'
            ], 500);
        }
    }

    private function hasDocumentationAccess(array $user): bool
    {
        // Method 1: Check Auth0 roles
        $roles = $user['https://your-namespace/roles'] ?? [];
        if (in_array('admin', $roles) || in_array('developer', $roles) || in_array('docs_access', $roles)) {
            return true;
        }

        // Method 2: Check Auth0 permissions
        $permissions = $user['permissions'] ?? [];
        if (in_array('read:documentation', $permissions) || in_array('admin:all', $permissions)) {
            return true;
        }

        // Method 3: Check custom claims
        if (isset($user['https://your-namespace/has_docs_access']) && $user['https://your-namespace/has_docs_access'] === true) {
            return true;
        }

        // Method 4: Check email domain (example)
        $allowedDomains = ['@corenettech.com', '@yourcompany.com'];
        $userEmail = $user['email'] ?? '';
        
        foreach ($allowedDomains as $domain) {
            if (str_ends_with($userEmail, $domain)) {
                return true;
            }
        }

        // Method 5: Check against your database
        // Uncomment and modify this if you want to check against your database
        /*
        $userModel = \App\Models\User::where('auth0_id', $user['sub'])->first();
        if ($userModel && $userModel->has_docs_access) {
            return true;
        }
        */

        // Default: deny access
        return false;
    }
}
