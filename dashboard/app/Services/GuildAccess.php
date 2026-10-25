<?php

namespace App\Services;

use App\Models\BlacklistEntry;
use App\Models\DashboardUser;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;

class GuildAccess
{
    public function __construct(
        private readonly Discord $discord,
        private readonly DiscordTokens $tokens,
    ) {}

    public function manageableGuilds(DashboardUser $user, bool $fresh = false): array
    {
        $cacheKey = "discord:user:{$user->id}:guilds";

        if ($fresh) {
            Cache::forget($cacheKey);
        }

        $guilds = Cache::remember($cacheKey, 300, function () use ($user) {
            $token = $this->tokens->accessToken($user);

            try {
                return $this->discord->currentUserGuilds($token);
            } catch (RequestException $e) {
                if ($e->response->status() === 401) {
                    $this->tokens->forget($user);
                    throw new MissingDiscordToken;
                }
                throw $e;
            }
        });

        $botGuilds = $this->discord->botGuildIds();
        $blocked = array_flip(BlacklistEntry::blockedGuildIds());

        return collect($guilds)
            ->filter(fn (array $g) => self::canManageByPermissions($g) && ! isset($blocked[$g['id']]))
            ->map(fn (array $g) => [
                'id' => $g['id'],
                'name' => $g['name'],
                'icon' => $g['icon'] ?? null,
                'icon_url' => isset($g['icon'])
                    ? "https://cdn.discordapp.com/icons/{$g['id']}/{$g['icon']}.png?size=128"
                    : null,
                'owner' => (bool) ($g['owner'] ?? false),
                'has_bot' => isset($botGuilds[$g['id']]),
            ])
            ->sortBy([['has_bot', 'desc'], fn ($a, $b) => strcasecmp($a['name'], $b['name'])])
            ->values()
            ->all();
    }

    public function find(DashboardUser $user, string $guildId): ?array
    {
        foreach ($this->manageableGuilds($user) as $guild) {
            if ($guild['id'] === $guildId && $guild['has_bot']) {
                return $guild;
            }
        }

        return null;
    }

    public static function canManageByPermissions(array $guild): bool
    {
        if ($guild['owner'] ?? false) {
            return true;
        }

        $permissions = (int) ($guild['permissions'] ?? 0);

        return ($permissions & Discord::PERMISSION_ADMINISTRATOR) === Discord::PERMISSION_ADMINISTRATOR
            || ($permissions & Discord::PERMISSION_MANAGE_GUILD) === Discord::PERMISSION_MANAGE_GUILD;
    }
}
