<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        app()->setLocale($this->resolve($request));

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        $locales = config('app.locales');

        if ($user = $request->user()) {
            $botLanguage = $user->botSettings()->language;
            $locale = collect($locales)->search(fn ($l) => $l['bot'] === $botLanguage);

            if ($locale !== false) {
                return $locale;
            }
        }

        if (array_key_exists((string) session('locale'), $locales)) {
            return session('locale');
        }

        return $request->getPreferredLanguage(array_keys($locales)) ?? config('app.locale');
    }
}
