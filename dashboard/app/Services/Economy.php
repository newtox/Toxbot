<?php

namespace App\Services;

use App\Models\BotUser;
use App\Models\CreditTransaction;
use App\Models\ShopItem;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Economy
{
    public function __construct(private readonly Premium $premium) {}

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

    public function buy(string $userId, ShopItem $item): void
    {
        if (! $item->active) {
            throw new RuntimeException('shop.not_available');
        }

        if ($item->premium_only && ! $this->premium->isPremium($userId)) {
            throw new RuntimeException('shop.premium_only');
        }

        DB::transaction(function () use ($userId, $item) {
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
            $user->{$item->column()} = $item->id;
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
        if (! $item->active) {
            throw new RuntimeException('shop.not_available');
        }

        if ($item->premium_only && ! $this->premium->isPremium($userId)) {
            throw new RuntimeException('shop.premium_only');
        }

        $user = BotUser::forDiscordId($userId);
        if (! $user->owns($item)) {
            throw new RuntimeException('shop.not_owned');
        }

        $user->{$item->column()} = $item->id;
        $user->save();
    }

    public function unequip(string $userId, string $type): void
    {
        $user = BotUser::forDiscordId($userId);
        $user->{ShopItem::COLUMNS[$type]} = null;
        $user->save();
    }
}
