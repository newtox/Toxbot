@php
    $enabled = $type.'Enabled';
    $channel = $type.'Channel';
    $msg = $type.'Msg';
    $max = \App\Models\Guild::MAX_MESSAGE_LENGTH;
@endphp

<section id="{{ $type }}" class="card scroll-mt-20">
    <div class="flex items-start gap-4">
        <div class="flex-1">
            <h2 class="text-lg font-semibold text-dc-header">{{ $title }}</h2>
            <p class="text-sm text-dc-muted">{{ $description }}</p>
        </div>

        <label class="relative mt-1 inline-flex cursor-pointer items-center">
            <input type="checkbox" wire:model="{{ $enabled }}" class="peer sr-only">
            <span class="h-6 w-10 rounded-full bg-dc-400 transition-colors peer-checked:bg-accent peer-focus-visible:ring-2 peer-focus-visible:ring-accent-fg peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-dc-800"></span>
            <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
            <span class="sr-only">{{ __('guild.enable', ['name' => $title]) }}</span>
        </label>
    </div>

    <div x-show="$wire.{{ $enabled }}" x-collapse.duration.200ms class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="space-y-5">
            <div>
                <label class="label" for="{{ $channel }}">{{ __('guild.channel') }}</label>
                <select id="{{ $channel }}" wire:model="{{ $channel }}" class="input">
                    <option value="">{{ __('guild.choose_channel') }}</option>
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
                @error($channel) <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>

            <div>
                <div class="mb-2 flex items-end justify-between">
                    <label class="label mb-0" for="{{ $msg }}">{{ __('guild.message') }}</label>
                    <span class="text-xs tabular-nums"
                          :class="($wire.{{ $msg }} ?? '').length > {{ $max }} ? 'text-dc-red' : 'text-dc-faint'"
                          x-text="($wire.{{ $msg }} ?? '').length + ' / {{ $max }}'"></span>
                </div>
                <textarea id="{{ $msg }}" x-ref="{{ $msg }}" wire:model="{{ $msg }}" rows="5"
                          placeholder="{{ $example }}" class="input resize-y font-mono text-sm leading-relaxed"></textarea>
                @error($msg) <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror

                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach (\App\Models\Guild::PLACEHOLDERS as $token => $hint)
                        <button type="button" title="{{ __($hint) }}"
                                x-on:click="toxbot.insertAtCursor($refs.{{ $msg }}, '{{ $token }}')"
                                class="rounded-full bg-dc-600 px-3 py-1 font-mono text-xs text-dc-text transition hover:bg-accent hover:text-on-accent">
                            {{ $token }}
                        </button>
                    @endforeach
                    <button type="button" x-show="!$wire.{{ $msg }}"
                            x-on:click="$refs.{{ $msg }}.value = @js($example); $refs.{{ $msg }}.dispatchEvent(new Event('input', { bubbles: true }))"
                            class="rounded-full px-3 py-1 text-xs text-accent-fg hover:underline">
                        {{ __('guild.insert_example') }}
                    </button>
                </div>
            </div>
        </div>

        <div>
            <span class="label">{{ __('guild.preview') }}</span>
            <div class="overflow-hidden rounded-lg bg-dc-700 ring-1 ring-black/30">
                <div class="flex items-center gap-1.5 border-b border-black/25 px-4 py-2.5 text-sm font-semibold text-dc-header">
                    <span class="text-lg leading-none text-dc-muted">#</span>
                    <span x-text="ctx.channels[$wire.{{ $channel }}] ?? @js(__('guild.no_channel'))"></span>
                </div>
                <div class="flex gap-4 px-4 py-4">
                    <img :src="ctx.botAvatar" alt="" class="size-10 shrink-0 rounded-full">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-2">
                            <span class="font-medium text-dc-header" x-text="ctx.botName"></span>
                            <span class="rounded-[3px] bg-blurple px-1 text-[10px] leading-4 font-semibold text-white">APP</span>
                            <span class="text-xs text-dc-muted" x-text="toxbot.discordTimestamp(ctx.todayAt)"></span>
                        </div>
                        <div x-show="$wire.{{ $msg }}" class="break-words whitespace-pre-wrap text-dc-text"
                             x-html="toxbot.renderDiscordMessage($wire.{{ $msg }}, ctx)"></div>
                        <div x-show="!$wire.{{ $msg }}" class="text-dc-faint italic">{{ __('guild.no_message') }}</div>
                    </div>
                </div>
            </div>
            <p class="mt-2 text-xs text-dc-faint">{{ __('guild.preview_hint') }}</p>
        </div>
    </div>
</section>
