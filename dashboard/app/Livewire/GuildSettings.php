<?php

namespace App\Livewire;

use App\Models\Guild;
use App\Services\Discord;
use App\Services\GuildAccess;
use Illuminate\Http\Client\RequestException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class GuildSettings extends Component
{
    private const TEXT_CHANNEL_TYPES = [0, 5];

    private const CATEGORY_TYPE = 4;

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

    public function mount(array $guild): void
    {
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
        $roleIds = collect($this->roles($discord))->where('assignable', true)->pluck('id')->all();

        $max = Guild::MAX_MESSAGE_LENGTH;

        $messageRules = fn (bool $enabled) => $enabled
            ? ['required', 'string', "max:{$max}"]
            : ['nullable', 'string', "max:{$max}"];
        $channelRules = fn (bool $enabled) => $enabled
            ? ['required', Rule::in($channelIds)]
            : ['nullable'];

        $this->validate([
            'welcomeChannel' => $channelRules($this->welcomeEnabled),
            'welcomeMsg' => $messageRules($this->welcomeEnabled),
            'byeChannel' => $channelRules($this->byeEnabled),
            'byeMsg' => $messageRules($this->byeEnabled),
            'autorole' => ['nullable', Rule::in($roleIds)],
        ], [
            '*Channel.required' => __('guild.channel_required'),
            '*Msg.required' => __('guild.message_required'),
            '*Msg.max' => __('guild.message_max', ['max' => $max]),
            '*Channel.in' => __('guild.channel_invalid'),
            'autorole.in' => __('guild.role_invalid'),
        ]);

        $row = Guild::forDiscordId($this->guildId);
        $row->fill([
            'welcome_channel' => $this->welcomeEnabled ? $this->welcomeChannel : null,
            'welcome_msg' => $this->welcomeMsg !== '' ? $this->welcomeMsg : null,
            'bye_channel' => $this->byeEnabled ? $this->byeChannel : null,
            'bye_msg' => $this->byeMsg !== '' ? $this->byeMsg : null,
            'autorole' => $this->autorole ?: null,
        ])->save();

        $this->loadFromDatabase();
        $this->dispatch('saved');
    }

    public function render()
    {
        $discord = app(Discord::class);

        try {
            $channelGroups = $this->channelGroups($discord);
            $roles = $this->roles($discord);
            $bot = $discord->botUser();
            $loadError = null;
        } catch (RequestException $e) {
            report($e);
            $channelGroups = $roles = [];
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
            'channelGroups' => $channelGroups,
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
