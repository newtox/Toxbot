<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LanguageController extends Controller
{
    public function switch(string $locale): RedirectResponse
    {
        $locales = config('app.locales');

        if (! array_key_exists($locale, $locales)) {
            return redirect()->back();
        }

        session()->put('locale', $locale);

        if ($user = auth()->user()) {
            $settings = $user->botSettings();
            $settings->language = $locales[$locale]['bot'];
            $settings->save();
        }

        return redirect()->back();
    }
}
