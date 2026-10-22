<?php

namespace App\Livewire;

use App\Models\Background;
use App\Models\BotUser;
use App\Services\Economy;
use App\Services\Premium;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

class Shop extends Component
{
    public ?int $previewId = null;

    #[Locked]
    public ?string $message = null;

    #[Locked]
    public bool $success = false;

    public function preview(int $id): void
    {
        $this->previewId = Background::query()->where('active', true)->whereKey($id)->exists() ? $id : null;
    }

    public function buy(int $id, Economy $economy): void
    {
        $this->run(fn () => $economy->buy($this->userId(), Background::findOrFail($id)), 'shop.bought');
    }

    public function equip(int $id, Economy $economy): void
    {
        $this->run(fn () => $economy->equip($this->userId(), Background::findOrFail($id)), 'shop.equipped');
    }

    public function useDefault(Economy $economy): void
    {
        $this->run(fn () => $economy->equip($this->userId(), null), 'shop.equipped_default');
        $this->previewId = null;
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
        $user = BotUser::forDiscordId($this->userId());
        $owned = $user->exists ? $user->backgrounds()->pluck('backgrounds.id')->all() : [];
        $premium = app(Premium::class)->isPremium($this->userId());

        $backgrounds = Background::query()->inShop()->get()->map(fn (Background $bg) => [
            'id' => $bg->id,
            'name' => $bg->name,
            'price' => $bg->price,
            'premium_only' => $bg->premium_only,
            'url' => $bg->url(),
            'owned' => in_array($bg->id, $owned, true) || ($bg->price === 0 && ! $bg->premium_only),
            'equipped' => $user->background_id === $bg->id,
            'locked' => $bg->premium_only && ! $premium,
        ]);

        $previewId = $this->previewId ?? $backgrounds->firstWhere('equipped', true)['id'] ?? null;

        return view('livewire.shop', [
            'backgrounds' => $backgrounds,
            'credits' => (int) $user->credits,
            'premium' => $premium,
            'previewId' => $previewId,
            'previewUrl' => route('card', array_filter(['user' => $this->userId(), 'bg' => $previewId, 'v' => $user->background_id ?? 0])),
            'hasBackground' => $user->background_id !== null,
        ]);
    }
}
