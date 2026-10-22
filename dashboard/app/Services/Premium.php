<?php

namespace App\Services;

use App\Models\Entitlement;

class Premium
{
    private array $cache = [];

    private array $guildCache = [];

    public function isGuildPremium(string $guildId): bool
    {
        if (in_array($guildId, config('toxbot.premium_guilds'), true)) {
            return true;
        }

        $skus = config('toxbot.premium_guild_skus');

        if ($skus === []) {
            return false;
        }

        return $this->guildCache[$guildId] ??= Entitlement::query()
            ->active()
            ->where('guild_id', $guildId)
            ->whereIn('sku_id', $skus)
            ->exists();
    }

    public function isPremium(string $userId): bool
    {
        if (in_array($userId, config('toxbot.premium_users'), true)) {
            return true;
        }

        $skus = config('toxbot.premium_user_skus');

        if ($skus === []) {
            return false;
        }

        return $this->cache[$userId] ??= Entitlement::query()
            ->active()
            ->where('user_id', $userId)
            ->whereIn('sku_id', $skus)
            ->exists();
    }
}
