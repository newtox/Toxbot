<?php

namespace App\Livewire;

use App\Http\Middleware\EnsureBotOwner;
use App\Models\BotUser;
use App\Models\CreditTransaction;
use App\Models\DashboardUser;
use App\Models\ShopItem;
use App\Services\Discord;
use App\Services\Economy;
use App\Services\GuildMembers;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AdminEconomy extends Component
{
    #[Locked]
    public string $userId;

    public string $amount = '';

    public string $giveId = '';

    public ?string $notice = null;

    public function mount(string $userId): void
    {
        $this->authorizeOwner();
        abort_unless(preg_match('/^\d{15,22}$/', $userId) === 1, 404);
        $this->userId = $userId;
    }

    public function adjust(Economy $economy): void
    {
        $this->authorizeOwner();
        $this->validate(['amount' => ['required', 'integer', 'not_in:0', 'min:-1000000000', 'max:1000000000']], ['amount.*' => __('admin.users_amount_invalid')]);

        $balance = $economy->adjustCredits($this->userId, (int) $this->amount);
        $this->notice = __('admin.users_adjusted', ['amount' => number_format($balance, 0, '', ' ').' '.config('toxbot.currency')]);
        $this->reset('amount');
    }

    public function give(Economy $economy): void
    {
        $this->authorizeOwner();
        $this->validate(['giveId' => ['required', 'integer', 'exists:backgrounds,id']], ['giveId.*' => __('admin.users_pick_item')]);

        $item = ShopItem::findOrFail((int) $this->giveId);
        $economy->giveItem($this->userId, $item);
        $this->notice = __('admin.users_given', ['name' => $item->name]);
        $this->reset('giveId');
    }

    public function take(int $id, Economy $economy): void
    {
        $this->authorizeOwner();

        $item = ShopItem::findOrFail($id);
        $economy->takeItem($this->userId, $item);
        $this->notice = __('admin.users_taken', ['name' => $item->name]);
    }

    private function authorizeOwner(): void
    {
        abort_unless(EnsureBotOwner::check(Auth::id()), 403);
    }

    public function render(Discord $discord)
    {
        $user = BotUser::query()->find($this->userId);
        $owned = $user ? $user->items()->orderBy('type')->orderBy('sort')->get() : collect();
        $dashboard = DashboardUser::query()->find($this->userId);
        $profile = $dashboard ? null : rescue(fn () => $discord->user($this->userId), null, report: false);

        $card = GuildMembers::userCard($dashboard
            ? ['id' => $this->userId, 'username' => $dashboard->username, 'global_name' => $dashboard->global_name, 'avatar' => $dashboard->avatar]
            : ['id' => $this->userId, 'username' => $profile['username'] ?? '', 'global_name' => $profile['global_name'] ?? null, 'avatar' => $profile['avatar'] ?? null]);

        $data = [
            'avatar' => $card['avatar'],
            'name' => $dashboard?->global_name ?: $dashboard?->username ?: ($profile['global_name'] ?? $profile['username'] ?? null),
            'credits' => (int) $user?->credits,
            'owned' => $owned,
            'equipped' => collect(ShopItem::TYPES)->map(fn (string $type) => $user?->equippedId($type))->filter()->all(),
            'available' => ShopItem::query()->whereNotIn('id', $owned->pluck('id'))->orderBy('type')->orderBy('sort')->get()->groupBy('type'),
            'history' => CreditTransaction::query()->where('user', $this->userId)->latest('id')->limit(10)->get(),
        ];

        return view('livewire.admin-economy', ['data' => $data]);
    }
}
