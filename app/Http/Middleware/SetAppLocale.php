<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetAppLocale
{
    /**
     * Handle an incoming request and set application locale based on user session or preference.
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = session('locale', auth()->user()?->locale ?? config('app.locale', 'ar'));

        if (in_array($locale, ['ar', 'en'])) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
