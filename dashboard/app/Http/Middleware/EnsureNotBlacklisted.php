<?php

namespace App\Http\Middleware;

use App\Models\BlacklistEntry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotBlacklisted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && BlacklistEntry::userBlocked($user->id)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('home')->with('status', __('login.blacklisted'));
        }

        return $next($request);
    }
}
