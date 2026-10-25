<div class="space-y-4">
    @if ($types->isEmpty())
        <p class="text-sm text-dc-faint">{{ __('tickets.types_empty') }}</p>
    @else
        <ul class="divide-y divide-white/5 rounded-lg bg-dc-900">
            @foreach ($types as $type)
                <li wire:key="type-{{ $type->id }}" class="flex flex-wrap items-center gap-x-3 gap-y-2 px-3 py-2.5">
                    <div class="min-w-0 flex-1 basis-56">
                        <div class="truncate font-medium text-dc-header">
                            @if ($type->emoji && ! str_starts_with($type->emoji, '<'))<span class="mr-1">{{ $type->emoji }}</span>@endif{{ $type->name }}
                        </div>
                        <div class="truncate text-xs text-dc-muted">
                            {{ collect($type->roleIds())->map(fn ($id) => '@'.($roleNames[$id] ?? $id))->implode(', ') ?: __('tickets.no_team_roles') }}
                        </div>
                    </div>
                    <div class="ml-auto flex shrink-0 gap-2">
                        <button type="button" wire:click="edit({{ $type->id }})" class="btn-secondary px-3 py-1.5">{{ __('tags.edit') }}</button>
                        <button type="button" wire:click="remove({{ $type->id }})" data-confirm="{{ __('tickets.type_confirm_remove', ['name' => $type->name]) }}"
                                class="btn px-3 py-1.5 text-dc-red hover:bg-dc-red hover:text-white">{{ __('streams.remove') }}</button>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    <form wire:submit="save" class="space-y-4 rounded-lg bg-dc-900 p-4">
        <h3 class="text-sm font-semibold text-dc-header">{{ $editingId ? __('tickets.type_edit') : __('tickets.type_new') }}</h3>
        <div class="grid gap-4 sm:grid-cols-[1fr_8rem]">
            <div>
                <label class="label" for="type-name">{{ __('tickets.type_name') }}</label>
                <input id="type-name" type="text" wire:model="name" maxlength="80" placeholder="{{ __('tickets.type_name_example') }}" class="input">
                @error('name') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="type-emoji">{{ __('tickets.type_emoji') }}</label>
                <input id="type-emoji" type="text" wire:model="emoji" maxlength="50" placeholder="🎫" class="input">
                @error('emoji') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>
        </div>
        <div>
            <label class="label" for="type-description">{{ __('tickets.type_description') }}</label>
            <textarea id="type-description" wire:model="description" rows="3" maxlength="1000" placeholder="{{ __('tickets.type_description_hint') }}" class="input resize-y"></textarea>
            @error('description') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
        </div>
        <div>
            <h4 class="label">{{ __('tickets.type_roles') }}</h4>
            <div class="flex flex-wrap gap-2">
                @foreach ($roles as $role)
                    <label wire:key="team-{{ $role['id'] }}" class="cursor-pointer">
                        <input type="checkbox" wire:model="teamRoles" value="{{ $role['id'] }}" class="peer sr-only">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-dc-800 px-3 py-1 text-sm text-dc-muted ring-1 ring-white/10 transition peer-checked:bg-accent/15 peer-checked:text-dc-header peer-checked:ring-accent peer-focus-visible:ring-accent-fg">
                            <span class="size-2 rounded-full" style="background: {{ $role['color'] ?? '#99aab5' }}"></span>{{ $role['name'] }}
                        </span>
                    </label>
                @endforeach
            </div>
            @error('teamRoles.*') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            <p class="mt-2 text-sm text-dc-faint">{{ __('tickets.type_roles_hint') }}</p>
        </div>
        <div class="flex justify-end gap-2">
            @if ($editingId)
                <button type="button" wire:click="cancel" class="btn-ghost">{{ __('tags.cancel') }}</button>
            @endif
            <button type="submit" class="btn-primary">{{ $editingId ? __('admin.save') : __('streams.add') }}</button>
        </div>
    </form>
</div>
