<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('admin')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized. Admin access required.'], 401);
            }
            return redirect()->route('admin.login');
        }

        $admin = Auth::guard('admin')->user();
        
        // Check if admin is active
        if ($admin->status !== 'active') {
            Auth::guard('admin')->logout();
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Account is inactive.'], 403);
            }
            return redirect()->route('admin.login')->with('error', 'Your account is inactive.');
        }

        return $next($request);
    }
} 