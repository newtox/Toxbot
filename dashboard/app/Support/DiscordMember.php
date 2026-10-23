<?php

namespace App\Support;

use Carbon\CarbonImmutable;

final class DiscordMember
{
    public static function avatar(array $user, ?array $member = null, ?string $guildId = null): string
    {
        if ($member && ! empty($member['avatar']) && $guildId) {
            return "https://cdn.discordapp.com/guilds/{$guildId}/users/{$user['id']}/avatars/{$member['avatar']}.png?size=128";
        }

        if (! empty($user['avatar'])) {
            $ext = str_starts_with($user['avatar'], 'a_') ? 'gif' : 'png';

            return "https://cdn.discordapp.com/avatars/{$user['id']}/{$user['avatar']}.{$ext}?size=128";
        }

        $index = intdiv((int) $user['id'], 4194304) % 6;

        return "https://cdn.discordapp.com/embed/avatars/{$index}.png";
    }

    public static function createdAt(string $id): CarbonImmutable
    {
        return CarbonImmutable::createFromTimestampMs(intdiv((int) $id, 4194304) + 1420070400000);
    }

    public static function summary(array $member, array $roles, string $guildId): array
    {
        $user = $member['user'];
        $memberRoles = collect($member['roles'] ?? [])
            ->map(fn ($id) => $roles[$id] ?? null)
            ->filter()
            ->sortByDesc('position')
            ->values();

        $timeout = isset($member['communication_disabled_until'])
            ? CarbonImmutable::parse($member['communication_disabled_until'])
            : null;

        return [
            'id' => $user['id'],
            'name' => $member['nick'] ?? $user['global_name'] ?? $user['username'],
            'username' => $user['username'],
            'avatar' => self::avatar($user, $member, $guildId),
            'bot' => (bool) ($user['bot'] ?? false),
            'joined_at' => isset($member['joined_at']) ? CarbonImmutable::parse($member['joined_at']) : null,
            'roles' => $memberRoles->all(),
            'timeout_until' => $timeout && $timeout->isFuture() ? $timeout : null,
        ];
    }
}
