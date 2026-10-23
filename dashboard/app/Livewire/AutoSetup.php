<?php

namespace App\Livewire;

use App\Models\Guild;
use App\Services\AutoSetup as AutoSetupService;
use App\Services\Discord;
use App\Services\GuildAccess;
use Illuminate\Http\Client\RequestException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AutoSetup extends Component
{
    #[Locked]
    public string $guildId;

    #[Locked]
    public string $guildName;

    #[Locked]
    public array $areas = AutoSetupService::AREAS;

    #[Locked]
    public bool $compact = false;

    public array $selected = [];

    public array $teamRoles = [];

    #[Locked]
    public ?array $steps = null;

    public function mount(string $guildId, string $guildName, array $areas = AutoSetupService::AREAS, bool $compact = false): void
    {
        $this->guildId = $guildId;
        $this->guildName = $guildName;
        $this->areas = array_values(array_intersect(AutoSetupService::AREAS, $areas));
        $this->compact = $compact;
        $this->selected = array_values(array_diff($this->areas, count($this->areas) > 1 ? ['verification'] : []));
        $this->teamRoles = collect($this->roles())->where('team', true)->pluck('id')->all();
    }

    public function run(AutoSetupService $setup): void
    {
        abort_if(app(GuildAccess::class)->find(auth()->user(), $this->guildId) === null, 403);

        $this->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => [Rule::in($this->areas)],
            'teamRoles' => ['required', 'array', 'min:1', 'max:10'],
            'teamRoles.*' => [Rule::in(array_column($this->roles(), 'id'))],
        ], [
            'selected.required' => __('setup.choose_area'),
            'selected.min' => __('setup.choose_area'),
            'teamRoles.required' => __('setup.choose_roles'),
            'teamRoles.min' => __('setup.choose_roles'),
            'teamRoles.max' => __('setup.too_many_roles'),
            'teamRoles.*' => __('guild.role_invalid'),
        ]);

        $user = auth()->user();
        $this->steps = $setup->run($this->guildId, $this->guildName, $this->selected, $this->teamRoles, $user->display_name);
        $this->dispatch('auto-setup-done');
    }

    public function dismiss(): void
    {
        $this->steps = null;
    }

    public function render()
    {
        return view('livewire.auto-setup', ['roles' => $this->roles()]);
    }

    private function roles(): array
    {
        try {
            $roles = app(Discord::class)->guildRoles($this->guildId);
        } catch (RequestException) {
            return [];
        }

        $verifyRole = Guild::query()->whereKey($this->guildId)->value('verify_role');

        return collect($roles)
            ->reject(fn ($r) => $r['id'] === $this->guildId || $r['id'] === $verifyRole || ($r['managed'] ?? false))
            ->sortByDesc('position')
            ->map(fn ($r) => [
                'id' => $r['id'],
                'name' => $r['name'],
                'color' => $r['color'] ? sprintf('#%06x', $r['color']) : null,
                'team' => ((int) ($r['permissions'] ?? 0) & AutoSetupService::TEAM_PERMISSIONS) !== 0,
            ])
            ->values()
            ->all();
    }
}
