<?php

namespace App\Http\Controllers;

use App\Services\Discord;
use App\Support\BotStats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Discord $discord): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('servers.index');
        }

        $stats = BotStats::summary($discord);

        return view('home', [
            'stats' => $stats,
            'inviteUrl' => $discord->inviteUrl(),
        ]);
    }
}
