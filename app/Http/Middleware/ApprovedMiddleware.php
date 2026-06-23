<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApprovedMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth('external')->check() && auth('external')->user()->merchant && auth('external')->user()->merchant->status === 'approved') {
            return $next($request);
        }
        // dd($request->all());

        return redirect()->route('merchant.profile');
    }
}
