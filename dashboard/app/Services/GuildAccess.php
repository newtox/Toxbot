<?php

namespace App\Services;

use App\Models\BlacklistEntry;
use App\Models\DashboardUser;
use App\Models\TicketType;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;

class GuildAccess
{
    public const ACTION_PERMISSIONS = [
        'warn' => Discord::PERMISSION_MODERATE_MEMBERS,
        'timeout' => Discord::PERMISSION_MODERATE_MEMBERS,
        'untimeout' => Discord::PERMISSION_MODERATE_MEMBERS,
        'kick' => Discord::PERMISSION_KICK_MEMBERS,
        'ban' => Discord::PERMISSION_BAN_MEMBERS,
        'unban' => Discord::PERMISSION_BAN_MEMBERS,
    ];

    private const MODERATION_PERMISSIONS = Discord::PERMISSION_KICK_MEMBERS
        | Discord::PERMISSION_BAN_MEMBERS
        | Discord::PERMISSION_VIEW_AUDIT_LOG
        | Discord::PERMISSION_MODERATE_MEMBERS;

    public function __construct(
        private readonly Discord $discord,
        private readonly DiscordTokens $tokens,
    ) {}

    public function guilds(DashboardUser $user, bool $fresh = false): array
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
            ->reject(fn (array $g) => isset($blocked[$g['id']]))
            ->map(fn (array $g) => [
                'id' => $g['id'],
                'name' => $g['name'],
                'icon' => $g['icon'] ?? null,
                'icon_url' => isset($g['icon'])
                    ? "https://cdn.discordapp.com/icons/{$g['id']}/{$g['icon']}.png?size=128"
                    : null,
                'owner' => (bool) ($g['owner'] ?? false),
                'manage' => self::canManageByPermissions($g),
                'moderate' => self::canModerateByPermissions($g),
                'appeals' => self::canHandleAppealsByPermissions($g),
                'actions' => self::actionsByPermissions($g),
                'has_bot' => isset($botGuilds[$g['id']]),
            ])
            ->sortBy([['manage', 'desc'], fn ($a, $b) => strcasecmp($a['name'], $b['name'])])
            ->values()
            ->all();
    }

    public function manageableGuilds(DashboardUser $user, bool $fresh = false): array
    {
        return collect($this->guilds($user, $fresh))
            ->where('manage', true)
            ->sortBy([['has_bot', 'desc'], fn ($a, $b) => strcasecmp($a['name'], $b['name'])])
            ->values()
            ->all();
    }

    public function dashboardGuilds(DashboardUser $user, bool $fresh = false): array
    {
        return collect($this->guilds($user, $fresh))->where('has_bot', true)->values()->all();
    }

    public function find(DashboardUser $user, string $guildId): ?array
    {
        $guild = $this->findMember($user, $guildId);

        return $guild !== null && $guild['manage'] ? $guild : null;
    }

    public function findWith(DashboardUser $user, string $guildId, string $ability): ?array
    {
        $guild = $this->findMember($user, $guildId);

        return $guild !== null && $guild[$ability] ? $guild : null;
    }

    public function findMember(DashboardUser $user, string $guildId): ?array
    {
        return collect($this->dashboardGuilds($user))->firstWhere('id', $guildId);
    }

    public function ticketTypeIds(DashboardUser $user, array $guild): ?array
    {
        if ($guild['manage']) {
            return null;
        }

        $types = TicketType::query()->where('guild', $guild['id'])->get(['id', 'roles'])
            ->filter(fn (TicketType $type) => $type->roleIds() !== []);

        if ($types->isEmpty()) {
            return [];
        }

        $roles = $this->memberRoleIds($user, $guild['id']);

        return $types
            ->filter(fn (TicketType $type) => array_intersect($type->roleIds(), $roles) !== [])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function canSeeTickets(DashboardUser $user, array $guild): bool
    {
        $ids = $this->ticketTypeIds($user, $guild);

        return $ids === null || $ids !== [];
    }

    public static function canManageByPermissions(array $guild): bool
    {
        return self::allows($guild, Discord::PERMISSION_MANAGE_GUILD);
    }

    public static function canModerateByPermissions(array $guild): bool
    {
        return self::canManageByPermissions($guild) || self::allows($guild, self::MODERATION_PERMISSIONS, any: true);
    }

    public static function canHandleAppealsByPermissions(array $guild): bool
    {
        return self::canManageByPermissions($guild) || self::allows($guild, Discord::PERMISSION_BAN_MEMBERS);
    }

    public static function actionsByPermissions(array $guild): array
    {
        return array_keys(array_filter(self::ACTION_PERMISSIONS, fn (int $bit) => self::allows($guild, $bit)));
    }

    private static function allows(array $guild, int $bits, bool $any = false): bool
    {
        if ($guild['owner'] ?? false) {
            return true;
        }

        $permissions = (int) ($guild['permissions'] ?? 0);

        if (($permissions & Discord::PERMISSION_ADMINISTRATOR) === Discord::PERMISSION_ADMINISTRATOR) {
            return true;
        }

        return $any ? ($permissions & $bits) !== 0 : ($permissions & $bits) === $bits;
    }

    private function memberRoleIds(DashboardUser $user, string $guildId): array
    {
        return Cache::remember("discord:guild:{$guildId}:member:{$user->id}:roles", 300, function () use ($user, $guildId) {
            try {
                return $this->discord->guildMember($guildId, (string) $user->id)['roles'] ?? [];
            } catch (RequestException) {
                return [];
            }
        });
    }
}
