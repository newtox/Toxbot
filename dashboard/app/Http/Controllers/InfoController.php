<?php

namespace App\Http\Controllers;

use App\Models\BotStatus;
use App\Services\Discord;
use App\Support\BotStats;
use Illuminate\Foundation\Application;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class InfoController extends Controller
{
    public function general(Discord $discord): View
    {
        $repository = rtrim((string) config('toxbot.repository'), '/');

        return view('info.general', [
            'avatar' => rescue(fn () => $discord->botAvatarUrl(), null, report: false),
            'inviteUrl' => $discord->inviteUrl(),
            'topggUrl' => 'https://top.gg/bot/'.config('services.discord.client_id'),
            'supportInvite' => config('toxbot.support_invite'),
            'contactEmail' => config('toxbot.contact_email'),
            'repository' => $repository,
        ]);
    }

    public function commands(): View
    {
        return view('info.commands');
    }

    public function system(Discord $discord): View
    {
        $repository = rtrim((string) config('toxbot.repository'), '/');
        $status = BotStatus::current();
        $commit = config('toxbot.commit');
        $buildDate = config('toxbot.build_date');

        return view('info.system', [
            'status' => $status,
            'stats' => BotStats::summary($discord),
            'repository' => $repository,
            'dashboard' => [
                'commit' => $commit,
                'built' => $buildDate ? rescue(fn () => Carbon::parse($buildDate), null, report: false) : null,
                'php' => PHP_VERSION,
                'laravel' => Application::VERSION,
                'os' => PHP_OS_FAMILY,
            ],
            'botBuilt' => $status?->build_date ? rescue(fn () => Carbon::parse($status->build_date), null, report: false) : null,
        ]);
    }
}
