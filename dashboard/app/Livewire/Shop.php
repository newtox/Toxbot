<?php

namespace App\Livewire;

use App\Models\BotUser;
use App\Models\ShopItem;
use App\Services\Economy;
use App\Services\Premium;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use RuntimeException;

class Shop extends Component
{
    #[Url(except: ShopItem::BACKGROUND)]
    public string $tab = ShopItem::BACKGROUND;

    public array $previews = [];

    #[Locked]
    public ?string $message = null;

    #[Locked]
    public bool $success = false;

    public function selectTab(string $tab): void
    {
        $this->tab = in_array($tab, ShopItem::TYPES, true) ? $tab : ShopItem::BACKGROUND;
        $this->message = null;
    }

    public function preview(int $id): void
    {
        $item = ShopItem::query()->find($id);
        if ($item && ($item->active || BotUser::forDiscordId($this->userId())->items()->whereKey($id)->exists())) {
            $this->previews[$item->type] = $item->id;
        }
    }

    public function buy(int $id, Economy $economy): void
    {
        $this->run(fn () => $economy->buy($this->userId(), ShopItem::findOrFail($id)), 'shop.bought');
    }

    public function equip(int $id, Economy $economy): void
    {
        $this->run(fn () => $economy->equip($this->userId(), ShopItem::findOrFail($id)), 'shop.equipped');
    }

    public function useDefault(Economy $economy): void
    {
        $this->run(fn () => $economy->unequip($this->userId(), $this->tab), 'shop.equipped_default');
        unset($this->previews[$this->tab]);
    }

    private function run(callable $action, string $successKey): void
    {
        try {
            $action();
            $this->message = __($successKey);
            $this->success = true;
            $this->dispatch('profile-changed');
        } catch (RuntimeException $e) {
            $this->message = __($e->getMessage());
            $this->success = false;
        }
    }

    private function userId(): string
    {
        return (string) auth()->id();
    }

    public function render()
    {
        if (! in_array($this->tab, ShopItem::TYPES, true)) {
            $this->tab = ShopItem::BACKGROUND;
        }

        app(Economy::class)->syncPurchases($this->userId());
        $user = BotUser::forDiscordId($this->userId());
        $owned = $user->exists ? $user->items()->pluck('backgrounds.id')->all() : [];
        $equipped = collect(ShopItem::TYPES)->mapWithKeys(fn (string $type) => [$type => $user->equippedId($type)])->all();

        $visible = fn ($query) => $query->where(fn ($q) => $q->where('active', true)->orWhereIn('id', $owned));

        $items = ShopItem::query()->tap($visible)->ofType($this->tab)->orderBy('sort')->orderBy('price')->orderBy('id')->get()->map(fn (ShopItem $item) => [
            'id' => $item->id,
            'name' => $item->name,
            'price' => $item->price,
            'for_money' => $item->forMoney(),
            'store_url' => $item->forMoney() ? Premium::storeUrl($item->sku_id) : null,
            'animated' => $item->animated,
            'url' => $item->url(),
            'owned' => in_array($item->id, $owned, true) || ($item->isFree() && $item->active),
            'retired' => ! $item->active,
            'equipped' => $equipped[$this->tab] === $item->id,
        ]);

        $selected = $this->previews[$this->tab] ?? $equipped[$this->tab];

        return view('livewire.shop', [
            'items' => $items,
            'counts' => ShopItem::query()->tap($visible)->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type'),
            'credits' => (int) $user->credits,
            'hasExclusive' => $items->contains('for_money', true),
            'selected' => $selected,
            'previewUrl' => route('card', array_filter([
                'user' => $this->userId(),
                'bg' => $this->previews[ShopItem::BACKGROUND] ?? null,
                'frame' => $this->previews[ShopItem::FRAME] ?? null,
                'bar' => $this->previews[ShopItem::BAR] ?? null,
                'v' => implode('-', array_map('intval', $equipped)),
            ])),
            'hasEquipped' => $equipped[$this->tab] !== null,
        ]);
    }
}
