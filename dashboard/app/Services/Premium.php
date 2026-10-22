<?php

namespace App\Services;

use App\Models\Entitlement;

class Premium
{
    private array $cache = [];

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
