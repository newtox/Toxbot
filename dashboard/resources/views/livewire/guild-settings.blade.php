<div x-data="{ ctx: @js($previewContext) }" class="space-y-6 pb-32">
    <div class="flex flex-wrap items-center gap-4">
        <div class="flex min-w-0 flex-1 basis-72 items-center gap-4">
            <x-guild-icon :guild="['name' => $guildName, 'icon_url' => $guildIcon]" class="rounded-2xl" size="size-14" />
            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-dc-muted">{{ $guildName }}</p>
                <h1 class="truncate text-2xl font-bold text-dc-header">{{ __('menu.section_'.$section) }}</h1>
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

    @if ($section === 'general')
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

    @endif

    @if ($section === 'levels')
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

    @endif

    @if ($section === 'streams')
    <section id="streams" class="card scroll-mt-20 space-y-5">
        <div>
            <h2 class="text-lg font-semibold text-dc-header">{{ __('streams.title') }}</h2>
            <p class="text-sm text-dc-muted">{{ __('streams.description') }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="stream-channel">{{ __('guild.channel') }}</label>
                <select id="stream-channel" wire:model="streamChannel" class="input">
                    <option value="">{{ __('streams.no_channel') }}</option>
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
                @error('streamChannel') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="stream-role">{{ __('streams.ping_role') }}</label>
                <select id="stream-role" wire:model="streamRole" class="input">
                    <option value="">{{ __('streams.no_ping') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role['id'] }}">@ {{ $role['name'] }}</option>
                    @endforeach
                </select>
                @error('streamRole') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>
        </div>

        <div x-data="{ target: 'streamMsg' }" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="stream-msg">{{ __('streams.live_message') }}</label>
                    <textarea id="stream-msg" x-ref="streamMsg" x-on:focus="target = 'streamMsg'" wire:model="streamMsg" rows="2" placeholder="{{ \App\Models\Guild::DEFAULT_STREAM_MESSAGE }}"
                              class="input resize-y font-mono text-sm"></textarea>
                    @error('streamMsg') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="stream-video-msg">{{ __('streams.video_message') }}</label>
                    <textarea id="stream-video-msg" x-ref="streamVideoMsg" x-on:focus="target = 'streamVideoMsg'" wire:model="streamVideoMsg" rows="2" placeholder="{{ \App\Models\Guild::DEFAULT_VIDEO_MESSAGE }}"
                              class="input resize-y font-mono text-sm"></textarea>
                    @error('streamVideoMsg') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach (\App\Models\Guild::STREAM_PLACEHOLDERS as $token => $hint)
                    <button type="button" title="{{ __($hint) }}" x-on:click="toxbot.insertAtCursor($refs[target], '{{ $token }}')"
                            class="rounded-full bg-dc-600 px-3 py-1 font-mono text-xs text-dc-text transition hover:bg-accent hover:text-on-accent">{{ $token }}</button>
                @endforeach
            </div>
        </div>

        <livewire:stream-list :guild-id="$guildId" />
    </section>

    <section id="live-role" class="card scroll-mt-20">
        <h2 class="text-lg font-semibold text-dc-header">{{ __('streams.live_role_title') }}</h2>
        <p class="mb-5 text-sm text-dc-muted">{{ __('streams.live_role_description') }}</p>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="live-role">{{ __('guild.role') }}</label>
                <select id="live-role" wire:model="liveRole" class="input">
                    <option value="">{{ __('streams.no_live_role') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role['id'] }}" @disabled(! $role['assignable'])>
                            {{ $role['name'] }}{{ $role['assignable'] ? '' : ' ('.__('guild.above_bot').')' }}
                        </option>
                    @endforeach
                </select>
                @error('liveRole') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>
            <div x-show="$wire.liveRole">
                <label class="label" for="live-role-required">{{ __('streams.live_role_required') }}</label>
                <select id="live-role-required" wire:model="liveRoleRequired" class="input">
                    <option value="">{{ __('streams.everyone') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role['id'] }}">{{ $role['name'] }}</option>
                    @endforeach
                </select>
                @error('liveRoleRequired') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="mt-3 text-sm text-dc-faint">{{ __('streams.live_role_hint') }}</p>
    </section>

    @endif

    @if ($section === 'tags')
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

    @endif

    @if ($section === 'moderation')
    <section id="moderation" class="card scroll-mt-20 space-y-5">
        <div>
            <h2 class="text-lg font-semibold text-dc-header">{{ __('moderation.settings_title') }}</h2>
            <p class="text-sm text-dc-muted">{{ __('moderation.settings_description') }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="modlog-channel">{{ __('moderation.modlog_channel') }}</label>
                <select id="modlog-channel" wire:model="modlogChannel" class="input">
                    <option value="">{{ __('moderation.no_modlog') }}</option>
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
                @error('modlogChannel') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-start gap-3 self-end rounded-lg bg-dc-900 p-3">
                <input type="checkbox" wire:model="modDm" class="mt-1 accent-[var(--color-accent)]">
                <span>
                    <span class="block text-sm font-medium text-dc-header">{{ __('moderation.dm_title') }}</span>
                    <span class="block text-xs text-dc-muted">{{ __('moderation.dm_hint') }}</span>
                </span>
            </label>
        </div>
        <p class="text-sm text-dc-faint">{{ __('moderation.audit_hint') }}</p>
    </section>

    <section class="card space-y-4">
        <div>
            <h2 class="text-lg font-semibold text-dc-header">{{ __('moderation.rules_title') }}</h2>
            <p class="text-sm text-dc-muted">{{ __('moderation.rules_description') }}</p>
        </div>
        <livewire:warn-rules :guild-id="$guildId" />
    </section>
    @endif

    <x-unsaved-bar />

    @if ($errors->any())
        <div class="fixed inset-x-0 bottom-24 z-30 px-4 md:left-70">
            <div class="mx-auto max-w-3xl rounded-lg bg-dc-red px-4 py-2 text-sm font-medium text-white shadow-lg">
                {{ __('common.not_saved') }}
            </div>
        </div>
    @endif
</div>
