<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\SetApiLocaleFromAcceptLanguage;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api') // Empty prefix instead of default '/api'
                ->group(base_path('routes/api.php'));
            Route::middleware('api')
                ->prefix('api') // Empty prefix instead of default '/api'
                ->group(base_path('routes/admin_api.php'));
        },
    )
    // ->withBroadcasting(
    //     channels: __DIR__.'/../routes/channels.php',
    //     attributes: ['prefix' => 'api', 'middleware' => ['api']],
    // )
    ->withBroadcasting(
        channels: __DIR__.'/../routes/channels.php',
        attributes: ['prefix' => 'api', 'middleware' => ['api', 'auth:api']],
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            SetApiLocaleFromAcceptLanguage::class,
        ]);

        // Behind nginx / load balancer: set TRUSTED_PROXIES=* in .env so HTTPS and APP_URL match the public URL (required for Google OAuth redirect_uri).
        $trusted = env('TRUSTED_PROXIES');
        if ($trusted === '*' || $trusted === 'true' || $trusted === true) {
            $middleware->trustProxies(at: '*');
        }

        // Enable CORS for API routes
        // $middleware->api(prepend: [
        //     \Illuminate\Http\Middleware\HandleCors::class,
        // ]);
        
        // RedirectIfAuthenticated::redirectUsing(function ($request) {
        //     if (Auth::guard('admin')->check()) {
        //         return route('admin.dashboard');
        //     }
        //     if (Auth::guard('web')->check()) {
        //         return route('merchant.dashboard');
        //     }
        //     return '/';
        // });

        $middleware->alias([
            'customer.jwt' => \App\Modules\CustomerAuth\Middleware\CustomerJwtMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($request->is('api/v1/customer/*') || $request->is('api/v1/countries') || $request->is('api/v1/countries/*')) {
                return \App\Support\SuccessResponse::error(
                    $e->getMessage() ?: 'Error',
                    $e->getStatusCode()
                );
            }

            return null;
        });
    })->create();
