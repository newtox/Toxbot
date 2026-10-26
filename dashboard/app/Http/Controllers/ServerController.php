<?php

namespace App\Http\Controllers;

use App\Livewire\GuildSettings;
use App\Models\Guild;
use App\Models\ModCase;
use App\Models\StarboardPost;
use App\Models\StreamSubscription;
use App\Models\TagGuildBlock;
use App\Models\Ticket;
use App\Services\Discord;
use App\Services\GuildAccess;
use App\Services\Premium;
use App\Services\Streams\SourceManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function __construct(
        private readonly GuildAccess $access,
        private readonly Discord $discord,
    ) {}

    public function index(Request $request): View
    {
        $guilds = $this->access->guilds($request->user(), fresh: $request->boolean('refresh'));

        return view('servers.index', [
            'withBot' => array_values(array_filter($guilds, fn ($g) => $g['has_bot'])),
            'withoutBot' => array_values(array_filter($guilds, fn ($g) => $g['manage'] && ! $g['has_bot'])),
            'discord' => $this->discord,
        ]);
    }

    public function show(Request $request, string $guild, Premium $premium, SourceManager $streams): View|RedirectResponse
    {
        [$found, $guilds] = $this->resolve($request, $guild, null);
        if (! $found['manage']) {
            return redirect()->route('servers.members', $guild);
        }

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
                    'section' => 'welcome',
                    'icon' => 'welcome',
                    'title' => __('guild.welcome_title'),
                    'description' => __('guild.welcome_description'),
                    'active' => $row->welcome_channel !== null && $row->welcome_msg !== null,
                ],
                [
                    'section' => 'goodbye',
                    'icon' => 'bye',
                    'title' => __('guild.bye_title'),
                    'description' => __('guild.bye_description'),
                    'active' => $row->bye_channel !== null && $row->bye_msg !== null,
                ],
                [
                    'section' => 'autorole',
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
                    'active' => (bool) $row->levelup_enabled || (bool) $row->levelup_dm,
                ],
                [
                    'section' => 'streams',
                    'icon' => 'stream',
                    'title' => __('streams.title'),
                    'description' => __('streams.description'),
                    'active' => $row->stream !== null,
                ],
                [
                    'section' => 'liverole',
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
                    'section' => 'starboard',
                    'icon' => 'star',
                    'title' => __('starboard.settings_title'),
                    'description' => __('starboard.settings_description'),
                    'active' => $row->starboard_channel !== null,
                    'extra' => trans_choice('starboard.count', StarboardPost::query()->forGuild($guild)->count()),
                ],
                [
                    'section' => 'moderation',
                    'icon' => 'shield',
                    'title' => __('moderation.settings_title'),
                    'description' => __('moderation.settings_description'),
                    'active' => $row->modlog_channel !== null,
                    'extra' => trans_choice('moderation.cases_count', ModCase::query()->forGuild($guild)->count()),
                ],
                [
                    'section' => 'tickets',
                    'icon' => 'ticket',
                    'title' => __('tickets.title'),
                    'description' => __('tickets.description'),
                    'active' => $row->ticket_category !== null,
                    'extra' => trans_choice('tickets.open_count', Ticket::query()->forGuild($guild)->where('status', 'open')->count()),
                ],
                [
                    'section' => 'verification',
                    'icon' => 'lock',
                    'title' => __('protection.verify_title'),
                    'description' => __('protection.verify_description'),
                    'active' => (bool) $row->verify_enabled,
                ],
                [
                    'section' => 'links',
                    'icon' => 'link',
                    'title' => __('protection.links_title'),
                    'description' => __('protection.links_description'),
                    'active' => $row->link_invites || $row->link_scam || $row->link_allowlist_enabled || $row->link_blocklist !== null,
                ],
                [
                    'section' => 'raid',
                    'icon' => 'alert',
                    'title' => __('protection.raid_title'),
                    'description' => __('protection.raid_description'),
                    'active' => (bool) $row->raid_enabled || $row->min_account_age > 0,
                ],
            ],
        ]);
    }

    public function settings(Request $request, string $guild, string $section): View|RedirectResponse
    {
        if (isset(GuildSettings::LEGACY_SECTIONS[$section])) {
            return redirect()->route('servers.settings', [$guild, GuildSettings::LEGACY_SECTIONS[$section]]);
        }

        $public = $section === GuildSettings::STARBOARD;
        [$found, $guilds] = $this->resolve($request, $guild, $public ? null : 'manage');

        if (! $found['manage']) {
            $settings = Guild::query()->find($guild);

            return view('servers.starboard', [
                'guild' => $found,
                'guilds' => $guilds,
                'threshold' => $settings?->starboard_channel ? $settings->starboard_threshold : null,
                'emoji' => collect(($settings ?? new Guild)->starboardEmojis())
                    ->map(fn (string $code) => Blade::render('<x-emoji :code="$code" />', ['code' => $code]))
                    ->implode(' '),
            ]);
        }

        return view('servers.settings', ['guild' => $found, 'guilds' => $guilds, 'section' => $section]);
    }

    public function tickets(Request $request, string $guild): View
    {
        [$found, $guilds] = $this->resolve($request, $guild, 'tickets');
        $types = $this->access->ticketTypeIds($request->user(), $found);
        $status = in_array($request->query('status'), ['open', 'closed'], true) ? $request->query('status') : null;

        return view('servers.tickets', [
            'guild' => $found,
            'guilds' => $guilds,
            'status' => $status,
            'tickets' => Ticket::query()->forGuild($guild)
                ->when($types !== null, fn ($query) => $query->whereIn('type_id', $types))
                ->when($status, fn ($query) => $query->where('status', $status))
                ->orderByDesc('number')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function ticket(Request $request, string $guild, int $ticket): View
    {
        [$found, $guilds] = $this->resolve($request, $guild, 'tickets');
        $types = $this->access->ticketTypeIds($request->user(), $found);

        return view('servers.ticket', [
            'guild' => $found,
            'guilds' => $guilds,
            'ticket' => Ticket::query()->forGuild($guild)
                ->when($types !== null, fn ($query) => $query->whereIn('type_id', $types))
                ->with('messages')
                ->findOrFail($ticket),
        ]);
    }

    public function appeals(Request $request, string $guild): View
    {
        [$found, $guilds] = $this->resolve($request, $guild, 'appeals');

        return view('servers.appeals', ['guild' => $found, 'guilds' => $guilds]);
    }

    public function data(Request $request, string $guild): View
    {
        [$found, $guilds] = $this->resolve($request, $guild);

        return view('servers.data', ['guild' => $found, 'guilds' => $guilds]);
    }

    public function members(Request $request, string $guild): View
    {
        [$found, $guilds] = $this->resolve($request, $guild, null);

        return view('servers.members', ['guild' => $found, 'guilds' => $guilds]);
    }

    public function member(Request $request, string $guild, string $user): View
    {
        [$found, $guilds] = $this->resolve($request, $guild, null);

        return view('servers.member', ['guild' => $found, 'guilds' => $guilds, 'userId' => $user]);
    }

    public function modlog(Request $request, string $guild): View
    {
        [$found, $guilds] = $this->resolve($request, $guild, 'moderate');

        return view('servers.modlog', ['guild' => $found, 'guilds' => $guilds]);
    }

    private function resolve(Request $request, string $guild, ?string $ability = 'manage'): array
    {
        $guilds = $this->access->dashboardGuilds($request->user());

        $found = collect($guilds)->firstWhere('id', $guild);
        abort_if($found === null, 403, __('servers.forbidden'));

        $found['tickets'] = $this->access->canSeeTickets($request->user(), $found);

        abort_if($ability !== null && ! $found[$ability], 403, __('servers.forbidden'));

        return [$found, $guilds];
    }
}
