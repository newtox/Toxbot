<?php

namespace App\Services;

use App\Models\Guild;
use App\Models\GuildEvent;
use App\Models\GuildEventInvite;
use Throwable;

class GuildEvents
{
    public const MAX_EVENTS = 10;

    public const MAX_INVITES = 50;

    public const MAX_NAME = 50;

    public const MAX_VOICE = 5;

    public const JOIN_PREFIX = 'event_join:';

    public const LEAVE_PREFIX = 'event_leave:';

    private const ROLE = 0;

    private const MEMBER = 1;

    private const VIEW = 1 << 10;

    private const SEND = 1 << 11;

    private const HISTORY = 1 << 16;

    private const EMBED = 1 << 14;

    private const ATTACH = 1 << 15;

    private const REACT = 1 << 6;

    private const MANAGE_MESSAGES = 1 << 13;

    private const MENTION_EVERYONE = 1 << 17;

    private const CONNECT = 1 << 20;

    private const SPEAK = 1 << 21;

    private const STREAM = 1 << 9;

    private const VOICE_ACTIVITY = 1 << 25;

    private const MANAGE_CHANNELS = 1 << 4;

    private const THREADS = (1 << 35) | (1 << 36) | (1 << 38);

    private const BOT_ACCESS = self::VIEW | self::SEND | self::HISTORY | self::EMBED | self::MANAGE_CHANNELS | self::CONNECT;

    private const MEMBER_ACCESS = self::VIEW | self::SEND | self::HISTORY | self::EMBED | self::ATTACH | self::REACT
        | self::CONNECT | self::SPEAK | self::STREAM | self::VOICE_ACTIVITY;

    private const TEAM_ACCESS = self::MEMBER_ACCESS | self::MANAGE_MESSAGES | self::MENTION_EVERYONE;

    private const READ_ACCESS = self::VIEW | self::HISTORY | self::REACT;

    public function __construct(
        private readonly Discord $discord,
        private readonly GuildMembers $members,
    ) {}

    public function create(string $guildId, string $name, string $creatorId, ?string $teamRoleId, int $voiceCount = 1): GuildEvent
    {
        $reason = 'Toxbot event: '.$name;
        $botId = (string) $this->discord->botUser()['id'];
        $created = ['role' => null, 'channels' => []];

        try {
            $role = $this->discord->createRole($guildId, [
                'name' => $name, 'permissions' => '0', 'mentionable' => false, 'hoist' => false,
            ], $reason);
            $created['role'] = $role['id'];

            $overwrites = $this->overwrites($guildId, $botId, $role['id'], $creatorId, $teamRoleId);
            $category = $this->discord->createChannel($guildId, ['name' => $name, 'type' => 4, 'permission_overwrites' => $overwrites], $reason);
            $created['channels'][] = $category['id'];

            $channel = fn (string $channelName, int $type, array $overwrites) => $this->discord->createChannel($guildId, [
                'name' => $channelName,
                'type' => $type,
                'parent_id' => $category['id'],
                'permission_overwrites' => $overwrites,
            ], $reason);

            $info = $channel($this->text($guildId, 'channel_info'), 0, $this->overwrites($guildId, $botId, $role['id'], $creatorId, $teamRoleId, true));
            array_unshift($created['channels'], $info['id']);
            $text = $channel($this->text($guildId, 'channel_chat'), 0, $overwrites);
            array_unshift($created['channels'], $text['id']);
            $voiceName = $this->text($guildId, 'channel_voice');
            $count = max(1, min(self::MAX_VOICE, $voiceCount));
            $voices = [];
            for ($i = 1; $i <= $count; $i++) {
                $voice = $channel($count === 1 ? $voiceName : "{$voiceName} {$i}", 2, $overwrites);
                $voices[] = $voice['id'];
                array_unshift($created['channels'], $voice['id']);
            }

            $event = GuildEvent::query()->create([
                'guild' => $guildId,
                'name' => $name,
                'role_id' => $role['id'],
                'team_role_id' => $teamRoleId,
                'category_id' => $category['id'],
                'info_channel_id' => $info['id'],
                'text_channel_id' => $text['id'],
                'voice_channel_id' => $voices[0],
                'extra_voice_channel_ids' => count($voices) > 1 ? implode(',', array_slice($voices, 1)) : null,
                'created_by' => $creatorId,
            ]);
        } catch (Throwable $e) {
            $this->deleteParts($guildId, $created['role'], $created['channels'], $reason);

            throw $e;
        }

        rescue(fn () => $this->discord->addMemberRole($guildId, $creatorId, $role['id'], $reason), report: false);

        return $event;
    }

    public function end(GuildEvent $event): void
    {
        $this->deleteParts(
            $event->guild,
            $event->role_id,
            [...$event->voiceChannelIds(), $event->text_channel_id, $event->info_channel_id, $event->category_id],
            'Toxbot event ended',
        );
        $event->invites()->delete();
        $event->delete();
    }

