<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use App\Modules\CustomerAuth\Support\CustomerJwtService;
use Closure;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates the /api/broadcasting/auth request for either a merchant/staff
 * user (Passport "api" guard) or a customer (custom JWT). The resolved entity is
 * bound to the request so the channel callbacks in routes/channels.php receive it.
 */
class BroadcastAuthenticate
{
    public function __construct(
        private readonly CustomerJwtService $jwtService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($user = Auth::guard('api')->user()) {
            $request->setUserResolver(fn () => $user);

            return $next($request);
        }

        if ($customer = $this->resolveCustomer($request)) {
            Auth::guard('customer')->setUser($customer);
            $request->setUserResolver(fn () => $customer);

            return $next($request);
        }

        abort(401, 'Unauthorized');
    }

    private function resolveCustomer(Request $request): ?Customer
    {
        $token = $this->extractToken($request);

        if (! $token) {
            return null;
        }

        try {
            $payload = $this->jwtService->decode($token);
        } catch (ExpiredException|SignatureInvalidException|\UnexpectedValueException) {
            return null;
        }

        if (($payload->type ?? null) !== 'customer') {
            return null;
        }

        return Customer::query()->find($payload->sub ?? null);
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
