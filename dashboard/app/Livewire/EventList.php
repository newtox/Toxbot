<?php

namespace App\Livewire;

use App\Models\GuildEvent;
use App\Models\GuildEventInvite;
use App\Services\Discord;
use App\Services\GuildAccess;
use App\Services\GuildEvents;
use Illuminate\Support\Facades\Auth;
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

    public int $voiceCount = 1;

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
            'voiceCount' => ['required', 'integer', 'between:1,'.GuildEvents::MAX_VOICE],
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
            $event = $events->create($this->guildId, $this->name, (string) Auth::id(), $this->teamRole !== '' ? $this->teamRole : null, $this->voiceCount);
        } catch (Throwable $e) {
            report($e);
            $this->addError('name', __('events.create_failed'));

            return;
        }

        $this->reset('name', 'teamRole', 'voiceCount');
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

    public function addUser(string $id, GuildEvents $events): void
    {
        $this->authorizeGuild();
        if (count($this->inviteUsers) < GuildEvents::MAX_INVITES && preg_match('/^\d{15,22}$/', $id)) {
            $member = rescue(fn () => $events->member($this->guildId, $id), null, report: false);
            $roleId = $this->invitingId === null ? null : GuildEvent::query()->forGuild($this->guildId)->find($this->invitingId)?->role_id;
            if ($member && ! ($roleId && in_array($roleId, $member['roles'], true))) {
                $this->inviteUsers[$id] = ['name' => $member['name'], 'avatar' => $member['avatar']];
            }
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

        $everyone = $events->inviteTargets($this->guildId, array_keys($this->inviteUsers), $this->inviteRoles);
        $targets = array_values(array_diff($everyone, [(string) Auth::id()]));
        if ($targets === [] && $everyone !== []) {
            $this->addError('invite', __('events.self_only'));

            return;
        }
        if ($targets === []) {
            $this->addError('invite', __('events.no_members'));

            return;
        }
        $participantIds = array_column(rescue(fn () => $events->participants($event), [], report: false), 'id');
        $skipped = count(array_intersect($targets, $participantIds));
        $targets = array_values(array_diff($targets, $participantIds));
        if ($targets === []) {
            $this->addError('invite', __('events.all_joined'));

            return;
        }
        if (count($targets) > GuildEvents::MAX_INVITES) {
            $this->addError('invite', __('events.too_many', ['max' => GuildEvents::MAX_INVITES, 'count' => count($targets)]));

            return;
        }

        $user = Auth::user();
        $guildName = (string) (rescue(fn () => $discord->guildInfo($this->guildId)['name'] ?? null, null, report: false) ?? '');
        $sent = $events->invite($event, $targets, $this->inviteMessage, (string) $user->id, $user->global_name ?? $user->username, $guildName);
        $failed = count($targets) - $sent;

        $this->reset('search', 'inviteUsers', 'inviteRoles', 'inviteMessage', 'invitingId');
        $this->notice = __('events.invited', ['count' => $sent, 'name' => $event->name])
            .($skipped > 0 ? ' '.__('events.already_joined', ['count' => $skipped]) : '')
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

        $participants = $list->mapWithKeys(fn ($event) => [
            $event->id => rescue(fn () => $events->participants($event), [], report: false),
        ]);
        $invitations = $list->mapWithKeys(fn ($event) => [
            $event->id => rescue(
                fn () => $events->invitations($event, array_column($participants[$event->id], 'id')),
                [GuildEventInvite::PENDING => [], GuildEventInvite::DECLINED => []],
                report: false,
            ),
        ]);

        $matches = $this->invitingId === null ? [] : rescue(fn () => $events->searchMembers(
            $this->guildId,
            $this->search,
            array_map('strval', array_keys($this->inviteUsers)),
            $eventRole,
            GuildEventInvite::query()->where('event_id', $this->invitingId)->pluck('status', 'user_id')->all(),
        ), [], report: false);

        return view('livewire.event-list', [
            'events' => $list,
            'participants' => $participants,
            'invitations' => $invitations,
            'roles' => $pickable,
            'roleNames' => collect($roles)->pluck('name', 'id'),
            'channels' => $channels,
            'matches' => $matches,
            'maxEvents' => GuildEvents::MAX_EVENTS,
            'maxInvites' => GuildEvents::MAX_INVITES,
            'maxName' => GuildEvents::MAX_NAME,
            'maxVoice' => GuildEvents::MAX_VOICE,
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
        abort_if(app(GuildAccess::class)->find(Auth::user(), $this->guildId) === null, 403);
    }
}
