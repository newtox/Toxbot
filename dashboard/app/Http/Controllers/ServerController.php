<?php

namespace App\Http\Controllers;

use App\Models\Guild;
use App\Models\ModCase;
use App\Models\StreamSubscription;
use App\Models\TagGuildBlock;
use App\Services\Discord;
use App\Services\GuildAccess;
use App\Services\Premium;
use App\Services\Streams\SourceManager;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function __construct(
        private readonly GuildAccess $access,
        private readonly Discord $discord,
    ) {}

    public function index(Request $request): View
    {
        $guilds = $this->access->manageableGuilds($request->user(), fresh: $request->boolean('refresh'));

        return view('servers.index', [
            'withBot' => array_values(array_filter($guilds, fn ($g) => $g['has_bot'])),
            'withoutBot' => array_values(array_filter($guilds, fn ($g) => ! $g['has_bot'])),
            'discord' => $this->discord,
        ]);
    }

    public function show(Request $request, string $guild, Premium $premium, SourceManager $streams): View
    {
        [$found, $guilds] = $this->resolve($request, $guild);

        $row = Guild::forDiscordId($guild);
        $info = rescue(fn () => $this->discord->guildInfo($guild), [], report: false);

        return view('servers.show', [
            'guild' => $found,
            'guilds' => $guilds,
            'members' => $info['approximate_member_count'] ?? null,
            'online' => $info['approximate_presence_count'] ?? null,
            'premium' => $premium->isGuildPremium($guild),
            'streamers' => StreamSubscription::query()->where('guild', $guild)->count(),
            'streamLimit' => $streams->limit($guild),
            'modules' => [
                [
                    'section' => 'general',
                    'icon' => 'welcome',
                    'title' => __('guild.welcome_title'),
                    'description' => __('guild.welcome_description'),
                    'active' => $row->welcome_channel !== null && $row->welcome_msg !== null,
                ],
                [
                    'section' => 'general',
                    'icon' => 'bye',
                    'title' => __('guild.bye_title'),
                    'description' => __('guild.bye_description'),
                    'active' => $row->bye_channel !== null && $row->bye_msg !== null,
                ],
                [
                    'section' => 'general',
                    'icon' => 'autorole',
                    'title' => __('guild.autorole_title'),
                    'description' => __('guild.autorole_description'),
                    'active' => $row->autorole !== null,
                ],
                [
                    'section' => 'levels',
                    'icon' => 'level',
                    'title' => __('guild.levelup_title'),
                    'description' => __('guild.levelup_description'),
                    'active' => (bool) $row->levelup_enabled,
                ],
                [
                    'section' => 'streams',
                    'icon' => 'stream',
                    'title' => __('streams.title'),
                    'description' => __('streams.description'),
                    'active' => $row->stream !== null,
                ],
                [
                    'section' => 'streams',
                    'icon' => 'live',
                    'title' => __('streams.live_role_title'),
                    'description' => __('streams.live_role_description'),
                    'active' => $row->live_role !== null,
                ],
                [
                    'section' => 'tags',
                    'icon' => 'tag',
                    'title' => __('guild.tags_title'),
                    'description' => __('guild.tags_description'),
                    'active' => $row->exists ? (bool) $row->tags_enabled : true,
                    'extra' => trans_choice('overview.blocked_tags', TagGuildBlock::query()->where('guild', $guild)->count()),
                ],
                [
                    'section' => 'moderation',
                    'icon' => 'shield',
                    'title' => __('moderation.settings_title'),
                    'description' => __('moderation.settings_description'),
                    'active' => $row->modlog_channel !== null,
                    'extra' => trans_choice('moderation.cases_count', ModCase::query()->forGuild($guild)->count()),
                ],
            ],
        ]);
    }

    public function settings(Request $request, string $guild, string $section): View
    {
        [$found, $guilds] = $this->resolve($request, $guild);

        return view('servers.settings', ['guild' => $found, 'guilds' => $guilds, 'section' => $section]);
    }

    public function members(Request $request, string $guild): View
    {
        [$found, $guilds] = $this->resolve($request, $guild);

        return view('servers.members', ['guild' => $found, 'guilds' => $guilds]);
    }

    public function member(Request $request, string $guild, string $user): View
    {
        [$found, $guilds] = $this->resolve($request, $guild);

        return view('servers.member', ['guild' => $found, 'guilds' => $guilds, 'userId' => $user]);
    }

    public function modlog(Request $request, string $guild): View
    {
        [$found, $guilds] = $this->resolve($request, $guild);

        return view('servers.modlog', ['guild' => $found, 'guilds' => $guilds]);
    }

    private function resolve(Request $request, string $guild): array
    {
        $guilds = array_values(array_filter(
            $this->access->manageableGuilds($request->user()),
            fn ($g) => $g['has_bot'],
        ));

        $found = collect($guilds)->firstWhere('id', $guild);

        abort_if($found === null, 403, __('servers.forbidden'));

        return [$found, $guilds];
    }
}
