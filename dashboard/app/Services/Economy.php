<?php

namespace App\Services;

use App\Models\Background;
use App\Models\BotUser;
use App\Models\CreditTransaction;
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

    public function buy(string $userId, Background $background): void
    {
        if (! $background->active) {
            throw new RuntimeException('shop.not_available');
        }

        if ($background->premium_only && ! $this->premium->isPremium($userId)) {
            throw new RuntimeException('shop.premium_only');
        }

        DB::transaction(function () use ($userId, $background) {
            $user = BotUser::forDiscordId($userId);
            if (! $user->exists) {
                $user->save();
            }
            $user = BotUser::query()->whereKey($userId)->lockForUpdate()->first();

            if ($user->owns($background)) {
                throw new RuntimeException('shop.already_owned');
            }

            if ((int) $user->credits < $background->price) {
                throw new RuntimeException('shop.not_enough');
            }

            $user->credits = (int) $user->credits - $background->price;
            $user->background_id = $background->id;
            $user->save();

            $user->backgrounds()->attach($background->id, ['created_at' => now()]);

            CreditTransaction::create([
                'user' => $userId,
                'amount' => -$background->price,
                'reason' => 'purchase',
            ]);
        });
    }

    public function equip(string $userId, ?Background $background): void
    {
        $user = BotUser::forDiscordId($userId);

        if ($background !== null) {
            if ($background->premium_only && ! $this->premium->isPremium($userId)) {
                throw new RuntimeException('shop.premium_only');
            }
            if (! $user->owns($background)) {
                throw new RuntimeException('shop.not_owned');
            }
        }

        $user->background_id = $background?->id;
        $user->save();
    }
}
