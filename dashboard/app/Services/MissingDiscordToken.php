<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use RuntimeException;

class MissingDiscordToken extends RuntimeException
{
    public function render()
    {
        Auth::logout();
        request()->session()->invalidate();

        return redirect()->route('home')->with('status', __('login.expired'));
    }
}
