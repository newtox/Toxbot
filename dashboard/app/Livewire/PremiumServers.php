<?php

namespace App\Livewire;

use App\Models\PremiumGrant;
use App\Services\GuildAccess;
use App\Services\Premium;
use Livewire\Component;
use RuntimeException;

class PremiumServers extends Component
{
    public function render(Premium $premium)
    {
        try {
            $guilds = array_values(array_filter(
                app(GuildAccess::class)->manageableGuilds(auth()->user()),
                fn (array $guild) => $guild['has_bot'],
            ));
            $failed = false;
        } catch (RuntimeException) {
            $guilds = [];
            $failed = true;
        }

        $grants = PremiumGrant::query()->active()->where('type', 'guild')
            ->whereIn('target_id', array_column($guilds, 'id'))
            ->pluck('ends_at', 'target_id');

        $guilds = array_map(fn (array $guild) => $guild + [
            'premium' => $premium->isGuildPremium($guild['id']),
            'until' => $grants[$guild['id']] ?? null,
        ], $guilds);

        usort($guilds, fn ($a, $b) => [$b['premium'], $a['name']] <=> [$a['premium'], $b['name']]);

        return view('livewire.premium-servers', [
            'guilds' => $guilds,
            'failed' => $failed,
            'storeUrl' => Premium::storeUrl(),
        ]);
    }
}
