<?php

namespace App\Modules\CustomerAuth\Middleware;

use App\Models\Customer;
use App\Modules\CustomerAuth\Support\CustomerJwtService;
use App\Support\SuccessResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerJwtMiddleware
{
    public function __construct(
        private readonly CustomerJwtService $jwtService,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractBearerToken($request);

        if (!$token) {
            return SuccessResponse::error('Unauthorized', 401);
        }

        try {
            $payload = $this->jwtService->decode($token);

            if (($payload->type ?? null) !== 'customer') {
                return SuccessResponse::error('Unauthorized', 401);
            }

            $customer = Customer::query()->find($payload->sub);

            if (!$customer) {
                return SuccessResponse::error('Unauthorized', 401);
            }

            auth()->guard('customer')->setUser($customer);
        } catch (\Throwable) {
            return SuccessResponse::error('Unauthorized', 401);
        }

        return $next($request);
    }

    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
