<div x-data="{ ctx: @js($previewContext) }" class="space-y-6 pb-32">
    <div class="flex flex-wrap items-center gap-4">
        <div class="flex min-w-0 flex-1 basis-72 items-center gap-4">
            <x-guild-icon :guild="['name' => $guildName, 'icon_url' => $guildIcon]" class="rounded-2xl" size="size-14" />
            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-dc-muted">{{ $guildName }}</p>
                <h1 class="truncate text-2xl font-bold text-dc-header">{{ __('menu.section_'.$section) }}</h1>
            </div>
        </div>
        <x-reload-button action="refreshDiscordData" :label="__('guild.reload')" />
    </div>

    @if ($loadError)
        <div class="rounded-lg border-l-4 border-dc-red bg-dc-800 px-4 py-3 text-sm">{{ $loadError }}</div>
    @endif

    @if ($section === 'welcome')
    @include('livewire.partials.announcement', [
        'type' => 'welcome',
        'title' => __('guild.welcome_title'),
        'description' => __('guild.welcome_description'),
        'example' => __('guild.welcome_example'),
    ])
    @endif

    @if ($section === 'goodbye')
    @include('livewire.partials.announcement', [
        'type' => 'bye',
        'title' => __('guild.bye_title'),
        'description' => __('guild.bye_description'),
        'example' => __('guild.bye_example'),
    ])
    @endif

    @if ($section === 'autorole')
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

        @if ($verifyEnabled)
            <p class="mt-3 rounded-lg border-l-4 border-accent bg-dc-900 px-3 py-2 text-sm">{{ __('guild.autorole_after_verify') }}</p>
        @endif
    </section>
    @endif

    @if ($section === 'language')
    <section id="language" class="card scroll-mt-20">
        <h2 class="text-lg font-semibold text-dc-header">{{ __('guild.language_title') }}</h2>
        <p class="mb-5 text-sm text-dc-muted">{{ __('guild.language_description') }}</p>

        <label class="label" for="language-select">{{ __('guild.language_label') }}</label>
        <select id="language-select" wire:model="language" class="input max-w-md">
            @foreach (config('app.locales') as $locale)
                <option value="{{ $locale['bot'] }}">{{ $locale['name'] }}</option>
            @endforeach
        </select>
        @error('language') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
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

        <label class="mt-6 flex cursor-pointer items-start gap-3 rounded-lg bg-dc-900 p-3">
            <input type="checkbox" wire:model="levelupDm" class="mt-1 size-4 shrink-0 accent-[var(--color-accent)]">
            <span>
                <span class="block font-medium text-dc-header">{{ __('guild.levelup_dm_title') }}</span>
                <span class="block text-sm text-dc-muted">{{ __('guild.levelup_dm_description') }}</span>
            </span>
        </label>
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

        <div>
            <label class="label" for="stream-end">{{ __('streams.end_action') }}</label>
            <select id="stream-end" wire:model="streamEndAction" class="input max-w-md">
                <option value="keep">{{ __('streams.end_keep') }}</option>
                <option value="edit">{{ __('streams.end_edit') }}</option>
                <option value="delete">{{ __('streams.end_delete') }}</option>
            </select>
            @error('streamEndAction') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            <p class="mt-2 text-sm text-dc-faint">{{ __('streams.end_server_hint') }}</p>
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
    @endif

    @if ($section === 'liverole')
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

    @if ($section === 'starboard')
    <section id="starboard" class="card space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-dc-header">{{ __('starboard.settings_title') }}</h2>
            <p class="text-sm text-dc-muted">{{ __('starboard.settings_description') }}</p>
        </div>

        <div>
            <label class="label" for="starboard-channel">{{ __('guild.channel') }}</label>
            <select id="starboard-channel" wire:model="starboardChannel" class="input max-w-md">
                <option value="">{{ __('starboard.off') }}</option>
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
            @error('starboardChannel') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
        </div>

        <div>
            <span class="label">{{ __('starboard.emoji') }}</span>
            <div class="flex flex-wrap gap-1.5">
                @foreach (array_merge(array_map(fn ($e) => ['code' => $e, 'name' => $e], \App\Livewire\GuildSettings::STARBOARD_EMOJIS), $serverEmojis) as $choice)
                    <button type="button" wire:key="star-emoji-{{ $choice['code'] }}" x-on:click="$wire.starboardEmoji = @js($choice['code'])"
                            title="{{ $choice['name'] }}" aria-label="{{ $choice['name'] }}"
                            class="grid size-10 place-items-center rounded-lg bg-dc-900 text-xl ring-1 ring-white/5 transition hover:bg-dc-700"
                            x-bind:class="$wire.starboardEmoji === @js($choice['code']) && 'ring-2 ring-accent! bg-accent/15'">
                        <x-emoji :code="$choice['code']" />
                    </button>
                @endforeach
            </div>
            <label class="mt-3 block text-sm text-dc-muted" for="starboard-emoji">{{ __('starboard.emoji_custom') }}</label>
            <input id="starboard-emoji" type="text" wire:model="starboardEmoji" maxlength="80" class="input mt-1 max-w-xs">
            @error('starboardEmoji') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="label" for="starboard-threshold">{{ __('starboard.threshold') }}</label>
            <input id="starboard-threshold" type="number" min="1" max="50" wire:model="starboardThreshold" class="input max-w-32">
            @error('starboardThreshold') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            <p class="mt-2 text-sm text-dc-faint">{{ __('starboard.threshold_hint') }}</p>
        </div>
    </section>

    <section class="card space-y-4">
        <h2 class="text-lg font-semibold text-dc-header">{{ __('starboard.posts_title') }}</h2>
        <livewire:starboard-list :guild-id="$guildId" :key="'starboard-list'" />
    </section>
    @endif

    @if ($section === 'moderation')
    <livewire:auto-setup :guild-id="$guildId" :guild-name="$guildName" :areas="['moderation']" :compact="true" :key="'auto-setup-moderation'" />

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

    @if (in_array($section, ['verification', 'links', 'raid'], true))
    <livewire:auto-setup :guild-id="$guildId" :guild-name="$guildName" :areas="$section === 'verification' ? ['verification'] : ['protection']" :compact="true" :key="'auto-setup-'.$section" />

    @if ($protectionNotice)
        <p class="rounded-lg border-l-4 border-accent bg-dc-800 px-4 py-3 text-sm" role="status">{{ $protectionNotice }}</p>
    @endif

    @if ($raidUntil)
        <div class="flex flex-wrap items-center gap-3 rounded-lg border-l-4 border-dc-red bg-dc-800 px-4 py-3">
            <p class="min-w-0 flex-1 basis-56 text-sm text-dc-header">{{ __('protection.raid_active', ['time' => \App\Support\Timezone::format(\Illuminate\Support\Carbon::parse($raidUntil), 'LT')]) }}</p>
            <button type="button" wire:click="endRaid" class="btn px-3 py-1.5 text-dc-red hover:bg-dc-red hover:text-white">{{ __('protection.raid_end') }}</button>
        </div>
    @endif

    @endif

    @if ($section === 'verification')
    <section id="verification" class="card scroll-mt-20">
        <div class="flex items-start gap-4">
            <div class="flex-1">
                <h2 class="text-lg font-semibold text-dc-header">{{ __('protection.verify_title') }}</h2>
                <p class="text-sm text-dc-muted">{{ __('protection.verify_description') }}</p>
            </div>
            <label class="relative mt-1 inline-flex shrink-0 cursor-pointer items-center">
                <input type="checkbox" wire:model="verifyEnabled" class="peer sr-only">
                <span class="h-6 w-10 rounded-full bg-dc-400 transition-colors peer-checked:bg-accent peer-focus-visible:ring-2 peer-focus-visible:ring-accent-fg peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-dc-800"></span>
                <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                <span class="sr-only">{{ __('guild.enable', ['name' => __('protection.verify_title')]) }}</span>
            </label>
        </div>

        <div x-show="$wire.verifyEnabled" x-collapse.duration.200ms class="mt-6 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="verify-role">{{ __('protection.verify_role') }}</label>
                    <select id="verify-role" wire:model="verifyRole" class="input">
                        <option value="">{{ __('protection.choose_role') }}</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role['id'] }}" @disabled(! $role['assignable'])>
                                {{ $role['name'] }}{{ $role['assignable'] ? '' : ' ('.__('guild.above_bot').')' }}
                            </option>
                        @endforeach
                    </select>
                    @error('verifyRole') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="verify-channel">{{ __('protection.verify_channel') }}</label>
                    <select id="verify-channel" wire:model="verifyChannel" class="input">
                        <option value="">{{ __('protection.no_channel') }}</option>
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
                    @error('verifyChannel') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="text-sm text-dc-faint">{{ __('protection.verify_hint') }}</p>
            @php($autoroleName = collect($roles)->firstWhere('id', $autorole)['name'] ?? null)
            <p class="rounded-lg border-l-4 border-accent bg-dc-900 px-3 py-2 text-sm">
                {{ $autoroleName ? __('protection.verify_autorole', ['role' => $autoroleName]) : __('protection.verify_no_autorole') }}
                <a href="{{ route('servers.settings', [$guildId, 'autorole']) }}" class="text-dc-link hover:underline">{{ __('protection.verify_autorole_link') }}</a>
            </p>
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" wire:click="postVerifyMessage" wire:loading.attr="disabled" class="btn-secondary">
                    {{ $verifyMessagePosted ? __('protection.post_update') : __('protection.post_message') }}
                </button>
                <span class="text-xs text-dc-faint">{{ __('protection.post_hint') }}</span>
            </div>
            @unless (config('toxbot.turnstile.site_key') && config('toxbot.turnstile.secret_key'))
                <p class="rounded-lg border-l-4 border-dc-yellow bg-dc-900 px-3 py-2 text-sm">{{ __('protection.turnstile_missing') }}</p>
            @endunless
        </div>
    </section>

    @endif

    @if ($section === 'links')
    <section id="links" class="card scroll-mt-20 space-y-5">
        <div>
            <h2 class="text-lg font-semibold text-dc-header">{{ __('protection.links_title') }}</h2>
            <p class="text-sm text-dc-muted">{{ __('protection.links_description') }}</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <label class="flex items-start gap-3 rounded-lg bg-dc-900 p-3">
                    <input type="checkbox" wire:model="linkInvites" class="mt-1 accent-[var(--color-accent)]">
                    <span>
                        <span class="block text-sm font-medium text-dc-header">{{ __('protection.links_invites') }}</span>
                        <span class="block text-xs text-dc-muted">{{ __('protection.links_invites_hint') }}</span>
                    </span>
                </label>
            <label class="flex items-start gap-3 rounded-lg bg-dc-900 p-3">
                    <input type="checkbox" wire:model="linkScam" class="mt-1 accent-[var(--color-accent)]">
                    <span>
                        <span class="block text-sm font-medium text-dc-header">{{ __('protection.links_scam') }}</span>
                        <span class="block text-xs text-dc-muted">{{ __('protection.links_scam_hint') }}</span>
                    </span>
                </label>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="link-blocklist">{{ __('protection.links_blocklist') }}</label>
                <textarea id="link-blocklist" wire:model="linkBlocklist" rows="4" placeholder="example.com" class="input resize-y font-mono text-sm"></textarea>
                @error('linkBlocklist') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-2 flex items-center gap-2 text-sm font-medium text-dc-header" for="link-allowlist">
                    <input type="checkbox" wire:model="linkAllowlistEnabled" class="accent-[var(--color-accent)]">
                    {{ __('protection.links_allowlist') }}
                </label>
                <textarea id="link-allowlist" wire:model="linkAllowlist" rows="4" placeholder="youtube.com&#10;twitch.tv" class="input resize-y font-mono text-sm"></textarea>
                @error('linkAllowlist') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="text-sm text-dc-faint">{{ __('protection.links_lists_hint') }}</p>

        <div class="max-w-md">
            <label class="label" for="link-action">{{ __('protection.links_action') }}</label>
            <select id="link-action" wire:model="linkAction" class="input">
                @foreach (\App\Models\Guild::LINK_ACTIONS as $action)
                    <option value="{{ $action }}">{{ __('protection.links_action_'.$action) }}</option>
                @endforeach
            </select>
            @error('linkAction') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
        </div>
        <div>
            <h3 class="label">{{ __('protection.links_exempt_roles') }}</h3>
            <div class="flex flex-wrap gap-2">
                @foreach ($roles as $role)
                    <label wire:key="exempt-{{ $role['id'] }}" class="cursor-pointer">
                        <input type="checkbox" wire:model="linkExemptRoles" value="{{ $role['id'] }}" class="peer sr-only">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-dc-900 px-3 py-1 text-sm text-dc-muted ring-1 ring-white/10 transition peer-checked:bg-accent/15 peer-checked:text-dc-header peer-checked:ring-accent peer-focus-visible:ring-accent-fg">
                            <span class="size-2 rounded-full" style="background: {{ $role['color'] ?? '#99aab5' }}"></span>{{ $role['name'] }}
                        </span>
                    </label>
                @endforeach
            </div>
            @error('linkExemptRoles.*') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            <p class="mt-2 text-sm text-dc-faint">{{ __('protection.links_exempt_hint') }}</p>
        </div>

        <div class="rounded-lg bg-dc-900 p-4">
            <h3 class="text-sm font-semibold text-dc-header">{{ __('protection.scam_title') }}</h3>
            <p class="mt-1 text-sm text-dc-muted">
                {{ __('protection.scam_sources') }}
                @foreach (\App\Services\ScamList::SOURCES as $name => $source)
                    <a href="{{ $source['page'] }}" target="_blank" rel="noopener" class="text-dc-link hover:underline">{{ $name }}</a>@if (! $loop->last), @endif
                @endforeach
            </p>
            <form novalidate wire:submit="checkScamDomain" class="mt-3 flex flex-wrap gap-2">
                <input type="text" wire:model="scamCheck" maxlength="200" placeholder="free-nitro.example" class="input min-w-48 flex-1 font-mono text-sm" aria-label="{{ __('protection.scam_check_label') }}">
                <button type="submit" class="btn-secondary" wire:loading.attr="disabled" wire:target="checkScamDomain">{{ __('protection.scam_check_button') }}</button>
            </form>
            @if ($scamCheckResult)
                <p class="mt-2 text-sm text-dc-header" role="status">{{ $scamCheckResult }}</p>
            @endif
        </div>
    </section>

    @endif

    @if ($section === 'raid')
    <section id="antiraid" class="card scroll-mt-20">
        <div class="flex items-start gap-4">
            <div class="flex-1">
                <h2 class="text-lg font-semibold text-dc-header">{{ __('protection.raid_title') }}</h2>
                <p class="text-sm text-dc-muted">{{ __('protection.raid_description') }}</p>
            </div>
            <label class="relative mt-1 inline-flex shrink-0 cursor-pointer items-center">
                <input type="checkbox" wire:model="raidEnabled" class="peer sr-only">
                <span class="h-6 w-10 rounded-full bg-dc-400 transition-colors peer-checked:bg-accent peer-focus-visible:ring-2 peer-focus-visible:ring-accent-fg peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-dc-800"></span>
                <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                <span class="sr-only">{{ __('guild.enable', ['name' => __('protection.raid_title')]) }}</span>
            </label>
        </div>

        <div x-show="$wire.raidEnabled" x-collapse.duration.200ms class="mt-6 space-y-4">
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="label" for="raid-joins">{{ __('protection.raid_joins') }}</label>
                    <input id="raid-joins" type="number" min="3" max="100" wire:model="raidJoins" class="input">
                    @error('raidJoins') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="raid-seconds">{{ __('protection.raid_seconds') }}</label>
                    <input id="raid-seconds" type="number" min="5" max="300" wire:model="raidSeconds" class="input">
                    @error('raidSeconds') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="raid-minutes">{{ __('protection.raid_minutes') }}</label>
                    <input id="raid-minutes" type="number" min="1" max="1440" wire:model="raidMinutes" class="input">
                    @error('raidMinutes') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="flex items-start gap-3 rounded-lg bg-dc-900 p-3">
                    <input type="checkbox" wire:model="raidLevel" class="mt-1 accent-[var(--color-accent)]">
                    <span>
                        <span class="block text-sm font-medium text-dc-header">{{ __('protection.raid_level') }}</span>
                        <span class="block text-xs text-dc-muted">{{ __('protection.raid_level_hint') }}</span>
                    </span>
                </label>
                <label class="flex items-start gap-3 rounded-lg bg-dc-900 p-3">
                    <input type="checkbox" wire:model="raidKick" class="mt-1 accent-[var(--color-accent)]">
                    <span>
                        <span class="block text-sm font-medium text-dc-header">{{ __('protection.raid_kick') }}</span>
                        <span class="block text-xs text-dc-muted">{{ __('protection.raid_kick_hint') }}</span>
                    </span>
                </label>
            </div>
            <p class="text-sm text-dc-faint">{{ __('protection.raid_hint') }}</p>
        </div>

        <div class="mt-6 max-w-md border-t border-white/5 pt-5">
            <label class="label" for="min-age">{{ __('protection.min_age') }}</label>
            <input id="min-age" type="number" min="0" max="365" wire:model="minAccountAge" class="input">
            @error('minAccountAge') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            <p class="mt-2 text-sm text-dc-faint">{{ __('protection.min_age_hint') }}</p>
        </div>
    </section>
    @endif

    @if ($section === 'tickets')
    <livewire:auto-setup :guild-id="$guildId" :guild-name="$guildName" :areas="['tickets']" :compact="true" :key="'auto-setup-tickets'" />

    @if ($ticketNotice)
        <p class="rounded-lg border-l-4 border-accent bg-dc-800 px-4 py-3 text-sm" role="status">{{ $ticketNotice }}</p>
    @endif

    <section id="tickets" class="card scroll-mt-20 space-y-5">
        <div>
            <h2 class="text-lg font-semibold text-dc-header">{{ __('tickets.title') }}</h2>
            <p class="text-sm text-dc-muted">{{ __('tickets.description') }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="ticket-category">{{ __('tickets.category') }}</label>
                <select id="ticket-category" wire:model="ticketCategory" class="input">
                    <option value="">{{ __('tickets.choose_category') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
                    @endforeach
                </select>
                @if ($categories === [] && ! $loadError)
                    <p class="mt-2 text-sm text-dc-faint">{{ __('tickets.no_categories') }}</p>
                @endif
                @error('ticketCategory') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="ticket-log">{{ __('tickets.log_channel') }}</label>
                <select id="ticket-log" wire:model="ticketLogChannel" class="input">
                    <option value="">{{ __('protection.no_channel') }}</option>
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
                @error('ticketLogChannel') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="ticket-panel">{{ __('tickets.panel_channel') }}</label>
                <select id="ticket-panel" wire:model="ticketPanelChannel" class="input">
                    <option value="">{{ __('protection.no_channel') }}</option>
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
                @error('ticketPanelChannel') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="ticket-limit">{{ __('tickets.limit') }}</label>
                <input id="ticket-limit" type="number" min="1" max="10" wire:model="ticketLimit" class="input">
                @error('ticketLimit') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>
        </div>
        <p class="text-sm text-dc-faint">{{ __('tickets.hint') }}</p>
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" wire:click="postTicketPanel" wire:loading.attr="disabled" class="btn-secondary">
                {{ $ticketPanelPosted ? __('protection.post_update') : __('tickets.panel_post') }}
            </button>
            <span class="text-xs text-dc-faint">{{ __('tickets.panel_hint') }}</span>
        </div>
    </section>

    <section class="card space-y-4">
        <div>
            <h2 class="text-lg font-semibold text-dc-header">{{ __('tickets.types_title') }}</h2>
            <p class="text-sm text-dc-muted">{{ __('tickets.types_description') }}</p>
        </div>
        <livewire:ticket-types :guild-id="$guildId" :roles="$roles" />
    </section>
    @endif

    <div class="flex flex-wrap items-center gap-3 border-t border-white/[0.06] pt-5">
        <p class="min-w-0 flex-1 basis-64 text-sm text-dc-faint">{{ __('data.section_text', ['section' => __('menu.section_'.$section)]) }}@if (__('data.section_extra_'.$section) !== 'data.section_extra_'.$section) {{ __('data.section_extra_'.$section) }}@endif</p>
        <button type="button" wire:click="resetSection" data-confirm="{{ __('data.section_confirm', ['section' => __('menu.section_'.$section)]) }}"
                class="btn text-dc-red ring-1 ring-dc-red/40 hover:bg-dc-red hover:text-white max-sm:w-full">{{ __('data.section_button') }}</button>
    </div>

    <x-unsaved-bar />

    @if ($errors->any())
        <div class="fixed inset-x-0 bottom-24 z-30 px-4 md:left-70">
            <div class="mx-auto max-w-3xl rounded-lg bg-dc-red px-4 py-2 text-sm font-medium text-white shadow-lg">
                {{ __('common.not_saved') }}
            </div>
        </div>
    @endif
</div>
