<?php

namespace App\Http\Controllers;

use App\Services\Discord;
use App\Support\BotStats;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Discord $discord): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('servers.index');
        }

        $stats = BotStats::summary($discord);

        return view('home', [
            'stats' => $stats,
            'inviteUrl' => $discord->inviteUrl(),
        ]);
    }
}
