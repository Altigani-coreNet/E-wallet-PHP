<?php

namespace App\Modules\CustomerAuth\Middleware;

use App\Models\Customer;
use App\Support\SuccessResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CustomerActiveMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Customer|null $customer */
        $customer = Auth::guard('customer')->user();

        if (! $customer || ! $customer->isActive()) {
            $message = $customer?->walletLoginBlockReason() ?? 'Your account is not available.';

            return SuccessResponse::error($message, 403);
        }

        return $next($request);
    }
}
