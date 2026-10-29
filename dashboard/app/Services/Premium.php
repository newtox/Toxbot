<?php

namespace App\Services;

use App\Models\Entitlement;
use App\Models\PremiumGrant;

class Premium
{
    private array $guildCache = [];

    public static function storeUrl(?string $sku = null): ?string
    {
        $app = (string) config('services.discord.client_id');
        $sku ??= config('toxbot.premium_guild_skus')[0] ?? null;

        return $app !== '' && $sku ? "https://discord.com/application-directory/{$app}/store/{$sku}" : null;
    }

    public function isGuildPremium(string $guildId): bool
    {
        if (in_array($guildId, config('toxbot.premium_guilds'), true)) {
            return true;
        }

        return $this->guildCache[$guildId] ??= PremiumGrant::covers('guild', $guildId)
            || $this->hasGuildSubscription($guildId);
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
}
