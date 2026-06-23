<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Admin;

class CheckIsAdmin
{
    /**
     * Handle an incoming request.
     * This middleware checks if the authenticated user is from the admins table
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated via Passport (auth:api)
        if (!auth('external')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Get the authenticated user
        $user = auth('external')->user();

        // Check if the authenticated user is an Admin model (from admins table)
        if (!($user instanceof Admin)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        // Check if admin status is active
        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your admin account is not active.'
            ], 403);
        }

        return $next($request);
    }
}

