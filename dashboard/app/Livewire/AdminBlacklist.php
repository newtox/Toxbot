<?php

namespace App\Livewire;

use App\Http\Middleware\EnsureBotOwner;
use App\Models\BlacklistEntry;
use App\Services\Discord;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AdminBlacklist extends Component
{
    public string $type = 'user';

    public string $target = '';

    public string $reason = '';

    public ?string $notice = null;

    public function mount(): void
    {
        $this->authorizeOwner();
    }

    public function add(Discord $discord): void
    {
        $this->authorizeOwner();

        $this->validate([
            'type' => ['required', Rule::in(['user', 'guild'])],
            'target' => ['required', 'regex:/^\d{15,22}$/'],
            'reason' => ['nullable', 'string', 'max:200'],
        ], [
            'target.*' => __('admin.blacklist_invalid_id'),
        ]);

        if ($this->type === 'user' && EnsureBotOwner::check($this->target)) {
            $this->addError('target', __('admin.blacklist_not_yourself'));

            return;
        }

        $exists = BlacklistEntry::query()->where($this->type, $this->target)->exists();
        if (! $exists) {
            BlacklistEntry::create([
                $this->type => $this->target,
                'reason' => trim($this->reason) ?: null,
                'created_at' => now(),
            ]);
        }

        $this->notice = __('admin.blacklist_added');
        if ($this->type === 'guild') {
            try {
                $discord->leaveGuild($this->target);
                $this->notice = __('admin.blacklist_added_left');
            } catch (RequestException $e) {
                Log::warning('Leaving a blacklisted server failed', ['guild' => $this->target, 'status' => $e->response->status()]);
            }
        }

        $this->reset('target', 'reason');
    }

    public function remove(int $id): void
    {
        $this->authorizeOwner();

        BlacklistEntry::query()->whereKey($id)->delete();
        $this->notice = null;
    }

    public function render()
    {
        return view('livewire.admin-blacklist', [
            'entries' => BlacklistEntry::query()->orderByDesc('id')->get(),
        ]);
    }

    private function authorizeOwner(): void
    {
        abort_unless(EnsureBotOwner::check(auth()->id()), 403);
    }
}
