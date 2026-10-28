<?php

namespace App\Livewire;

use App\Models\BotUser;
use App\Models\ShopItem;
use App\Services\Economy;
use App\Services\Premium;
use App\Support\Level;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use RuntimeException;

class ProfileOverview extends Component
{
    public int $version = 0;

    public array $equipped = [];

    #[Locked]
    public ?string $error = null;

    public function mount(): void
    {
        $this->syncEquipped();
    }

    #[On('profile-changed')]
    public function refreshCard(): void
    {
        $this->syncEquipped();
        $this->version++;
    }

    public function updatedEquipped(mixed $value, string $type): void
    {
        $this->error = null;
        if (! in_array($type, ShopItem::TYPES, true)) {
            $this->syncEquipped();

            return;
        }

        $userId = (string) auth()->id();
        $economy = app(Economy::class);

        try {
            if ($value === '' || $value === null) {
                $economy->unequip($userId, $type);
            } else {
                $item = ShopItem::query()->ofType($type)->find((int) $value);
                if (! $item) {
                    throw new RuntimeException('shop.not_available');
                }
                $economy->equip($userId, $item);
            }
        } catch (RuntimeException $e) {
            $this->error = __($e->getMessage());
        }

        $this->syncEquipped();
        $this->version++;
    }

    private function syncEquipped(): void
    {
        $bot = BotUser::forDiscordId((string) auth()->id());
        $this->equipped = collect(ShopItem::TYPES)
            ->mapWithKeys(fn (string $type) => [$type => (string) ($bot->equippedId($type) ?? '')])
            ->all();
    }

    public function render()
    {
        $userId = (string) auth()->id();
        $bot = BotUser::forDiscordId($userId);
        $premium = app(Premium::class)->isPremium($userId);
        $owned = $bot->exists ? $bot->items()->pluck('backgrounds.id')->all() : [];

        $usable = ShopItem::query()
            ->where(fn ($q) => $q->whereIn('id', $owned)->orWhere(fn ($free) => $free->where('active', true)->where('price', 0)->where('premium_only', false)))
            ->when(! $premium, fn ($q) => $q->where('premium_only', false))
            ->orderBy('sort')->orderBy('id')
            ->get(['id', 'name', 'type'])
            ->groupBy('type');

        return view('livewire.profile-overview', [
            'cardUrl' => route('card', ['user' => $userId, 'v' => $this->version.'-'.time()]),
            'level' => Level::fromXp((int) $bot->xp),
            'credits' => (int) $bot->credits,
            'rank' => $bot->exists ? $bot->globalRank() : null,
            'commands' => (int) $bot->commands_used,
            'premium' => $premium,
            'usable' => $usable,
        ]);
    }
}