    public function participants(GuildEvent $event): array
    {
        if (! $event->role_id) {
            return [];
        }

        return collect($this->discord->guildMembers($event->guild))
            ->filter(fn ($member) => in_array($event->role_id, $member['roles'] ?? [], true))
            ->map(fn ($member) => GuildMembers::card($event->guild, $member))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    public function invitations(GuildEvent $event, array $participantIds): array
    {
        $members = collect($this->discord->guildMembers($event->guild))->keyBy(fn ($member) => (string) $member['user']['id']);
        $grouped = [GuildEventInvite::PENDING => [], GuildEventInvite::DECLINED => []];
        foreach ($event->invites()->orderBy('updated_at')->get() as $invite) {
            $member = $members->get($invite->user_id);
            if ($member && ! in_array($invite->user_id, $participantIds, true) && isset($grouped[$invite->status])) {
                $grouped[$invite->status][] = GuildMembers::card($event->guild, $member);
            }
        }

        return $grouped;
    }

    public function searchMembers(string $guildId, string $query, array $exclude = [], ?string $roleId = null, array $statuses = [], int $limit = 8): array
    {
        return array_map(fn ($member) => [
            ...$member,
            'status' => $roleId && in_array($roleId, $member['roles'], true) ? 'joined' : ($statuses[$member['id']] ?? null),
        ], $this->members->search($guildId, $query, $exclude, $limit));
    }

    public function member(string $guildId, string $userId): ?array
    {
        return $this->members->member($guildId, $userId);
    }

    public function inviteTargets(string $guildId, array $userIds, array $roleIds): array
    {
        $targets = array_fill_keys(array_map('strval', $userIds), true);
        if ($roleIds !== []) {
            foreach ($this->discord->guildMembers($guildId) as $member) {
                if (! ($member['user']['bot'] ?? false) && array_intersect($roleIds, $member['roles'] ?? []) !== []) {
                    $targets[(string) $member['user']['id']] = true;
                }
            }
        }

        return array_keys($targets);
    }

    public function invite(GuildEvent $event, array $userIds, ?string $message, string $inviterId, string $inviterName, string $guildName): int
    {
        $payload = $this->invitePayload($event, $message, $inviterName, $guildName);
        $sent = 0;
        foreach ($userIds as $userId) {
            try {
                $this->discord->sendDirectMessage((string) $userId, $payload);
            } catch (Throwable) {
                continue;
            }
            $sent++;
            GuildEventInvite::query()->updateOrCreate(
                ['event_id' => $event->id, 'user_id' => (string) $userId],
                ['status' => GuildEventInvite::PENDING, 'invited_by' => $inviterId],
            );
        }

        return $sent;
    }

    public function invitePayload(GuildEvent $event, ?string $message, string $inviterName, string $guildName): array
    {
        $message = trim((string) $message);

        return [
            'allowed_mentions' => ['parse' => []],
            'embeds' => [[
                'color' => 0x5865F2,
                'title' => $this->text($event->guild, 'invite_title', ['name' => $event->name]),
                'description' => $message !== ''
                    ? $message
                    : $this->text($event->guild, 'invite_text', ['name' => $event->name, 'server' => $guildName]),
                'footer' => ['text' => $this->text($event->guild, 'invite_from', ['user' => $inviterName, 'server' => $guildName])],
            ]],
            'components' => [[
                'type' => 1,
                'components' => [
                    ['type' => 2, 'style' => 3, 'custom_id' => self::JOIN_PREFIX.$event->id, 'label' => $this->text($event->guild, 'join_button')],
                    ['type' => 2, 'style' => 2, 'custom_id' => self::LEAVE_PREFIX.$event->id, 'label' => $this->text($event->guild, 'leave_button')],
                ],
            ]],
        ];
    }

    private function overwrites(string $guildId, string $botId, string $roleId, string $creatorId, ?string $teamRoleId, bool $readOnly = false): array
    {
        $overwrites = [
            ['id' => $guildId, 'type' => self::ROLE, 'allow' => '0', 'deny' => (string) self::VIEW],
            ['id' => $botId, 'type' => self::MEMBER, 'allow' => (string) self::BOT_ACCESS, 'deny' => '0'],
            [
                'id' => $roleId,
                'type' => self::ROLE,
                'allow' => (string) ($readOnly ? self::READ_ACCESS : self::MEMBER_ACCESS),
                'deny' => (string) ($readOnly ? self::SEND | self::THREADS : 0),
            ],
            ['id' => $creatorId, 'type' => self::MEMBER, 'allow' => (string) self::TEAM_ACCESS, 'deny' => '0'],
        ];
        if ($teamRoleId && $teamRoleId !== $roleId) {
            $overwrites[] = ['id' => $teamRoleId, 'type' => self::ROLE, 'allow' => (string) self::TEAM_ACCESS, 'deny' => '0'];
        }

        return $overwrites;
    }

    private function deleteParts(string $guildId, ?string $roleId, array $channels, string $reason): void
    {
        foreach (array_filter($channels) as $channelId) {
            rescue(fn () => $this->discord->deleteChannel($channelId, $reason), report: false);
        }
        if ($roleId) {
            rescue(fn () => $this->discord->deleteRole($guildId, $roleId, $reason), report: false);
        }
    }

    private function text(string $guildId, string $key, array $replace = []): string
    {
        $locale = Guild::forDiscordId($guildId)->messageLocale();

        return __('events.'.$key, $replace, $locale);
    }
}
