<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TelescopeBasicAuth
{
    public function handle(Request $request, Closure $next)
    {
        $enabled = (bool) config('telescope.basic_auth.enabled', true);
        if (! $enabled) {
            return $next($request);
        }

        $expectedUsername = (string) config('telescope.basic_auth.username', 'jksaadmin');
        $expectedPassword = (string) config('telescope.basic_auth.password', 'softpos');

        $username = $request->getUser();
        $password = $request->getPassword();

        if ($username === $expectedUsername && $password === $expectedPassword) {
            return $next($request);
        }

        return response('Unauthorized.', 401, [
            'WWW-Authenticate' => 'Basic realm="Telescope"',
        ]);
    }
}









