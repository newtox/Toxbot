<div class="space-y-4">
    @if ($notice)
        <p class="rounded-lg border-l-4 border-dc-green bg-dc-900 px-4 py-3 text-sm">{{ $notice }}</p>
    @endif

    @if ($events->isEmpty())
        <p class="text-sm text-dc-faint">{{ __('events.empty') }}</p>
    @else
        <ul class="space-y-3">
            @foreach ($events as $event)
                @php($members = $participants[$event->id] ?? [])
                <li wire:key="event-{{ $event->id }}" class="rounded-lg bg-dc-900 p-4">
                    <div class="flex flex-wrap items-start gap-x-3 gap-y-3">
                        <div class="min-w-0 flex-1 basis-64">
                            <div class="truncate font-semibold text-dc-header">{{ $event->name }}</div>
                            <div class="mt-1.5 flex flex-wrap gap-1.5 text-xs">
                                @if ($event->role_id)
                                    <span class="dc-mention">@ {{ $roleNames[$event->role_id] ?? $event->name }}</span>
                                @endif
                                @foreach (['info_channel_id', 'text_channel_id'] as $column)
                                    @if ($event->{$column} && isset($channels[$event->{$column}]))
                                        <span class="dc-mention"># {{ $channels[$event->{$column}] }}</span>
                                    @endif
                                @endforeach
                                @foreach ($event->voiceChannelIds() as $voiceId)
                                    @if (isset($channels[$voiceId]))
                                        <span class="dc-mention inline-flex items-center gap-1"><x-icon name="volume" class="size-3" /> {{ $channels[$voiceId] }}</span>
                                    @endif
                                @endforeach
                            </div>
                            @if ($event->team_role_id)
                                <div class="mt-1.5 text-xs text-dc-muted">{{ __('events.team', ['role' => $roleNames[$event->team_role_id] ?? '?']) }}</div>
                            @endif
                        </div>
                        <div class="ml-auto flex shrink-0 gap-2">
                            <button type="button" wire:click="startInvite({{ $event->id }})" @class(['px-3 py-1.5', 'btn-primary' => $invitingId === $event->id, 'btn-secondary' => $invitingId !== $event->id])>{{ __('events.invite') }}</button>
                            <button type="button" wire:click="end({{ $event->id }})" data-confirm="{{ __('events.end_confirm', ['name' => $event->name]) }}"
                                    class="btn px-3 py-1.5 text-dc-red hover:bg-dc-red hover:text-white">{{ __('events.end') }}</button>
                        </div>
                    </div>

                    <div class="mt-3 border-t border-white/5 pt-3">
                        <p class="mb-2 text-xs font-semibold tracking-wide text-dc-muted uppercase">{{ __('events.participants', ['count' => count($members)]) }}</p>
                        @if ($members === [])
                            <p class="text-sm text-dc-faint">{{ __('events.no_participants') }}</p>
                        @else
                            <ul class="flex flex-wrap gap-2">
                                @foreach ($members as $member)
                                    <li class="flex items-center gap-1.5 rounded-full bg-dc-800 py-0.5 pr-2.5 pl-0.5 text-sm">
                                        <img src="{{ $member['avatar'] }}" alt="" class="size-6 rounded-full" loading="lazy">
                                        <span class="max-w-40 truncate">{{ $member['name'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    @if ($invitingId === $event->id)
                        <form novalidate wire:submit="sendInvites" class="mt-4 space-y-4 border-t border-white/5 pt-4">
                            <div>
                                <label class="label" for="event-search-{{ $event->id }}">{{ __('events.invite_members') }}</label>
                                <input id="event-search-{{ $event->id }}" type="search" wire:model.live.debounce.300ms="search" autocomplete="off"
                                       placeholder="{{ __('events.search_placeholder') }}" class="input max-w-md">
                                @if ($matches !== [])
                                    <ul class="mt-2 max-w-md divide-y divide-white/5 overflow-hidden rounded-lg bg-dc-800">
                                        @foreach ($matches as $match)
                                            <li>
                                                <button type="button" wire:click="addUser('{{ $match['id'] }}', @js($match['name']))"
                                                        class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-dc-700">
                                                    <span class="truncate">{{ $match['name'] }} <span class="text-dc-faint">{{ '@'.$match['username'] }}</span></span>
                                                    <x-icon name="plus" class="size-4 shrink-0 text-dc-muted" />
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if ($inviteUsers !== [])
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($inviteUsers as $id => $userName)
                                            <span wire:key="invite-user-{{ $id }}" class="inline-flex items-center gap-1 rounded-full bg-accent/20 py-1 pr-1 pl-3 text-sm text-dc-header">
                                                {{ $userName }}
                                                <button type="button" wire:click="removeUser('{{ $id }}')" class="rounded-full p-0.5 hover:bg-white/10" aria-label="{{ __('events.remove') }}">
                                                    <x-icon name="close" class="size-3.5" />
                                                </button>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            @if ($roles !== [])
                                <div>
                                    <p class="label">{{ __('events.invite_roles') }}</p>
                                    <div class="flex max-h-40 flex-wrap gap-2 overflow-y-auto">
                                        @foreach ($roles as $role)
                                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-full bg-dc-800 px-3 py-1 text-sm has-[:checked]:bg-accent/20 has-[:checked]:text-dc-header">
                                                <input type="checkbox" value="{{ $role['id'] }}" wire:model="inviteRoles" class="accent-[var(--color-accent)]">
                                                {{ $role['name'] }}
                                            </label>
                                        @endforeach
                                    </div>
                                    <p class="mt-2 text-xs text-dc-faint">{{ __('events.invite_roles_hint', ['max' => $maxInvites]) }}</p>
                                </div>
                            @endif

                            <div>
                                <label class="label" for="event-message-{{ $event->id }}">{{ __('events.message') }}</label>
                                <textarea id="event-message-{{ $event->id }}" wire:model="inviteMessage" rows="3" maxlength="2000"
                                          placeholder="{{ __('events.message_placeholder') }}" class="input resize-y"></textarea>
                            </div>

                            @error('invite') <p class="text-sm text-dc-red">{{ $message }}</p> @enderror

                            <div class="flex justify-end gap-2">
                                <button type="button" wire:click="startInvite({{ $event->id }})" class="btn-ghost">{{ __('tags.cancel') }}</button>
                                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="sendInvites">{{ __('events.send') }}</button>
                            </div>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
    <p class="text-xs text-dc-faint tabular-nums">{{ __('events.count', ['count' => $events->count(), 'limit' => $maxEvents]) }}</p>

    <form novalidate wire:submit="create" class="space-y-4 rounded-lg bg-dc-900 p-4">
        <h3 class="text-sm font-semibold text-dc-header">{{ __('events.new') }}</h3>
        <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_8rem]">
            <div>
                <label class="label" for="event-name">{{ __('events.name') }}</label>
                <input id="event-name" type="text" wire:model="name" maxlength="{{ $maxName }}" autocomplete="off" placeholder="{{ __('events.name_placeholder') }}" class="input">
                @error('name') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="event-team">{{ __('events.team_role') }}</label>
                <select id="event-team" wire:model="teamRole" class="input">
                    <option value="">{{ __('events.team_none') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role['id'] }}">@ {{ $role['name'] }}</option>
                    @endforeach
                </select>
                @error('teamRole') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="event-voice">{{ __('events.voice_count') }}</label>
                <select id="event-voice" wire:model="voiceCount" class="input">
                    @foreach (range(1, $maxVoice) as $count)
                        <option value="{{ $count }}">{{ $count }}</option>
                    @endforeach
                </select>
                @error('voiceCount') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="text-xs text-dc-faint">{{ __('events.new_hint') }}</p>
        <div class="flex justify-end">
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="create">{{ __('events.create') }}</button>
        </div>
    </form>
</div>
