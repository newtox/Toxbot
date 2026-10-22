<div x-data="{ ctx: @js($previewContext) }" class="space-y-6 pb-32">
    <div class="flex flex-wrap items-center gap-4">
        <div class="flex min-w-0 flex-1 basis-72 items-center gap-4">
            <x-guild-icon :guild="['name' => $guildName, 'icon_url' => $guildIcon]" class="rounded-2xl" size="size-14" />
            <div class="min-w-0">
                <h1 class="truncate text-2xl font-bold text-dc-header">{{ $guildName }}</h1>
                <p class="text-sm text-dc-muted">{{ __('guild.intro') }}</p>
            </div>
        </div>
        <button type="button" wire:click="refreshDiscordData" wire:loading.attr="disabled" class="btn-secondary max-sm:w-full">
            <x-icon name="refresh" class="size-4" wire:loading.class="animate-spin" wire:target="refreshDiscordData" />
            {{ __('guild.reload') }}
        </button>
    </div>

    @if ($loadError)
        <div class="rounded-lg border-l-4 border-dc-red bg-dc-800 px-4 py-3 text-sm">{{ $loadError }}</div>
    @endif

    @include('livewire.partials.announcement', [
        'type' => 'welcome',
        'title' => __('guild.welcome_title'),
        'description' => __('guild.welcome_description'),
        'example' => __('guild.welcome_example'),
    ])

    @include('livewire.partials.announcement', [
        'type' => 'bye',
        'title' => __('guild.bye_title'),
        'description' => __('guild.bye_description'),
        'example' => __('guild.bye_example'),
    ])

    <section id="autorole" class="card scroll-mt-20">
        <h2 class="text-lg font-semibold text-dc-header">{{ __('guild.autorole_title') }}</h2>
        <p class="mb-5 text-sm text-dc-muted">{{ __('guild.autorole_description') }}</p>

        <label class="label" for="autorole-select">{{ __('guild.role') }}</label>
        <select id="autorole-select" wire:model="autorole" class="input max-w-md">
            <option value="">{{ __('guild.no_autorole') }}</option>
            @foreach ($roles as $role)
                <option value="{{ $role['id'] }}" @disabled(! $role['assignable'])>
                    {{ $role['name'] }}{{ $role['assignable'] ? '' : ' ('.__('guild.above_bot').')' }}
                </option>
            @endforeach
        </select>
        @error('autorole') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror

        @if (collect($roles)->contains('assignable', false))
            <p class="mt-3 text-sm text-dc-faint">
                {{ __('guild.autorole_hint') }}
            </p>
        @endif
    </section>

    <section id="levels" class="card scroll-mt-20">
        <div class="flex items-start gap-4">
            <div class="flex-1">
                <h2 class="text-lg font-semibold text-dc-header">{{ __('guild.levelup_title') }}</h2>
                <p class="text-sm text-dc-muted">{{ __('guild.levelup_description') }}</p>
            </div>
            <label class="relative mt-1 inline-flex cursor-pointer items-center">
                <input type="checkbox" wire:model="levelupEnabled" class="peer sr-only">
                <span class="h-6 w-10 rounded-full bg-dc-400 transition-colors peer-checked:bg-accent peer-focus-visible:ring-2 peer-focus-visible:ring-accent-fg peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-dc-800"></span>
                <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                <span class="sr-only">{{ __('guild.enable', ['name' => __('guild.levelup_title')]) }}</span>
            </label>
        </div>

        <div x-show="$wire.levelupEnabled" x-collapse.duration.200ms class="mt-6">
            <label class="label" for="levelup-channel">{{ __('guild.channel') }}</label>
            <select id="levelup-channel" wire:model="levelupChannel" class="input max-w-md">
                <option value="">{{ __('guild.levelup_same_channel') }}</option>
                @foreach ($channelGroups as $group)
                    @if ($group['name'])
                        <optgroup label="{{ $group['name'] }}">
                    @endif
                    @foreach ($group['channels'] as $c)
                        <option value="{{ $c['id'] }}"># {{ $c['name'] }}</option>
                    @endforeach
                    @if ($group['name'])
                        </optgroup>
                    @endif
                @endforeach
            </select>
            @error('levelupChannel') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
        </div>
    </section>

    <section id="tags" class="card scroll-mt-20">
        <div class="flex items-start gap-4">
            <div class="flex-1">
                <h2 class="text-lg font-semibold text-dc-header">{{ __('guild.tags_title') }}</h2>
                <p class="text-sm text-dc-muted">{{ __('guild.tags_description') }}</p>
            </div>
            <label class="relative mt-1 inline-flex cursor-pointer items-center">
                <input type="checkbox" wire:model="tagsEnabled" class="peer sr-only">
                <span class="h-6 w-10 rounded-full bg-dc-400 transition-colors peer-checked:bg-accent peer-focus-visible:ring-2 peer-focus-visible:ring-accent-fg peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-dc-800"></span>
                <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                <span class="sr-only">{{ __('guild.enable', ['name' => __('guild.tags_title')]) }}</span>
            </label>
        </div>

        <div class="mt-6">
            <h3 class="label">{{ __('guild.blocked_tags') }}</h3>
            @if ($blockedTags->isEmpty())
                <p class="text-sm text-dc-faint">{{ __('guild.no_blocked_tags') }}</p>
            @else
                <ul class="divide-y divide-black/25 rounded-lg bg-dc-900">
                    @foreach ($blockedTags as $block)
                        <li wire:key="block-{{ $block->tag_id }}" class="flex items-center gap-3 px-3 py-2">
                            <span class="flex-1 font-mono text-sm text-dc-header">{{ $block->tag->name }}</span>
                            <button type="button" wire:click="unblockTag({{ $block->tag_id }})" class="btn-secondary px-3 py-1">{{ __('guild.unblock') }}</button>
                        </li>
                    @endforeach
                </ul>
            @endif
            <p class="mt-3 text-sm text-dc-faint">{{ __('guild.block_hint') }}</p>
        </div>
    </section>

    <x-unsaved-bar />

    @if ($errors->any())
        <div class="fixed inset-x-0 bottom-24 z-30 px-4 md:left-70">
            <div class="mx-auto max-w-3xl rounded-lg bg-dc-red px-4 py-2 text-sm font-medium text-white shadow-lg">
                {{ __('common.not_saved') }}
            </div>
        </div>
    @endif
</div>
