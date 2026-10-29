<?php

namespace App\Livewire;

use App\Http\Middleware\EnsureBotOwner;
use App\Models\PremiumGrant;
use App\Support\Timezone;
use Illuminate\Support\Carbon;
use Livewire\Component;

class AdminPremium extends Component
{
    public string $target = '';

    public string $until = '';

    public string $note = '';

    public ?string $notice = null;

    public function mount(): void
    {
        $this->authorizeOwner();
    }

    public function grant(): void
    {
        $this->authorizeOwner();

        $this->validate([
            'target' => ['required', 'regex:/^\d{15,22}$/'],
            'until' => ['nullable', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:200'],
        ], [
            'target.*' => __('admin.blacklist_invalid_id'),
            'until.*' => __('admin.premium_until_invalid'),
        ]);

        $endsAt = null;
        if ($this->until !== '') {
            $endsAt = Carbon::createFromFormat('Y-m-d', $this->until, Timezone::current())->endOfDay()->utc();
            if ($endsAt->isPast()) {
                $this->addError('until', __('admin.premium_until_invalid'));

                return;
            }
        }

        PremiumGrant::updateOrCreate(
            ['type' => 'guild', 'target_id' => $this->target],
            ['ends_at' => $endsAt, 'note' => trim($this->note) ?: null, 'granted_by' => (string) auth()->id()],
        );

        $this->notice = __('admin.premium_granted');
        $this->reset('target', 'until', 'note');
    }

    public function revoke(int $id): void
    {
        $this->authorizeOwner();

        PremiumGrant::query()->whereKey($id)->delete();
        $this->notice = null;
    }

    public function render()
    {
        $grants = PremiumGrant::query()->where('type', 'guild')->orderByRaw('ends_at IS NOT NULL')->orderByDesc('ends_at')->orderByDesc('id')->get();

        return view('livewire.admin-premium', [
            'grants' => $grants,
            'configGuilds' => config('toxbot.premium_guilds'),
        ]);
    }

    private function authorizeOwner(): void
    {
        abort_unless(EnsureBotOwner::check(auth()->id()), 403);
    }
}
