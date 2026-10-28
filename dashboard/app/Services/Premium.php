<?php

namespace App\Services;

use App\Models\Entitlement;
use App\Models\PremiumGrant;
use App\Models\PremiumServer;

class Premium
{
    private array $cache = [];

    private array $guildCache = [];

    public static function storeUrl(): ?string
    {
        $app = (string) config('services.discord.client_id');
        $sku = config('toxbot.premium_user_skus')[0] ?? null;

        return $app !== '' && $sku ? "https://discord.com/application-directory/{$app}/store/{$sku}" : null;
    }

    public function isGuildPremium(string $guildId): bool
    {
        if (in_array($guildId, config('toxbot.premium_guilds'), true)) {
            return true;
        }

        return $this->guildCache[$guildId] ??= PremiumGrant::covers('guild', $guildId)
            || $this->hasGuildSubscription($guildId)
            || $this->hasPremiumMember($guildId);
    }

    private function hasGuildSubscription(string $guildId): bool
    {
        $skus = config('toxbot.premium_guild_skus');

        return $skus !== [] && Entitlement::query()
            ->active()
            ->where('guild_id', $guildId)
            ->whereIn('sku_id', $skus)
            ->exists();
    }

    private function hasPremiumMember(string $guildId): bool
    {
        foreach (PremiumServer::query()->where('guild_id', $guildId)->pluck('user_id') as $userId) {
            if ($this->isPremium((string) $userId)) {
                return true;
            }
        }

        return false;
    }

    public function isPremium(string $userId): bool
    {
        if (in_array($userId, config('toxbot.premium_users'), true)) {
            return true;
        }

        return $this->cache[$userId] ??= PremiumGrant::covers('user', $userId) || $this->hasUserSubscription($userId);
    }

    private function hasUserSubscription(string $userId): bool
    {
        $skus = config('toxbot.premium_user_skus');

        return $skus !== [] && Entitlement::query()
            ->active()
            ->where('user_id', $userId)
            ->whereIn('sku_id', $skus)
            ->exists();
    }
}
