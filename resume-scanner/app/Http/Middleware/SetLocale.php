<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = array_keys(config('app.supported_locales', ['en' => 'English']));
        $locale = (string) $request->session()->get('locale', config('app.locale', 'en'));

        if (Auth::check() && Schema::hasColumn('users', 'locale')) {
            $userLocale = Auth::user()?->locale;
            if (is_string($userLocale) && in_array($userLocale, $supportedLocales, true)) {
                $locale = $userLocale;
            }
        }

        if (!in_array($locale, $supportedLocales, true)) {
            $locale = (string) config('app.locale', 'en');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
