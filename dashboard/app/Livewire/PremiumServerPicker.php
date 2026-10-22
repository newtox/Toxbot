<?php

namespace App\Livewire;

use App\Models\PremiumServer;
use App\Services\GuildAccess;
use App\Services\Premium;
use Illuminate\Validation\Rule;
use Livewire\Component;
use RuntimeException;

class PremiumServerPicker extends Component
{
    public string $guildId = '';

    public function mount(): void
    {
        $this->guildId = (string) PremiumServer::find((string) auth()->id())?->guild_id;
    }

    public function save(Premium $premium): void
    {
        $userId = (string) auth()->id();
        abort_unless($premium->isPremium($userId), 403);

        $this->validate([
            'guildId' => ['nullable', Rule::in(array_column($this->guilds(), 'id'))],
        ], ['guildId.in' => __('profile.premium_server_invalid')]);

        if ($this->guildId === '') {
            PremiumServer::query()->whereKey($userId)->delete();
        } else {
            PremiumServer::query()->updateOrCreate(['user_id' => $userId], ['guild_id' => $this->guildId]);
        }

        $this->dispatch('saved');
    }

    private function guilds(): array
    {
        try {
            $guilds = app(GuildAccess::class)->manageableGuilds(auth()->user());
        } catch (RuntimeException) {
            return [];
        }

        return array_values(array_filter($guilds, fn ($g) => $g['has_bot']));
    }

    public function render(Premium $premium)
    {
        $premiumUser = $premium->isPremium((string) auth()->id());
        $guilds = $premiumUser ? $this->guilds() : [];

        if ($this->guildId !== '' && ! in_array($this->guildId, array_column($guilds, 'id'), true)) {
            $guilds[] = ['id' => $this->guildId, 'name' => __('profile.premium_server_unknown')];
        }

        return view('livewire.premium-server-picker', [
            'premium' => $premiumUser,
            'guilds' => $guilds,
        ]);
    }
}
