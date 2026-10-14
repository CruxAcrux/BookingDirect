<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Answers in the language the client asks for (sv or en), so validation and other
 * messages match the UI language.
 */
class SetLocaleFromRequest
{
    private const SUPPORTED = ['sv', 'en'];

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->getPreferredLanguage(self::SUPPORTED);

        if ($locale !== null && $request->hasHeader('Accept-Language')) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
