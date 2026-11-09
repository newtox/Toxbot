<?php

namespace App\Livewire\Concerns;

use App\Services\GuildAccess;
use App\Services\GuildMembers;
use Illuminate\Support\Facades\Auth;

trait PicksMember
{
    public string $pickGuild = '';

    public string $pickSearch = '';

    public array $picked = [];

    public function pickMember(string $id): void
    {
        $guild = $this->pickableGuild();
        $member = $guild && preg_match('/^\d{15,22}$/', $id) && $id !== (string) Auth::id()
            ? rescue(fn () => app(GuildMembers::class)->member($guild['id'], $id), null, report: false)
            : null;

        $this->picked = $member ? [
            'id' => $member['id'],
            'name' => $member['name'],
            'username' => $member['username'],
            'avatar' => $member['avatar'],
            'guild' => $guild['id'],
        ] : [];
        $this->pickSearch = '';
    }

    public function clearPick(): void
    {
        $this->picked = [];
    }

    public function updatedPickGuild(): void
    {
        $this->picked = [];
        $this->pickSearch = '';
    }

    protected function pickedMemberId(): ?string
    {
        $id = $this->picked['id'] ?? null;
        $guild = $this->pickableGuild();
        if (! $id || ! $guild || ($this->picked['guild'] ?? null) !== $guild['id'] || $id === (string) Auth::id()) {
            return null;
        }

        return rescue(fn () => app(GuildMembers::class)->member($guild['id'], $id), null, report: false) ? $id : null;
    }

    protected function resetPick(): void
    {
        $this->picked = [];
        $this->pickSearch = '';
    }

    protected function pickerData(): array
    {
        $guilds = $this->pickableGuilds();
        if ($this->pickGuild === '' && $guilds !== []) {
            $this->pickGuild = $guilds[0]['id'];
        }
        $guild = $this->pickableGuild($guilds);

        return [
            'pickGuilds' => $guilds,
            'pickMatches' => $guild && $this->picked === []
                ? rescue(fn () => app(GuildMembers::class)->search($guild['id'], $this->pickSearch, [(string) Auth::id()], 6), [], report: false)
                : [],
        ];
    }

    private function pickableGuilds(): array
    {
        return rescue(fn () => app(GuildAccess::class)->dashboardGuilds(Auth::user()), [], report: false);
    }

    private function pickableGuild(?array $guilds = null): ?array
    {
        return collect($guilds ?? $this->pickableGuilds())->firstWhere('id', $this->pickGuild);
    }
}
