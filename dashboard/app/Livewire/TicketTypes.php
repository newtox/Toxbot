<?php

namespace App\Livewire;

use App\Models\TicketType;
use App\Services\GuildAccess;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class TicketTypes extends Component
{
    #[Locked]
    public string $guildId;

    #[Locked]
    public array $roles = [];

    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public string $emoji = '';

    public string $description = '';

    public array $teamRoles = [];

    public function mount(string $guildId, array $roles = []): void
    {
        $this->guildId = $guildId;
        $this->roles = array_map(fn (array $role) => ['id' => $role['id'], 'name' => $role['name'], 'color' => $role['color']], $roles);
    }

    public function save(): void
    {
        $this->authorizeGuild();

        $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'emoji' => ['nullable', 'string', 'max:50', 'regex:/^(<a?:\w{2,32}:\d{15,22}>|[^\s<>:A-Za-z]{1,16})$/u'],
            'description' => ['nullable', 'string', 'max:1000'],
            'teamRoles' => ['array', 'max:10'],
            'teamRoles.*' => [Rule::in(array_column($this->roles, 'id'))],
        ], [
            'emoji.regex' => __('tickets.emoji_invalid'),
            'teamRoles.*' => __('guild.role_invalid'),
        ]);

        $fields = [
            'name' => trim($this->name),
            'emoji' => trim($this->emoji) ?: null,
            'description' => trim($this->description) ?: null,
            'roles' => $this->teamRoles === [] ? null : implode(',', array_unique($this->teamRoles)),
        ];

        if ($this->editingId !== null) {
            TicketType::query()->where('guild', $this->guildId)->whereKey($this->editingId)->update($fields);
        } else {
            $count = TicketType::query()->where('guild', $this->guildId)->count();
            if ($count >= TicketType::MAX_PER_GUILD) {
                $this->addError('name', __('tickets.types_limit', ['max' => TicketType::MAX_PER_GUILD]));

                return;
            }
            TicketType::create(['guild' => $this->guildId, 'position' => $count] + $fields);
        }

        $this->cancel();
        $this->dispatch('saved');
    }

    public function edit(int $id): void
    {
        $this->authorizeGuild();
        $type = TicketType::query()->where('guild', $this->guildId)->findOrFail($id);

        $this->editingId = $type->id;
        $this->name = $type->name;
        $this->emoji = (string) $type->emoji;
        $this->description = (string) $type->description;
        $this->teamRoles = $type->roleIds();
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'name', 'emoji', 'description', 'teamRoles');
        $this->resetValidation();
    }

    public function remove(int $id): void
    {
        $this->authorizeGuild();
        TicketType::query()->where('guild', $this->guildId)->whereKey($id)->delete();

        if ($this->editingId === $id) {
            $this->cancel();
        }
    }

    #[On('auto-setup-done')]
    public function refreshTypes(): void {}

    public function render()
    {
        return view('livewire.ticket-types', [
            'types' => TicketType::query()->where('guild', $this->guildId)->orderBy('position')->orderBy('id')->get(),
            'roleNames' => array_column($this->roles, 'name', 'id'),
        ]);
    }

    private function authorizeGuild(): void
    {
        abort_if(app(GuildAccess::class)->find(auth()->user(), $this->guildId) === null, 403);
    }
}
