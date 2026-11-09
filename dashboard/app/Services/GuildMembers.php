<?php

namespace App\Services;

use Illuminate\Support\Str;

class GuildMembers
{
    public function __construct(private readonly Discord $discord) {}

    public function search(string $guildId, string $query, array $exclude = [], int $limit = 8): array
    {
        $needle = self::normalize($query);
        if (mb_strlen(str_replace(' ', '', $needle)) < 2) {
            return [];
        }

        return collect($this->discord->guildMembers($guildId))
            ->reject(fn ($member) => ($member['user']['bot'] ?? false)
                || in_array((string) $member['user']['id'], $exclude, true))
            ->map(function ($member) use ($needle) {
                $names = array_filter([$member['nick'] ?? null, $member['user']['global_name'] ?? null, $member['user']['username']]);
                $scores = array_filter(array_map(fn ($name) => self::matchScore(self::normalize($name), $needle), $names), fn ($score) => $score !== null);

                return ['member' => $member, 'rank' => $scores === [] ? null : min($scores)];
            })
            ->filter(fn ($entry) => $entry['rank'] !== null)
            ->sortBy([['rank', 'asc'], [fn ($a, $b) => strnatcasecmp(self::displayName($a['member']), self::displayName($b['member']))]])
            ->take($limit)
            ->map(fn ($entry) => [...self::card($guildId, $entry['member']), 'roles' => $entry['member']['roles'] ?? []])
            ->values()
            ->all();
    }

    public function member(string $guildId, string $userId): ?array
    {
        $member = collect($this->discord->guildMembers($guildId))
            ->first(fn ($member) => (string) $member['user']['id'] === $userId && ! ($member['user']['bot'] ?? false));

        return $member ? [...self::card($guildId, $member), 'roles' => $member['roles'] ?? []] : null;
    }

    public static function card(?string $guildId, array $member): array
    {
        $user = $member['user'];

        return [
            'id' => (string) $user['id'],
            'name' => self::displayName($member),
            'username' => $user['username'],
            'avatar' => match (true) {
                $guildId !== null && isset($member['avatar']) => "https://cdn.discordapp.com/guilds/{$guildId}/users/{$user['id']}/avatars/{$member['avatar']}.png?size=64",
                isset($user['avatar']) => "https://cdn.discordapp.com/avatars/{$user['id']}/{$user['avatar']}.png?size=64",
                default => 'https://cdn.discordapp.com/embed/avatars/'.(((int) $user['id'] >> 22) % 6).'.png',
            },
        ];
    }

    public static function userCard(array $user): array
    {
        return self::card(null, ['user' => $user]);
    }

    private static function displayName(array $member): string
    {
        return $member['nick'] ?? $member['user']['global_name'] ?? $member['user']['username'];
    }

    private static function normalize(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower(Str::ascii($text))));
    }

    private static function matchScore(string $name, string $query): ?int
    {
        if ($name === '') {
            return null;
        }
        if (str_starts_with($name, $query)) {
            return 0;
        }
        if (str_contains($name, $query)) {
            return 1;
        }

        $words = explode(' ', $name);
        $words[] = str_replace(' ', '', $name);
        $total = 10;
        foreach (explode(' ', $query) as $token) {
            $best = null;
            foreach ($words as $word) {
                $score = self::tokenScore($word, $token);
                if ($score !== null && ($best === null || $score < $best)) {
                    $best = $score;
                }
            }
            if ($best === null) {
                return null;
            }
            $total += $best;
        }

        return $total;
    }

    private static function tokenScore(string $word, string $token): ?int
    {
        $length = strlen($token);
        if (str_starts_with($word, $token)) {
            return 0;
        }
        if (str_contains($word, $token)) {
            return 1;
        }
        if ($length >= 2 && $word[0] === $token[0] && $length >= strlen($word) * 0.6 && self::isSubsequence($token, $word)) {
            return 2;
        }

        $allowed = $length >= 6 ? 2 : ($length >= 4 ? 1 : 0);
        if ($allowed > 0 && min(levenshtein($token, $word), levenshtein($token, substr($word, 0, $length))) <= $allowed) {
            return 3;
        }

        return null;
    }

    private static function isSubsequence(string $needle, string $haystack): bool
    {
        $position = 0;
        foreach (str_split($needle) as $char) {
            $position = strpos($haystack, $char, $position);
            if ($position === false) {
                return false;
            }
            $position++;
        }

        return true;
    }
}
