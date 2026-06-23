<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetApiLocaleFromAcceptLanguage
{
    /**
     * Supported API locales.
     *
     * @var string[]
     */
    private array $supportedLocales = ['en', 'ar'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        foreach (['X-App-Locale', 'X-Locale'] as $header) {
            $explicit = strtolower(trim((string) $request->header($header)));
            if (in_array($explicit, $this->supportedLocales, true)) {
                return $explicit;
            }
        }

        $acceptLanguage = trim((string) $request->header('Accept-Language', ''));
        if ($acceptLanguage !== '') {
            $primary = strtolower(substr(explode(',', $acceptLanguage)[0], 0, 2));
            if (in_array($primary, $this->supportedLocales, true)) {
                return $primary;
            }
        }

        foreach ($request->getLanguages() as $language) {
            $normalized = strtolower(substr($language, 0, 2));
            if (in_array($normalized, $this->supportedLocales, true)) {
                return $normalized;
            }
        }

        return 'en';
    }
}
