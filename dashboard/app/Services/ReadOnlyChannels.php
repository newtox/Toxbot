<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;

class ReadOnlyChannels
{
    public const THREADS = (1 << 35) | (1 << 36) | (1 << 38);

    private const SEND = 0x800;

    private const ROLE = 0;

    private const TYPES = [0, 4, 5];

    public function __construct(private Discord $discord) {}

    public function list(string $guildId, ?array $channels = null): array
    {
        $channels ??= $this->discord->guildChannels($guildId);

        return collect($channels)
            ->filter(fn (array $channel) => in_array($channel['type'], self::TYPES, true))
            ->map(fn (array $channel) => [$channel, $this->everyone($channel, $guildId)])
            ->filter(fn (array $pair) => $pair[1] !== null && ((int) $pair[1]['deny'] & self::SEND))
            ->sortBy(fn (array $pair) => [$pair[0]['type'] === 4 ? 0 : 1, $pair[0]['position'] ?? 0])
            ->map(fn (array $pair) => [
                'id' => $pair[0]['id'],
                'name' => $pair[0]['name'],
                'category' => $pair[0]['type'] === 4,
                'state' => match (true) {
                    ((int) $pair[1]['allow'] & self::THREADS) !== 0 => 'allowed',
                    $this->missing($pair[1]) !== 0 => 'open',
                    default => 'locked',
                },
            ])
            ->values()
            ->all();
    }

    public function lock(string $guildId, string $reason, ?array $channels = null): array
    {
        $locked = 0;
        $failed = 0;
        foreach ($channels ?? $this->discord->guildChannels($guildId) as $channel) {
            if (! in_array($channel['type'], self::TYPES, true)) {
                continue;
            }
            $everyone = $this->everyone($channel, $guildId);
            if ($everyone === null || ! ((int) $everyone['deny'] & self::SEND) || ((int) $everyone['allow'] & self::THREADS) || $this->missing($everyone) === 0) {
                continue;
            }
            try {
                $this->discord->setChannelPermission($channel['id'], $guildId, self::ROLE, (int) $everyone['allow'], (int) $everyone['deny'] | $this->missing($everyone), $reason);
                $locked++;
            } catch (RequestException) {
                $failed++;
            }
        }
        if ($locked > 0) {
            $this->discord->forgetGuildCache($guildId);
        }

        return ['locked' => $locked, 'failed' => $failed];
    }

    private function everyone(array $channel, string $guildId): ?array
    {
        foreach ($channel['permission_overwrites'] ?? [] as $overwrite) {
            if ($overwrite['id'] === $guildId) {
                return $overwrite;
            }
        }

        return null;
    }

    private function missing(array $overwrite): int
    {
        return self::THREADS & ~(int) $overwrite['deny'] & ~(int) $overwrite['allow'];
    }
}
