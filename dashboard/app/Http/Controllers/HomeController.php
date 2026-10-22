<?php

namespace App\Http\Controllers;

use App\Models\BotUser;
use App\Models\Tag;
use App\Services\Discord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Discord $discord): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('servers.index');
        }

        $stats = Cache::remember('home:stats', 600, fn () => [
            'servers' => rescue(fn () => count($discord->botGuildIds()), null, report: false),
            'users' => rescue(fn () => BotUser::query()->where('xp', '>', 0)->count(), null, report: false),
            'tags' => rescue(fn () => Tag::query()->count(), null, report: false),
        ]);

        return view('home', [
            'stats' => $stats,
            'inviteUrl' => $discord->inviteUrl(),
        ]);
    }
}
