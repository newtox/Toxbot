<?php

namespace App\Livewire;

use App\Models\Guild;
use App\Models\TagGuildBlock;
use App\Models\TicketType;
use App\Services\Discord;
use App\Services\GuildAccess;
use App\Services\ScamList;
use App\Support\DomainList;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class GuildSettings extends Component
{
    private const TEXT_CHANNEL_TYPES = [0, 5];

    private const CATEGORY_TYPE = 4;

    public const SECTIONS = ['general', 'levels', 'streams', 'tags', 'moderation', 'protection', 'tickets'];

    #[Locked]
    public string $section = 'general';

    #[Locked]
    public string $guildId;

    #[Locked]
    public string $guildName;

    #[Locked]
    public ?string $guildIcon = null;

    public bool $welcomeEnabled = false;

    public ?string $welcomeChannel = null;

    public string $welcomeMsg = '';

    public bool $byeEnabled = false;

    public ?string $byeChannel = null;

    public string $byeMsg = '';

    public ?string $autorole = null;

    public bool $tagsEnabled = true;

    public bool $levelupEnabled = false;

    public ?string $levelupChannel = null;

    public ?string $streamChannel = null;

    public ?string $streamRole = null;

    public string $streamMsg = '';

    public string $streamVideoMsg = '';

    public ?string $liveRole = null;

    public ?string $liveRoleRequired = null;

    public ?string $modlogChannel = null;

    public bool $modDm = true;

    public bool $verifyEnabled = false;

    public ?string $verifyRole = null;

    public ?string $verifyChannel = null;

    public bool $linkInvites = false;

    public bool $linkScam = false;

    public bool $linkAllowlistEnabled = false;

    public string $linkAllowlist = '';

    public string $linkBlocklist = '';

    public string $linkAction = 'delete';

    public array $linkExemptRoles = [];

    public string $scamCheck = '';

    public ?string $scamCheckResult = null;

    public bool $raidEnabled = false;

    public int $raidJoins = 10;

    public int $raidSeconds = 10;

    public int $raidMinutes = 10;

    public bool $raidKick = true;

    public bool $raidLevel = true;

    public int $minAccountAge = 0;

    #[Locked]
    public ?string $raidUntil = null;

    #[Locked]
    public bool $verifyMessagePosted = false;

    public ?string $protectionNotice = null;

    public ?string $ticketCategory = null;

    public ?string $ticketLogChannel = null;

    public int $ticketLimit = 1;

    public ?string $ticketPanelChannel = null;

    public bool $unbanEnabled = false;

    #[Locked]
    public bool $ticketPanelPosted = false;

    public ?string $ticketNotice = null;

    public function mount(array $guild, string $section = 'general'): void
    {
        $this->section = in_array($section, self::SECTIONS, true) ? $section : 'general';
        $this->guildId = $guild['id'];
        $this->guildName = $guild['name'];
        $this->guildIcon = $guild['icon_url'];

        $this->loadFromDatabase();
    }

    public function loadFromDatabase(): void
    {
        $row = Guild::forDiscordId($this->guildId);

        $this->welcomeChannel = $row->welcome_channel;
        $this->welcomeMsg = (string) $row->welcome_msg;
        $this->welcomeEnabled = $row->welcome_channel !== null && $row->welcome_msg !== null;

        $this->byeChannel = $row->bye_channel;
        $this->byeMsg = (string) $row->bye_msg;
        $this->byeEnabled = $row->bye_channel !== null && $row->bye_msg !== null;

        $this->autorole = $row->autorole;

        $this->tagsEnabled = (bool) $row->tags_enabled;
        $this->levelupEnabled = (bool) $row->levelup_enabled;
        $this->levelupChannel = $row->levelup_channel;

        $this->streamChannel = $row->stream;
        $this->streamRole = $row->notify;
        $this->streamMsg = (string) $row->stream_msg;
        $this->streamVideoMsg = (string) $row->stream_video_msg;
        $this->liveRole = $row->live_role;
        $this->liveRoleRequired = $row->live_role_required;
        $this->modlogChannel = $row->modlog_channel;
        $this->modDm = $row->exists ? (bool) $row->mod_dm : true;

        $this->verifyEnabled = (bool) $row->verify_enabled;
        $this->verifyRole = $row->verify_role;
        $this->verifyChannel = $row->verify_channel;
        $this->verifyMessagePosted = $row->verify_message_id !== null;
        $this->linkInvites = (bool) $row->link_invites;
        $this->linkScam = (bool) $row->link_scam;
        $this->linkAllowlistEnabled = (bool) $row->link_allowlist_enabled;
        $this->linkAllowlist = (string) $row->link_allowlist;
        $this->linkBlocklist = (string) $row->link_blocklist;
        $this->linkAction = $row->link_action ?: 'delete';
        $this->linkExemptRoles = array_values(array_filter(explode(',', (string) $row->link_exempt_roles)));
        $this->raidEnabled = (bool) $row->raid_enabled;
        $this->raidJoins = (int) $row->raid_joins;
        $this->raidSeconds = (int) $row->raid_seconds;
        $this->raidMinutes = (int) $row->raid_minutes;
        $this->raidKick = (bool) $row->raid_kick;
        $this->raidLevel = (bool) $row->raid_level;
        $this->minAccountAge = (int) $row->min_account_age;
        $this->raidUntil = $row->raidActive() ? $row->raid_until->toIso8601String() : null;

        $this->ticketCategory = $row->ticket_category;
        $this->ticketLogChannel = $row->ticket_log_channel;
        $this->ticketLimit = (int) $row->ticket_limit;
        $this->ticketPanelChannel = $row->ticket_panel_channel;
        $this->ticketPanelPosted = $row->ticket_panel_message_id !== null;
        $this->unbanEnabled = (bool) $row->unban_enabled;

        $this->resetValidation();
    }

    public function discard(): void
    {
        $this->loadFromDatabase();
    }

    public function refreshDiscordData(): void
    {
        app(Discord::class)->forgetGuildCache($this->guildId);
    }

    public function save(): void
    {
        $discord = app(Discord::class);

        abort_if(app(GuildAccess::class)->find(auth()->user(), $this->guildId) === null, 403);

        $channelIds = collect($this->channelGroups($discord))->flatMap(fn ($g) => array_column($g['channels'], 'id'))->all();
        $categoryIds = array_column($this->categories($discord), 'id');
        $allRoles = collect($this->roles($discord));
        $roleIds = $allRoles->where('assignable', true)->pluck('id')->all();
        $mentionableRoleIds = $allRoles->pluck('id')->all();

        $max = Guild::MAX_MESSAGE_LENGTH;

        $messageRules = fn (bool $enabled) => $enabled
            ? ['required', 'string', "max:{$max}"]
            : ['nullable', 'string', "max:{$max}"];
        $channelRules = fn (bool $enabled) => $enabled
            ? ['required', Rule::in($channelIds)]
            : ['nullable'];

        $rules = [
            'general' => [
                'welcomeChannel' => $channelRules($this->welcomeEnabled),
                'welcomeMsg' => $messageRules($this->welcomeEnabled),
                'byeChannel' => $channelRules($this->byeEnabled),
                'byeMsg' => $messageRules($this->byeEnabled),
                'autorole' => ['nullable', Rule::in($roleIds)],
            ],
            'levels' => [
                'levelupChannel' => ['nullable', Rule::in($channelIds)],
            ],
            'streams' => [
                'streamChannel' => ['nullable', Rule::in($channelIds)],
                'streamRole' => ['nullable', Rule::in($mentionableRoleIds)],
                'streamMsg' => ['nullable', 'string', "max:{$max}"],
                'streamVideoMsg' => ['nullable', 'string', "max:{$max}"],
                'liveRole' => ['nullable', Rule::in($roleIds)],
                'liveRoleRequired' => ['nullable', Rule::in($mentionableRoleIds)],
            ],
            'tags' => [],
            'moderation' => [
                'modlogChannel' => ['nullable', Rule::in($channelIds)],
            ],
            'protection' => [
                'verifyRole' => $this->verifyEnabled ? ['required', Rule::in($roleIds)] : ['nullable'],
                'verifyChannel' => ['nullable', Rule::in($channelIds)],
                'linkAllowlist' => ['nullable', 'string', 'max:4000', $this->domainRule()],
                'linkBlocklist' => ['nullable', 'string', 'max:4000', $this->domainRule()],
                'linkAction' => ['required', Rule::in(Guild::LINK_ACTIONS)],
                'linkExemptRoles' => ['array', 'max:25'],
                'linkExemptRoles.*' => [Rule::in($mentionableRoleIds)],
                'raidJoins' => ['required', 'integer', 'min:3', 'max:100'],
                'raidSeconds' => ['required', 'integer', 'min:5', 'max:300'],
                'raidMinutes' => ['required', 'integer', 'min:1', 'max:1440'],
                'minAccountAge' => ['required', 'integer', 'min:0', 'max:365'],
            ],
            'tickets' => [
                'ticketCategory' => ['nullable', Rule::in($categoryIds)],
                'ticketLogChannel' => ['nullable', Rule::in($channelIds)],
                'ticketPanelChannel' => ['nullable', Rule::in($channelIds)],
                'ticketLimit' => ['required', 'integer', 'min:1', 'max:10'],
            ],
        ];

        $sectionRules = $rules[$this->section];
        if ($sectionRules !== []) {
            $this->validate($sectionRules, [
                '*Channel.required' => __('guild.channel_required'),
                '*Msg.required' => __('guild.message_required'),
                '*Msg.max' => __('guild.message_max', ['max' => $max]),
                '*Channel.in' => __('guild.channel_invalid'),
                'autorole.in' => __('guild.role_invalid'),
                'levelupChannel.in' => __('guild.channel_invalid'),
                'streamChannel.in' => __('guild.channel_invalid'),
                'modlogChannel.in' => __('guild.channel_invalid'),
                'streamRole.in' => __('guild.role_invalid'),
                'liveRole.in' => __('guild.role_invalid'),
                'liveRoleRequired.in' => __('guild.role_invalid'),
                'verifyRole.required' => __('protection.verify_role_required'),
                'verifyRole.in' => __('guild.role_invalid'),
                'verifyChannel.in' => __('guild.channel_invalid'),
                'linkExemptRoles.*' => __('guild.role_invalid'),
                'raidJoins.*' => __('protection.number_range', ['min' => 3, 'max' => 100]),
                'raidSeconds.*' => __('protection.number_range', ['min' => 5, 'max' => 300]),
                'raidMinutes.*' => __('protection.number_range', ['min' => 1, 'max' => 1440]),
                'minAccountAge.*' => __('protection.number_range', ['min' => 0, 'max' => 365]),
                'ticketCategory.in' => __('tickets.category_invalid'),
                'ticketLogChannel.in' => __('guild.channel_invalid'),
                'ticketPanelChannel.in' => __('guild.channel_invalid'),
                'ticketLimit.*' => __('protection.number_range', ['min' => 1, 'max' => 10]),
            ]);
        }

        $fields = match ($this->section) {
            'general' => [
                'welcome_channel' => $this->welcomeEnabled ? $this->welcomeChannel : null,
                'welcome_msg' => $this->welcomeMsg !== '' ? $this->welcomeMsg : null,
                'bye_channel' => $this->byeEnabled ? $this->byeChannel : null,
                'bye_msg' => $this->byeMsg !== '' ? $this->byeMsg : null,
                'autorole' => $this->autorole ?: null,
            ],
            'levels' => [
                'levelup_enabled' => $this->levelupEnabled,
                'levelup_channel' => $this->levelupChannel ?: null,
            ],
            'streams' => [
                'stream' => $this->streamChannel ?: null,
                'notify' => $this->streamRole ?: null,
                'stream_msg' => $this->streamMsg !== '' ? $this->streamMsg : null,
                'stream_video_msg' => $this->streamVideoMsg !== '' ? $this->streamVideoMsg : null,
                'live_role' => $this->liveRole ?: null,
                'live_role_required' => $this->liveRole ? ($this->liveRoleRequired ?: null) : null,
            ],
            'tags' => [
                'tags_enabled' => $this->tagsEnabled,
            ],
            'moderation' => [
                'modlog_channel' => $this->modlogChannel ?: null,
                'mod_dm' => $this->modDm,
            ],
            'protection' => [
                'verify_enabled' => $this->verifyEnabled,
                'verify_role' => $this->verifyRole ?: null,
                'verify_channel' => $this->verifyChannel ?: null,
                'link_invites' => $this->linkInvites,
                'link_scam' => $this->linkScam,
                'link_allowlist_enabled' => $this->linkAllowlistEnabled,
                'link_allowlist' => DomainList::serialize($this->linkAllowlist),
                'link_blocklist' => DomainList::serialize($this->linkBlocklist),
                'link_action' => $this->linkAction,
                'link_exempt_roles' => $this->linkExemptRoles === [] ? null : implode(',', array_unique($this->linkExemptRoles)),
                'raid_enabled' => $this->raidEnabled,
                'raid_joins' => $this->raidJoins,
                'raid_seconds' => $this->raidSeconds,
                'raid_minutes' => $this->raidMinutes,
                'raid_kick' => $this->raidKick,
                'raid_level' => $this->raidLevel,
                'min_account_age' => $this->minAccountAge,
            ],
            'tickets' => [
                'ticket_category' => $this->ticketCategory ?: null,
                'ticket_log_channel' => $this->ticketLogChannel ?: null,
                'ticket_limit' => $this->ticketLimit,
                'ticket_panel_channel' => $this->ticketPanelChannel ?: null,
                'unban_enabled' => $this->unbanEnabled,
            ],
        };

        Guild::forDiscordId($this->guildId)->fill($fields)->save();

        $this->loadFromDatabase();
        $this->dispatch('saved');
    }

    public function postVerifyMessage(): void
    {
        abort_if(app(GuildAccess::class)->find(auth()->user(), $this->guildId) === null, 403);

        $row = Guild::forDiscordId($this->guildId);
        if (! $row->verify_enabled || ! $row->verify_channel) {
            $this->protectionNotice = __('protection.post_needs_settings');

            return;
        }

        $discord = app(Discord::class);
        $payload = [
            'embeds' => [[
                'color' => 0x6DBE33,
                'title' => __('protection.message_title', ['server' => $this->guildName]),
                'description' => __('protection.message_text'),
            ]],
            'components' => [[
                'type' => 1,
                'components' => [[
                    'type' => 2,
                    'style' => 5,
                    'label' => __('protection.message_button'),
                    'url' => route('verify.show', $this->guildId),
                ]],
            ]],
            'allowed_mentions' => ['parse' => []],
        ];

        try {
            $messageId = null;
            if ($row->verify_message_id) {
                try {
                    $discord->editMessage($row->verify_channel, $row->verify_message_id, $payload);
                    $messageId = $row->verify_message_id;
                } catch (RequestException $e) {
                    if ($e->response->status() !== 404) {
                        throw $e;
                    }
                }
            }
            $messageId ??= $discord->sendMessage($row->verify_channel, $payload);
        } catch (RequestException $e) {
            Log::warning('Posting the verification message failed', ['guild' => $this->guildId, 'status' => $e->response->status()]);
            $this->protectionNotice = __('protection.post_failed');

            return;
        }

        $row->forceFill(['verify_message_id' => $messageId])->save();
        $this->verifyMessagePosted = true;
        $this->protectionNotice = __('protection.post_done');
    }

    public function checkScamDomain(ScamList $scamList): void
    {
        $host = DomainList::entries($this->scamCheck)[0] ?? '';
        if ($host === '' || DomainList::invalid($host) !== []) {
            $this->scamCheckResult = __('protection.scam_check_invalid');

            return;
        }

        if ($scamList->count() === 0) {
            $this->scamCheckResult = __('protection.scam_check_unavailable');

            return;
        }

        $match = $scamList->listed($host);
        $this->scamCheckResult = $match
            ? __('protection.scam_check_listed', ['domain' => $match])
            : __('protection.scam_check_clean', ['domain' => $host]);
    }

    public function postTicketPanel(): void
    {
        abort_if(app(GuildAccess::class)->find(auth()->user(), $this->guildId) === null, 403);

        $this->save();

        $row = Guild::forDiscordId($this->guildId);
        $types = TicketType::query()->where('guild', $this->guildId)->orderBy('position')->orderBy('id')->get();
        $missing = match (true) {
            ! $row->ticket_category => 'panel_needs_category',
            ! $row->ticket_panel_channel => 'panel_needs_channel',
            $types->isEmpty() => 'panel_needs_type',
            default => null,
        };
        if ($missing) {
            $this->ticketNotice = __('tickets.'.$missing);

            return;
        }

        $payload = [
            'embeds' => [[
                'color' => 0x5865F2,
                'title' => __('tickets.panel_title'),
                'description' => __('tickets.panel_text'),
                'fields' => $types->map(fn (TicketType $type) => [
                    'name' => trim(($type->emoji && ! str_starts_with($type->emoji, '<') ? $type->emoji.' ' : '').$type->name),
                    'value' => Str::limit($type->description ?: "\u{200B}", 1000),
                    'inline' => false,
                ])->all(),
            ]],
            'components' => [[
                'type' => 1,
                'components' => $types->map(fn (TicketType $type) => array_filter([
                    'type' => 2,
                    'style' => 1,
                    'label' => Str::limit($type->name, 80, ''),
                    'custom_id' => 'ticket_open:'.$type->id,
                    'emoji' => $this->buttonEmoji($type->emoji),
                ]))->all(),
            ]],
            'allowed_mentions' => ['parse' => []],
        ];

        $discord = app(Discord::class);
        try {
            $messageId = null;
            if ($row->ticket_panel_message_id) {
                try {
                    $discord->editMessage($row->ticket_panel_channel, $row->ticket_panel_message_id, $payload);
                    $messageId = $row->ticket_panel_message_id;
                } catch (RequestException $e) {
                    if ($e->response->status() !== 404) {
                        throw $e;
                    }
                }
            }
            $messageId ??= $discord->sendMessage($row->ticket_panel_channel, $payload);
        } catch (RequestException $e) {
            Log::warning('Posting the ticket panel failed', ['guild' => $this->guildId, 'status' => $e->response->status()]);
            $this->ticketNotice = __('protection.post_failed');

            return;
        }

        $row->forceFill(['ticket_panel_message_id' => $messageId])->save();
        $this->ticketPanelPosted = true;
        $this->ticketNotice = __('tickets.panel_done');
    }

    public function endRaid(): void
    {
        abort_if(app(GuildAccess::class)->find(auth()->user(), $this->guildId) === null, 403);

        $row = Guild::forDiscordId($this->guildId);
        if ($row->raidActive()) {
            $row->forceFill(['raid_until' => now()])->save();
        }
        $this->raidUntil = null;
        $this->protectionNotice = __('protection.raid_ending');
    }

    public function unblockTag(int $tagId): void
    {
        abort_if(app(GuildAccess::class)->find(auth()->user(), $this->guildId) === null, 403);

        TagGuildBlock::query()->where('guild', $this->guildId)->where('tag_id', $tagId)->delete();
    }

    public function render()
    {
        $discord = app(Discord::class);

        try {
            $channelGroups = $this->channelGroups($discord);
            $categories = $this->categories($discord);
            $roles = $this->roles($discord);
            $bot = $discord->botUser();
            $loadError = null;
        } catch (RequestException $e) {
            report($e);
            $channelGroups = $categories = $roles = [];
            $bot = null;
            $loadError = __('guild.load_error', ['status' => $e->response->status()]);
        }

        $user = auth()->user();

        $channelNames = [];
        foreach ($channelGroups as $group) {
            foreach ($group['channels'] as $channel) {
                $channelNames[$channel['id']] = $channel['name'];
            }
        }

        $roleMap = [];
        foreach ($roles as $role) {
            $roleMap[$role['id']] = ['name' => $role['name'], 'color' => $role['color']];
        }

        return view('livewire.guild-settings', [
            'blockedTags' => TagGuildBlock::query()->with('tag')->where('guild', $this->guildId)->get()
                ->filter(fn ($block) => $block->tag !== null),
            'channelGroups' => $channelGroups,
            'categories' => $categories,
            'roles' => $roles,
            'loadError' => $loadError,
            'previewContext' => [
                'username' => $user->username,
                'displayName' => $user->display_name,
                'server' => $this->guildName,
                'todayAt' => __('guild.today_at'),
                'unknownChannel' => __('guild.unknown_channel'),
                'unknownRole' => __('guild.unknown_role'),
                'unknownUser' => __('guild.unknown_user'),
                'channels' => (object) $channelNames,
                'roles' => (object) $roleMap,
                'botName' => $bot['global_name'] ?? $bot['username'] ?? 'Toxbot',
                'botAvatar' => isset($bot['avatar'])
                    ? "https://cdn.discordapp.com/avatars/{$bot['id']}/{$bot['avatar']}.png?size=80"
                    : 'https://cdn.discordapp.com/embed/avatars/0.png',
            ],
        ]);
    }

    private function buttonEmoji(?string $emoji): ?array
    {
        if (! $emoji) {
            return null;
        }
        if (preg_match('/^<(a?):(\w+):(\d+)>$/', $emoji, $m)) {
            return ['id' => $m[3], 'name' => $m[2], 'animated' => $m[1] === 'a'];
        }

        return ['name' => $emoji];
    }

    private function categories(Discord $discord): array
    {
        return collect($discord->guildChannels($this->guildId))
            ->where('type', self::CATEGORY_TYPE)
            ->sortBy('position')
            ->map(fn ($c) => ['id' => $c['id'], 'name' => $c['name']])
            ->values()
            ->all();
    }

    private function domainRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            $invalid = DomainList::invalid((string) $value);
            if ($invalid !== []) {
                $fail(__('protection.domains_invalid', ['domains' => implode(', ', array_slice($invalid, 0, 5))]));
            }
        };
    }

    private function channelGroups(Discord $discord): array
    {
        $all = collect($discord->guildChannels($this->guildId));

        $categories = $all->where('type', self::CATEGORY_TYPE)->keyBy('id');

        return $all
            ->whereIn('type', self::TEXT_CHANNEL_TYPES)
            ->groupBy(fn ($c) => $c['parent_id'] ?? '')
            ->map(fn ($channels, $parentId) => [
                'name' => $categories[$parentId]['name'] ?? null,
                'position' => $parentId === '' ? -1 : ($categories[$parentId]['position'] ?? 999),
                'channels' => $channels->sortBy('position')
                    ->map(fn ($c) => ['id' => $c['id'], 'name' => $c['name']])
                    ->values()->all(),
            ])
            ->sortBy('position')
            ->values()
            ->all();
    }

    private function roles(Discord $discord): array
    {
        $roles = collect($discord->guildRoles($this->guildId));
        $botRoleIds = $discord->botMember($this->guildId)['roles'] ?? [];
        $botTop = $roles->whereIn('id', $botRoleIds)->max('position') ?? 0;

        return $roles
            ->reject(fn ($r) => $r['id'] === $this->guildId || ($r['managed'] ?? false))
            ->sortByDesc('position')
            ->map(fn ($r) => [
                'id' => $r['id'],
                'name' => $r['name'],
                'color' => $r['color'] ? sprintf('#%06x', $r['color']) : null,
                'assignable' => $r['position'] < $botTop,
            ])
            ->values()
            ->all();
    }
}
