<?php

namespace App\Services;

use App\Models\Guild;
use App\Models\TicketType;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class AutoSetup
{
    public const AREAS = ['tickets', 'moderation', 'protection', 'verification'];

    public const TEAM_PERMISSIONS = 0x8 | 0x20 | 0x2 | 0x4 | 0x2000 | 0x10000000000;

    private const TEXT = 0;

    private const CATEGORY = 4;

    private const ROLE = 0;

    private const MEMBER = 1;

    private const VIEW = 0x400;

    private const SEND = 0x800;

    private const EMBED = 0x4000;

    private const ATTACH = 0x8000;

    private const HISTORY = 0x10000;

    private const MANAGE_CHANNELS = 0x10;

    private const MANAGE_ROLES = 0x10000000;

    private const ADMINISTRATOR = 0x8;

    private const READ_ONLY = 0x800 | 0x40 | (1 << 35) | (1 << 36) | (1 << 38);

    private const BOT_ACCESS = self::VIEW | self::SEND | self::EMBED | self::ATTACH | self::HISTORY;

    private const TEAM_ACCESS = self::VIEW | self::SEND | self::EMBED | self::ATTACH | self::HISTORY;

    private string $guildId;

    private string $botId;

    private string $reason;

    private array $channels = [];

    private array $roles = [];

    private array $teamRoles = [];

    private array $steps = [];

    private string $locale;

    public function __construct(private Discord $discord, private SetupMessages $messages) {}

    public function run(string $guildId, string $guildName, array $areas, array $teamRoles, string $userName): array
    {
        $this->guildId = $guildId;
        $this->teamRoles = array_values(array_unique($teamRoles));
        $this->reason = __('setup.audit_reason', ['user' => $userName]);
        $this->steps = [];

        $row = Guild::forDiscordId($guildId);
        $row->rememberLanguage();
        $this->locale = $row->messageLocale();

        $this->discord->forgetGuildCache($guildId);
        try {
            $this->botId = $this->discord->botUser()['id'];
            $this->channels = $this->discord->guildChannels($guildId);
            $this->roles = $this->discord->guildRoles($guildId);
            $permissions = $this->botPermissions();
        } catch (RequestException $e) {
            $this->fail(__('setup.load_failed', ['status' => $e->response->status()]));

            return $this->steps;
        }

        $missing = array_keys(array_filter([
            __('setup.permission_channels') => ! $this->has($permissions, self::MANAGE_CHANNELS),
            __('setup.permission_roles') => ! $this->has($permissions, self::MANAGE_ROLES),
        ]));
        if ($missing !== []) {
            $this->fail(__('setup.missing_permissions', ['permissions' => implode(', ', $missing)]));

            return $this->steps;
        }

        $row->save();

        foreach (self::AREAS as $area) {
            if (in_array($area, $areas, true)) {
                $this->{$area}($guildName);
            }
        }

        $this->discord->forgetGuildCache($guildId);

        return $this->steps;
    }

    private function moderation(): void
    {
        $row = Guild::forDiscordId($this->guildId);
        $channel = $this->ensureChannel($row->modlog_channel, 'channel_modlog', self::TEXT, null, $this->privateOverwrites());

        $row->fill(['modlog_channel' => $channel ?? $row->modlog_channel, 'mod_dm' => true])->save();
        $this->done(__('setup.step_mod_dm'));
    }

    private function tickets(): void
    {
        $row = Guild::forDiscordId($this->guildId);

        $category = $this->ensureChannel($row->ticket_category, 'channel_ticket_category', self::CATEGORY, null, []);
        if ($category === null) {
            return;
        }
        $panel = $this->ensureChannel($row->ticket_panel_channel, 'channel_tickets', self::TEXT, $category, $this->readOnlyOverwrites());
        $log = $this->ensureChannel($row->ticket_log_channel, 'channel_ticket_log', self::TEXT, $category, $this->privateOverwrites());

        $row->fill([
            'ticket_category' => $category,
            'ticket_panel_channel' => $panel ?? $row->ticket_panel_channel,
            'ticket_log_channel' => $log ?? $row->ticket_log_channel,
        ])->save();

        if ($this->messages->ticketTypes($this->guildId)->isEmpty()) {
            TicketType::create([
                'guild' => $this->guildId,
                'name' => __('setup.ticket_type_name', [], $this->locale),
                'emoji' => '🎫',
                'description' => __('setup.ticket_type_text', [], $this->locale),
                'roles' => $this->teamRoles === [] ? null : implode(',', $this->teamRoles),
                'position' => 0,
            ]);
            $this->done(__('setup.step_ticket_type', ['name' => __('setup.ticket_type_name', [], $this->locale)]));
        } else {
            $this->done(__('setup.step_ticket_types_kept'));
        }

        if (! $row->ticket_panel_channel) {
            return;
        }
        try {
            $this->messages->postTicketPanel($row);
            $this->done(__('setup.step_panel', ['channel' => $this->label($row->ticket_panel_channel)]));
        } catch (RequestException $e) {
            Log::warning('Auto setup could not post the ticket panel', ['guild' => $this->guildId, 'status' => $e->response->status()]);
            $this->fail(__('setup.step_panel_failed'));
        }
    }

    private function protection(): void
    {
        $row = Guild::forDiscordId($this->guildId);
        $exempt = array_values(array_unique(array_merge(
            array_filter(explode(',', (string) $row->link_exempt_roles)),
            $this->teamRoles,
        )));

        $row->fill([
            'link_scam' => true,
            'link_invites' => true,
            'link_exempt_roles' => $exempt === [] ? null : implode(',', array_slice($exempt, 0, 25)),
            'raid_enabled' => true,
        ])->save();

        $this->done(__('setup.step_links'));
        if ($this->teamRoles !== []) {
            $this->done(__('setup.step_links_exempt', ['roles' => $this->roleNames($this->teamRoles)]));
        }
        $this->done(__('setup.step_raid', ['joins' => $row->raid_joins, 'seconds' => $row->raid_seconds]));
    }

    private function verification(string $guildName): void
    {
        $row = Guild::forDiscordId($this->guildId);

        $role = $this->ensureVerifyRole($row->verify_role);
        if ($role === null) {
            return;
        }

        $overwrites = [
            ['id' => $this->guildId, 'type' => self::ROLE, 'allow' => '0', 'deny' => (string) self::VIEW],
            ['id' => $this->botId, 'type' => self::MEMBER, 'allow' => (string) self::BOT_ACCESS, 'deny' => '0'],
            ['id' => $role, 'type' => self::ROLE, 'allow' => (string) (self::VIEW | self::HISTORY), 'deny' => (string) self::READ_ONLY],
            ...$this->teamOverwrites(),
        ];
        $reused = $this->found($row->verify_channel, 'channel_verify', self::TEXT) !== null;
        $channel = $this->ensureChannel($row->verify_channel, 'channel_verify', self::TEXT, null, $overwrites);
        if ($channel === null) {
            return;
        }

        if ($reused) {
            $this->permit($channel, $role, self::VIEW | self::HISTORY, self::READ_ONLY);
        }

        $hidden = 0;
        $failed = 0;
        $others = collect($this->channels)
            ->reject(fn ($c) => $c['id'] === $channel)
            ->sortBy(fn ($c) => $c['type'] === self::CATEGORY ? 0 : 1);
        foreach ($others as $other) {
            if ($this->alreadyHidden($other, $role)) {
                continue;
            }
            $this->permit($other['id'], $role, 0, self::VIEW) ? $hidden++ : $failed++;
        }
        $this->done(trans_choice('setup.step_hidden', $hidden, ['count' => $hidden]));
        if ($failed > 0) {
            $this->fail(trans_choice('setup.step_hidden_failed', $failed, ['count' => $failed]));
        }

        $row->fill(['verify_enabled' => true, 'verify_role' => $role, 'verify_channel' => $channel])->save();
        $this->done(__('setup.step_verify_on'));

        try {
            $this->messages->postVerifyMessage($row, $guildName);
            $this->done(__('setup.step_verify_message', ['channel' => $this->label($channel)]));
        } catch (RequestException $e) {
            Log::warning('Auto setup could not post the verification message', ['guild' => $this->guildId, 'status' => $e->response->status()]);
            $this->fail(__('setup.step_verify_message_failed'));
        }
    }

    private function ensureVerifyRole(?string $configured): ?string
    {
        $names = $this->names('role_unverified');
        $existing = collect($this->roles)->first(fn ($r) => $r['id'] === $configured)
            ?? collect($this->roles)->first(fn ($r) => in_array(mb_strtolower($r['name']), $names, true));

        if ($existing) {
            if ($existing['position'] >= $this->botTopPosition()) {
                $this->fail(__('setup.step_role_above_bot', ['role' => $existing['name']]));

                return null;
            }
            $this->done(__('setup.step_role_reused', ['role' => $existing['name']]));

            return $existing['id'];
        }

        try {
            $created = $this->discord->createRole($this->guildId, [
                'name' => __('setup.role_unverified', [], $this->locale),
                'permissions' => '0',
                'mentionable' => false,
                'hoist' => false,
            ], $this->reason);
        } catch (RequestException $e) {
            $this->fail(__('setup.step_role_failed', ['role' => __('setup.role_unverified', [], $this->locale)]));

            return null;
        }

        $this->roles[] = $created;
        $this->done(__('setup.step_role_created', ['role' => $created['name']]));

        return $created['id'];
    }

    private function ensureChannel(?string $configured, string $key, int $type, ?string $parent, array $overwrites): ?string
    {
        $existing = $this->found($configured, $key, $type);
        if ($existing !== null) {
            $this->done(__('setup.step_channel_reused', ['what' => __('setup.what_'.$key), 'channel' => $this->label($existing['id'])]));

            return $existing['id'];
        }

        try {
            $created = $this->discord->createChannel($this->guildId, array_filter([
                'name' => __('setup.'.$key, [], $this->locale),
                'type' => $type,
                'parent_id' => $parent,
                'permission_overwrites' => $overwrites,
            ], fn ($value) => $value !== null), $this->reason);
        } catch (RequestException $e) {
            Log::warning('Auto setup could not create a channel', ['guild' => $this->guildId, 'channel' => $key, 'status' => $e->response->status()]);
            $this->fail(__('setup.step_channel_failed', ['what' => __('setup.what_'.$key)]));

            return null;
        }

        $this->channels[] = $created;
        $this->done(__('setup.step_channel_created', ['what' => __('setup.what_'.$key), 'channel' => $this->label($created['id'])]));

        return $created['id'];
    }

    private function found(?string $configured, string $key, int $type): ?array
    {
        $channels = collect($this->channels);
        if ($configured) {
            $match = $channels->first(fn ($c) => $c['id'] === $configured);
            if ($match) {
                return $match;
            }
        }

        $names = $this->names($key);

        return $channels->first(fn ($c) => $c['type'] === $type && in_array(mb_strtolower($c['name']), $names, true));
    }

    private function names(string $key): array
    {
        return array_values(array_unique(array_map('mb_strtolower', [
            __('setup.'.$key),
            __('setup.'.$key, [], $this->locale),
            __('setup.'.$key, [], 'en'),
        ])));
    }

    private function permit(string $channelId, string $roleId, int $allow, int $deny): bool
    {
        try {
            $this->discord->setChannelPermission($channelId, $roleId, self::ROLE, $allow, $deny, $this->reason);

            return true;
        } catch (RequestException) {
            return false;
        }
    }

    private function alreadyHidden(array $channel, string $roleId): bool
    {
        foreach ($channel['permission_overwrites'] ?? [] as $overwrite) {
            if ($overwrite['id'] === $roleId && ((int) $overwrite['deny'] & self::VIEW)) {
                return true;
            }
        }

        return false;
    }

    private function privateOverwrites(): array
    {
        return [
            ['id' => $this->guildId, 'type' => self::ROLE, 'allow' => '0', 'deny' => (string) self::VIEW],
            ['id' => $this->botId, 'type' => self::MEMBER, 'allow' => (string) self::BOT_ACCESS, 'deny' => '0'],
            ...$this->teamOverwrites(),
        ];
    }

    private function readOnlyOverwrites(): array
    {
        return [
            ['id' => $this->guildId, 'type' => self::ROLE, 'allow' => (string) (self::VIEW | self::HISTORY), 'deny' => (string) self::READ_ONLY],
            ['id' => $this->botId, 'type' => self::MEMBER, 'allow' => (string) self::BOT_ACCESS, 'deny' => '0'],
        ];
    }

    private function teamOverwrites(): array
    {
        return array_map(fn ($id) => ['id' => $id, 'type' => self::ROLE, 'allow' => (string) self::TEAM_ACCESS, 'deny' => '0'], $this->teamRoles);
    }

    private function botPermissions(): int
    {
        $botRoles = $this->discord->botMember($this->guildId)['roles'] ?? [];
        $value = 0;
        foreach ($this->roles as $role) {
            if ($role['id'] === $this->guildId || in_array($role['id'], $botRoles, true)) {
                $value |= (int) ($role['permissions'] ?? 0);
            }
        }

        return $value;
    }

    private function botTopPosition(): int
    {
        $botRoles = $this->discord->botMember($this->guildId)['roles'] ?? [];

        return (int) collect($this->roles)->whereIn('id', $botRoles)->max('position');
    }

    private function has(int $permissions, int $flag): bool
    {
        return ($permissions & self::ADMINISTRATOR) === self::ADMINISTRATOR || ($permissions & $flag) === $flag;
    }

    private function label(string $channelId): string
    {
        $channel = collect($this->channels)->first(fn ($c) => $c['id'] === $channelId);
        if (! $channel) {
            return '#?';
        }

        return $channel['type'] === self::CATEGORY ? $channel['name'] : '#'.$channel['name'];
    }

    private function roleNames(array $ids): string
    {
        return collect($this->roles)->whereIn('id', $ids)->pluck('name')->map(fn ($name) => '@'.$name)->implode(', ');
    }

    private function done(string $text): void
    {
        $this->steps[] = ['ok' => true, 'text' => $text];
    }

    private function fail(string $text): void
    {
        $this->steps[] = ['ok' => false, 'text' => $text];
    }
}
