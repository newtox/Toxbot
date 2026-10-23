<section x-data="{ open: @js(! $compact) }" class="card space-y-5">
    <div class="flex flex-wrap items-start gap-4">
        <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-accent/15 text-accent-fg">
            <x-icon name="sparkles" />
        </span>
        <div class="min-w-0 flex-1 basis-60">
            <h2 class="text-lg font-semibold text-dc-header">{{ __('setup.title') }}</h2>
            <p class="text-sm text-dc-muted">{{ __('setup.description') }}</p>
        </div>
        @if ($compact)
            <button type="button" x-on:click="open = !open" class="btn-secondary max-sm:w-full" :aria-expanded="open">
                <span x-show="!open">{{ __('setup.open') }}</span>
                <span x-show="open" x-cloak>{{ __('setup.close') }}</span>
            </button>
        @endif
    </div>

    <div x-show="open" @if ($compact) x-cloak x-collapse.duration.200ms @endif class="space-y-5">
        @if ($steps !== null)
            <ul class="space-y-2 rounded-lg bg-dc-900 p-4" role="status">
                @foreach ($steps as $step)
                    <li class="flex gap-2.5 text-sm">
                        <x-icon :name="$step['ok'] ? 'check' : 'alert'" @class(['mt-0.5 size-4 shrink-0', 'text-accent-fg' => $step['ok'], 'text-dc-red' => ! $step['ok']]) />
                        <span @class(['text-dc-text' => $step['ok'], 'text-dc-red' => ! $step['ok']])>{{ $step['text'] }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="flex flex-wrap justify-end gap-2">
                <button type="button" wire:click="dismiss" class="btn-ghost">{{ __('setup.again') }}</button>
                <button type="button" x-on:click="window.location.reload()" class="btn-secondary">{{ __('setup.reload') }}</button>
            </div>
        @else
            <div>
                <h3 class="label">{{ __('setup.team_roles') }}</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach ($roles as $role)
                        <label wire:key="setup-role-{{ $role['id'] }}" class="cursor-pointer">
                            <input type="checkbox" wire:model="teamRoles" value="{{ $role['id'] }}" class="peer sr-only">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-dc-800 px-3 py-1 text-sm text-dc-muted ring-1 ring-white/10 transition peer-checked:bg-accent/15 peer-checked:text-dc-header peer-checked:ring-accent peer-focus-visible:ring-accent-fg">
                                <span class="size-2 rounded-full" style="background: {{ $role['color'] ?? '#99aab5' }}"></span>{{ $role['name'] }}
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('teamRoles') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
                @error('teamRoles.*') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
                <p class="mt-2 text-sm text-dc-faint">{{ __('setup.team_roles_hint') }}</p>
            </div>

            <div>
                <h3 class="label">{{ __('setup.areas') }}</h3>
                <div @class(['grid gap-2', 'sm:grid-cols-2' => count($areas) > 1])>
                    @foreach ($areas as $area)
                        <label wire:key="setup-area-{{ $area }}" class="flex cursor-pointer gap-3 rounded-lg bg-dc-900 p-3 ring-1 ring-white/5 transition has-checked:ring-accent">
                            <input type="checkbox" wire:model="selected" value="{{ $area }}" class="mt-1 size-4 shrink-0 accent-accent">
                            <span class="min-w-0">
                                <span class="block font-medium text-dc-header">{{ __('setup.area_'.$area) }}</span>
                                <span class="block text-sm text-dc-muted">{{ __('setup.area_'.$area.'_text') }}</span>
                                @if ($area === 'verification')
                                    <span class="mt-1 block text-sm text-dc-yellow">{{ __('setup.verification_warning') }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('selected') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <p class="mr-auto text-sm text-dc-faint">{{ __('setup.existing_hint') }}</p>
                <button type="button" wire:click="run" wire:confirm="{{ __('setup.confirm') }}" wire:loading.attr="disabled" class="btn-primary max-sm:w-full">
                    <span wire:loading.remove wire:target="run">{{ __('setup.run') }}</span>
                    <span wire:loading wire:target="run">{{ __('setup.running') }}</span>
                </button>
            </div>
        @endif
    </div>
</section>
