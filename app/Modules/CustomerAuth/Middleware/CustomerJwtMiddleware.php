<?php

namespace App\Modules\CustomerAuth\Middleware;

use App\Models\Customer;
use App\Modules\CustomerAuth\Support\CustomerJwtService;
use App\Support\SuccessResponse;
use Closure;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CustomerJwtMiddleware
{
    public function __construct(
        private readonly CustomerJwtService $jwtService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (! $token) {
            return SuccessResponse::error('Unauthorized', 401);
        }

        try {
            $payload = $this->jwtService->decode($token);
        } catch (ExpiredException|SignatureInvalidException|\UnexpectedValueException) {
            return SuccessResponse::error('Unauthorized', 401);
        }

        if (($payload->type ?? null) !== 'customer') {
            return SuccessResponse::error('Customer access required', 403);
        }

        $customer = Customer::query()->find($payload->sub ?? null);

        if (! $customer) {
            return SuccessResponse::error('Unauthorized', 401);
        }

        Auth::guard('customer')->setUser($customer);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
