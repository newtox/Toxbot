<?php

namespace App\Livewire;

use App\Models\GuildEvent;
use App\Services\Discord;
use App\Services\GuildAccess;
use App\Services\GuildEvents;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

class EventList extends Component
{
    #[Locked]
    public string $guildId;

    public string $name = '';

    public string $teamRole = '';

    #[Locked]
    public ?int $invitingId = null;

    public string $search = '';

    public array $inviteUsers = [];

    public array $inviteRoles = [];

    public string $inviteMessage = '';

    #[Locked]
    public ?string $notice = null;

    public function mount(string $guildId): void
    {
        $this->guildId = $guildId;
    }

    public function create(GuildEvents $events, Discord $discord): void
    {
        $this->authorizeGuild();
        $this->name = trim($this->name);

        $this->validate([
            'name' => ['required', 'string', 'max:'.GuildEvents::MAX_NAME],
            'teamRole' => ['nullable', Rule::in(array_column($this->pickableRoles($this->roles($discord), $this->eventRoleIds()), 'id'))],
        ], [
            'name.required' => __('events.name_required'),
            'name.max' => __('events.name_max', ['max' => GuildEvents::MAX_NAME]),
            'teamRole.in' => __('guild.role_invalid'),
        ]);

        if (GuildEvent::query()->forGuild($this->guildId)->count() >= GuildEvents::MAX_EVENTS) {
            $this->addError('name', __('events.limit', ['limit' => GuildEvents::MAX_EVENTS]));

            return;
        }

        try {
            $event = $events->create($this->guildId, $this->name, (string) auth()->id(), $this->teamRole !== '' ? $this->teamRole : null);
        } catch (Throwable $e) {
            report($e);
            $this->addError('name', __('events.create_failed'));

            return;
        }

        $this->reset('name', 'teamRole');
        $this->notice = __('events.created', ['name' => $event->name]);
        $this->dispatch('saved');
    }

    public function startInvite(int $id): void
    {
        $this->authorizeGuild();
        $this->event($id);
        $this->resetErrorBag();
        $this->reset('search', 'inviteUsers', 'inviteRoles', 'inviteMessage', 'notice');
        $this->invitingId = $this->invitingId === $id ? null : $id;
    }

    public function addUser(string $id, string $name): void
    {
        if (count($this->inviteUsers) < GuildEvents::MAX_INVITES && preg_match('/^\d{15,22}$/', $id)) {
            $this->inviteUsers[$id] = mb_substr($name, 0, 80);
        }
        $this->search = '';
    }

    public function removeUser(string $id): void
    {
        unset($this->inviteUsers[$id]);
    }

    public function sendInvites(GuildEvents $events, Discord $discord): void
    {
        $this->authorizeGuild();
        $event = $this->event((int) $this->invitingId);

        $this->validate([
            'inviteRoles' => ['array'],
            'inviteRoles.*' => [Rule::in(array_column($this->pickableRoles($this->roles($discord), $this->eventRoleIds()), 'id'))],
            'inviteMessage' => ['nullable', 'string', 'max:2000'],
        ]);

        $targets = array_values(array_diff(
            $events->inviteTargets($this->guildId, array_keys($this->inviteUsers), $this->inviteRoles),
            [(string) auth()->id()],
        ));
        if ($targets === []) {
            $this->addError('invite', __('events.no_members'));

            return;
        }
        if (count($targets) > GuildEvents::MAX_INVITES) {
            $this->addError('invite', __('events.too_many', ['max' => GuildEvents::MAX_INVITES, 'count' => count($targets)]));

            return;
        }

        $user = auth()->user();
        $guildName = (string) (rescue(fn () => $discord->guildInfo($this->guildId)['name'] ?? null, null, report: false) ?? '');
        $sent = $events->invite($event, $targets, $this->inviteMessage, $user->global_name ?? $user->username, $guildName);
        $failed = count($targets) - $sent;

        $this->reset('search', 'inviteUsers', 'inviteRoles', 'inviteMessage', 'invitingId');
        $this->notice = __('events.invited', ['count' => $sent, 'name' => $event->name])
            .($failed > 0 ? ' '.__('events.invite_failed', ['count' => $failed]) : '');
    }

    public function end(int $id, GuildEvents $events): void
    {
        $this->authorizeGuild();
        $event = $this->event($id);
        $events->end($event);
        if ($this->invitingId === $id) {
            $this->invitingId = null;
        }
        $this->notice = __('events.ended', ['name' => $event->name]);
    }

    public function render(GuildEvents $events, Discord $discord)
    {
        $list = GuildEvent::query()->forGuild($this->guildId)->orderBy('id')->get();
        $roles = $this->roles($discord);
        $pickable = $this->pickableRoles($roles, $list->pluck('role_id')->filter()->all());
        $eventRole = $list->firstWhere('id', $this->invitingId)?->role_id;
        $channels = collect(rescue(fn () => $discord->guildChannels($this->guildId), [], report: false))->pluck('name', 'id');

        $matches = [];
        $query = trim($this->search);
        if ($this->invitingId !== null && mb_strlen($query) >= 2) {
            $matches = collect(rescue(fn () => $discord->searchGuildMembers($this->guildId, $query), [], report: false))
                ->reject(fn ($member) => ($member['user']['bot'] ?? false)
                    || isset($this->inviteUsers[$member['user']['id']])
                    || ($eventRole && in_array($eventRole, $member['roles'] ?? [], true)))
                ->take(8)
                ->map(fn ($member) => [
                    'id' => (string) $member['user']['id'],
                    'name' => $member['nick'] ?? $member['user']['global_name'] ?? $member['user']['username'],
                    'username' => $member['user']['username'],
                ])
                ->values()
                ->all();
        }

        return view('livewire.event-list', [
            'events' => $list,
            'participants' => $list->mapWithKeys(fn ($event) => [
                $event->id => rescue(fn () => $events->participants($event), [], report: false),
            ]),
            'roles' => $pickable,
            'roleNames' => collect($roles)->pluck('name', 'id'),
            'channels' => $channels,
            'matches' => $matches,
            'maxEvents' => GuildEvents::MAX_EVENTS,
            'maxInvites' => GuildEvents::MAX_INVITES,
            'maxName' => GuildEvents::MAX_NAME,
        ]);
    }

    private function event(int $id): GuildEvent
    {
        return GuildEvent::query()->forGuild($this->guildId)->findOrFail($id);
    }

    private function roles(Discord $discord): array
    {
        return collect(rescue(fn () => $discord->guildRoles($this->guildId), [], report: false))
            ->reject(fn ($role) => (string) $role['id'] === $this->guildId || isset($role['tags']['bot_id']) || isset($role['tags']['integration_id']))
            ->sortByDesc('position')
            ->map(fn ($role) => ['id' => (string) $role['id'], 'name' => $role['name']])
            ->values()
            ->all();
    }

    private function pickableRoles(array $roles, array $eventRoleIds): array
    {
        return array_values(array_filter($roles, fn ($role) => ! in_array($role['id'], $eventRoleIds, true)));
    }

    private function eventRoleIds(): array
    {
        return GuildEvent::query()->forGuild($this->guildId)->whereNotNull('role_id')->pluck('role_id')->all();
    }

    private function authorizeGuild(): void
    {
        abort_if(app(GuildAccess::class)->find(auth()->user(), $this->guildId) === null, 403);
    }
}
