<?php

namespace App\Services;

use App\Models\BotUser;
use App\Models\CreditTransaction;
use App\Models\Entitlement;
use App\Models\ShopItem;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Economy
{
    private const DAY = 86400;

    public function addCredits(string $userId, int $amount, string $reason, ?string $reference = null): bool
    {
        try {
            DB::transaction(function () use ($userId, $amount, $reason, $reference) {
                CreditTransaction::create([
                    'user' => $userId,
                    'amount' => $amount,
                    'reason' => $reason,
                    'reference' => $reference,
                ]);

                $user = BotUser::forDiscordId($userId);
                $user->credits = (int) $user->credits + $amount;
                $user->save();
            });
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }

    public function buy(string $userId, ShopItem $item, bool $equip = true): void
    {
        if (! $item->active) {
            throw new RuntimeException('shop.not_available');
        }

        if ($item->forMoney()) {
            throw new RuntimeException('shop.for_money');
        }

        DB::transaction(function () use ($userId, $item, $equip) {
            $user = BotUser::forDiscordId($userId);
            if (! $user->exists) {
                $user->save();
            }
            $user = BotUser::query()->whereKey($userId)->lockForUpdate()->first();

            if ($user->owns($item)) {
                throw new RuntimeException('shop.already_owned');
            }

            if ((int) $user->credits < $item->price) {
                throw new RuntimeException('shop.not_enough');
            }

            $user->credits = (int) $user->credits - $item->price;
            if ($equip) {
                $user->{$item->column()} = $item->id;
            }
            $user->save();

            $user->items()->attach($item->id, ['created_at' => now()]);

            CreditTransaction::create([
                'user' => $userId,
                'amount' => -$item->price,
                'reason' => 'purchase',
            ]);
        });
    }

    public function equip(string $userId, ShopItem $item): void
    {
        $this->syncPurchases($userId);

        $user = BotUser::forDiscordId($userId);
        if (! $user->owns($item)) {
            throw new RuntimeException('shop.not_owned');
        }

        $user->{$item->column()} = $item->id;
        $user->save();

        if ($item->isFree()) {
            $user->items()->syncWithoutDetaching([$item->id => ['created_at' => now()]]);
        }
    }

    public function unequip(string $userId, string $type): void
    {
        $user = BotUser::forDiscordId($userId);
        $user->{ShopItem::COLUMNS[$type]} = null;
        $user->save();
    }

    public function adjustCredits(string $userId, int $amount): int
    {
        return DB::transaction(function () use ($userId, $amount) {
            $user = BotUser::forDiscordId($userId);
            if (! $user->exists) {
                $user->save();
            }
            $user = BotUser::query()->whereKey($userId)->lockForUpdate()->first();
            $balance = (int) $user->credits;
            $change = $amount < 0 ? -min($balance, -$amount) : $amount;

            if ($change !== 0) {
                $user->credits = $balance + $change;
                $user->save();
                CreditTransaction::create(['user' => $userId, 'amount' => $change, 'reason' => 'admin']);
            }

            return $balance + $change;
        });
    }

    public function claimDaily(string $userId): array
    {
        return DB::transaction(function () use ($userId) {
            $user = $this->lockedUser($userId);
            $since = $user->daily_at ? (int) $user->daily_at->diffInSeconds(now(), true) : null;

            if ($since !== null && $since < self::DAY) {
                return ['claimed' => false, 'wait' => self::DAY - $since];
            }

            $streak = $since !== null && $since < 2 * self::DAY ? (int) $user->daily_streak + 1 : 1;
            $amount = (int) config('toxbot.daily.reward') + min(max($streak - 1, 0), 10) * (int) config('toxbot.daily.streak_bonus');

            $user->daily_at = now();
            $user->daily_streak = $streak;
            $user->credits = (int) $user->credits + $amount;
            $user->save();
            CreditTransaction::create(['user' => $userId, 'amount' => $amount, 'reason' => 'daily']);

            return ['claimed' => true, 'amount' => $amount, 'streak' => $streak];
        });
    }

    public function creditsOverview(string $userId, int $history = 15): array
    {
        $user = BotUser::query()->find($userId);
        $since = $user?->daily_at ? (int) $user->daily_at->diffInSeconds(now(), true) : null;

        return [
            'credits' => (int) $user?->credits,
            'streak' => $since !== null && $since < 2 * self::DAY ? (int) $user->daily_streak : 0,
            'next_daily' => $since !== null && $since < self::DAY ? $user->daily_at->copy()->addSeconds(self::DAY) : null,
            'history' => CreditTransaction::query()->where('user', $userId)->orderByDesc('id')->limit($history)->get(),
        ];
    }

    public function transferCredits(string $fromId, string $toId, int $amount): int
    {
        if ($amount < 1) {
            throw new RuntimeException('credits.amount_invalid');
        }
        if ($fromId === $toId) {
            throw new RuntimeException('credits.pay_self');
        }

        return DB::transaction(function () use ($fromId, $toId, $amount) {
            $locked = collect([$fromId, $toId])->sort(SORT_STRING)->mapWithKeys(fn ($id) => [$id => $this->lockedUser($id)]);
            $sender = $locked[$fromId];
            $receiver = $locked[$toId];
            if ((int) $sender->credits < $amount) {
                throw new RuntimeException('shop.not_enough');
            }

            $sender->credits = (int) $sender->credits - $amount;
            $sender->save();
            $receiver->credits = (int) $receiver->credits + $amount;
            $receiver->save();
            CreditTransaction::create(['user' => $fromId, 'amount' => -$amount, 'reason' => 'transfer_out', 'reference' => null]);
            CreditTransaction::create(['user' => $toId, 'amount' => $amount, 'reason' => 'transfer_in', 'reference' => null]);

            return (int) $sender->credits;
        });
    }

    private function lockedUser(string $userId): BotUser
    {
        $user = BotUser::forDiscordId($userId);
        if (! $user->exists) {
            $user->save();
        }

        return BotUser::query()->whereKey($userId)->lockForUpdate()->first();
    }

    public function giveItem(string $userId, ShopItem $item): bool
    {
        $user = BotUser::forDiscordId($userId);
        if (! $user->exists) {
            $user->save();
        }
        if ($user->items()->whereKey($item->id)->exists()) {
            return false;
        }
        $user->items()->attach($item->id, ['created_at' => now()]);

        return true;
    }

    public function takeItem(string $userId, ShopItem $item): bool
    {
        $user = BotUser::query()->find($userId);
        if (! $user) {
            return false;
        }
        $removed = $user->items()->detach($item->id) > 0;
        if ($user->{$item->column()} === $item->id) {
            $user->{$item->column()} = null;
            $user->save();
        }

        return $removed;
    }

    public function syncPurchases(string $userId): void
    {
        $skus = Entitlement::query()->active()->where('user_id', $userId)->pluck('sku_id')->map(fn ($sku) => (string) $sku)->all();
        if ($skus === []) {
            return;
        }

        $items = ShopItem::query()->whereIn('sku_id', $skus)->pluck('id');
        if ($items->isEmpty()) {
            return;
        }

        $user = BotUser::forDiscordId($userId);
        if (! $user->exists) {
            $user->save();
        }
        $user->items()->syncWithoutDetaching($items->mapWithKeys(fn ($id) => [$id => ['created_at' => now()]])->all());
    }

    public function tagLimit(string $userId): int
    {
        $user = BotUser::query()->find($userId);

        return min(
            (int) config('toxbot.tags.max_limit'),
            (int) config('toxbot.tags.free_limit') + (int) $user?->tag_slots * (int) config('toxbot.tags.slot_size'),
        );
    }

    public function buyTagSlots(string $userId): void
    {
        $price = (int) config('toxbot.tags.slot_price');

        DB::transaction(function () use ($userId, $price) {
            $user = BotUser::forDiscordId($userId);
            if (! $user->exists) {
                $user->save();
            }
            $user = BotUser::query()->whereKey($userId)->lockForUpdate()->first();

            if ($this->tagLimit($userId) >= (int) config('toxbot.tags.max_limit')) {
                throw new RuntimeException('tags.slots_max');
            }

            if ((int) $user->credits < $price) {
                throw new RuntimeException('shop.not_enough');
            }

            $user->credits = (int) $user->credits - $price;
            $user->tag_slots = (int) $user->tag_slots + 1;
            $user->save();

            CreditTransaction::create([
                'user' => $userId,
                'amount' => -$price,
                'reason' => 'tag_slots',
            ]);
        });
    }
}
