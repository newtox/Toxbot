<?php

namespace App\Livewire;

use App\Models\BotUser;
use App\Services\Premium;
use App\Support\Level;
use Livewire\Attributes\On;
use Livewire\Component;

class ProfileOverview extends Component
{
    public int $version = 0;

    #[On('profile-changed')]
    public function refreshCard(): void
    {
        $this->version++;
    }

    public function render()
    {
        $userId = (string) auth()->id();
        $bot = BotUser::forDiscordId($userId);

        return view('livewire.profile-overview', [
            'cardUrl' => route('card', ['user' => $userId, 'v' => $this->version.'-'.time()]),
            'level' => Level::fromXp((int) $bot->xp),
            'credits' => (int) $bot->credits,
            'rank' => $bot->exists ? $bot->globalRank() : null,
            'commands' => (int) $bot->commands_used,
            'premium' => app(Premium::class)->isPremium($userId),
        ]);
    }
}
