<div wire:poll.60s class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 class="label mb-0">{{ __('streams.streamers') }}</h3>
        <span class="text-sm text-dc-muted tabular-nums">{{ __('streams.count', ['count' => $subscriptions->count(), 'limit' => $limit]) }}</span>
    </div>

    @if (! $twitchReady || ! $youtubeReady)
        <p class="rounded-lg border-l-4 border-dc-yellow bg-dc-900 px-3 py-2 text-sm">{{ __('streams.not_configured') }}</p>
    @endif

    <form wire:submit="add" class="flex flex-wrap gap-2">
        <select wire:model.live="platform" class="input w-auto" aria-label="{{ __('streams.platform') }}">
            <option value="twitch">Twitch</option>
            <option value="youtube">YouTube</option>
        </select>
        <input type="text" wire:model="input" maxlength="200" class="input min-w-48 flex-1"
               placeholder="{{ $platform === 'youtube' ? ($youtubeHandles ? __('streams.youtube_placeholder') : __('streams.youtube_id_placeholder')) : __('streams.twitch_placeholder') }}">
        <button type="submit" wire:loading.attr="disabled" wire:target="add" class="btn-primary" @disabled($subscriptions->count() >= $limit)>
            {{ __('streams.add') }}
        </button>
    </form>
    @if ($platform === 'youtube' && ! $youtubeHandles)
        <p class="text-sm text-dc-muted">{{ __('streams.youtube_no_key') }}</p>
    @endif
    @error('input') <p class="text-sm text-dc-red">{{ $message }}</p> @enderror
    @if ($error) <p class="text-sm text-dc-red" role="alert">{{ $error }}</p> @endif

    @if ($subscriptions->isEmpty())
        <p class="text-sm text-dc-faint">{{ __('streams.empty') }}</p>
    @else
        <ul class="divide-y divide-black/25 rounded-lg bg-dc-900">
            @foreach ($subscriptions as $subscription)
                @php($source = $subscription->source)
                <li wire:key="stream-{{ $subscription->id }}" class="p-3">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                        <div class="flex min-w-0 flex-1 basis-56 items-center gap-3">
                            @if ($source->avatar_url)
                                <img src="{{ $source->avatar_url }}" alt="" class="size-8 shrink-0 rounded-full">
                            @else
                                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-dc-600 text-xs font-semibold">{{ mb_substr($source->name, 0, 1) }}</span>
                            @endif
                            <div class="min-w-0">
                                <a href="{{ $source->url() }}" target="_blank" rel="noopener" class="block truncate font-medium text-dc-header hover:underline">{{ $source->name }}</a>
                                <div class="text-xs text-dc-muted">
                                    {{ $source->platform === 'twitch' ? 'Twitch' : 'YouTube' }}
                                    @if ($source->live_item) · <span class="font-semibold text-dc-red">LIVE</span> @endif
                                    @if ($activeIds !== null && ! in_array((int) $subscription->id, $activeIds, true)) · <span class="font-semibold text-dc-yellow" title="{{ __('streams.paused_hint', ['limit' => config('toxbot.streams.free_limit')]) }}">{{ __('streams.paused') }}</span> @endif
                                </div>
                            </div>
                        </div>
                        <div class="ml-auto flex shrink-0 gap-2">
                            <button type="button" wire:click="edit({{ $subscription->id }})" class="btn-secondary px-3 py-1.5">{{ __('streams.settings') }}</button>
                            <button type="button" wire:click="remove({{ $subscription->id }})" wire:confirm="{{ __('streams.confirm_remove', ['name' => $source->name]) }}"
                                    class="btn px-3 py-1.5 text-dc-red hover:bg-dc-red hover:text-white">{{ __('streams.remove') }}</button>
                        </div>
                    </div>

                    @if ($editingId === $subscription->id)
                        <form wire:submit="saveSubscription" class="mt-4 space-y-4 rounded-lg bg-dc-800 p-4">
                            @if ($source->platform === 'youtube')
                                <div class="flex flex-wrap gap-4 text-sm">
                                    <label class="flex items-center gap-2"><input type="checkbox" wire:model="notifyVideos" class="accent-[var(--color-accent)]"> {{ __('streams.notify_videos') }}</label>
                                    <label class="flex items-center gap-2"><input type="checkbox" wire:model="notifyLive" class="accent-[var(--color-accent)]"> {{ __('streams.notify_live') }}</label>
                                </div>
                            @endif

                            <fieldset @class(['space-y-4', 'opacity-60' => ! $premium]) @disabled(! $premium)>
                                <legend class="mb-2 text-sm font-medium text-dc-header">
                                    {{ __('streams.premium_title') }}
                                    <span class="ml-1 rounded-full bg-dc-yellow px-2 py-0.5 text-xs font-semibold text-dc-950">Premium</span>
                                </legend>
                                @unless ($premium)
                                    <p class="text-sm text-dc-muted">{{ __('streams.premium_locked') }}</p>
                                @endunless

                                <div @class(['grid gap-4', 'sm:grid-cols-2' => $source->platform === 'youtube'])>
                                    <div>
                                        <label class="label" for="stream-msg-{{ $subscription->id }}">{{ __('streams.own_message') }}</label>
                                        <textarea id="stream-msg-{{ $subscription->id }}" wire:model="customMessage" rows="2" placeholder="{{ __('streams.own_message_hint') }}" class="input resize-y font-mono text-sm"></textarea>
                                        @error('customMessage') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
                                    </div>
                                    @if ($source->platform === 'youtube')
                                        <div>
                                            <label class="label" for="stream-video-msg-{{ $subscription->id }}">{{ __('streams.own_video_message') }}</label>
                                            <textarea id="stream-video-msg-{{ $subscription->id }}" wire:model="videoMessage" rows="2" placeholder="{{ __('streams.own_message_hint') }}" class="input resize-y font-mono text-sm"></textarea>
                                            @error('videoMessage') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
                                        </div>
                                    @endif
                                </div>

                                <div>
                                    <label class="label" for="stream-role-{{ $subscription->id }}">{{ __('streams.own_role') }}</label>
                                    <select id="stream-role-{{ $subscription->id }}" wire:model="pingRole" class="input max-w-sm">
                                        <option value="">{{ __('streams.own_role_default') }}</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role['id'] }}">@ {{ $role['name'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('pingRole') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    @if ($source->platform === 'twitch')
                                        <div>
                                            <label class="label" for="stream-games-{{ $subscription->id }}">{{ __('streams.filter_games') }}</label>
                                            <input id="stream-games-{{ $subscription->id }}" type="text" wire:model="filterGames" placeholder="Valheim, Palworld" class="input">
                                        </div>
                                    @endif
                                    <div>
                                        <label class="label" for="stream-title-{{ $subscription->id }}">{{ __('streams.filter_title') }}</label>
                                        <input id="stream-title-{{ $subscription->id }}" type="text" wire:model="filterTitle" class="input">
                                    </div>
                                </div>

                                @if ($source->platform === 'twitch')
                                    <div>
                                        <label class="label" for="stream-end-{{ $subscription->id }}">{{ __('streams.end_action') }}</label>
                                        <select id="stream-end-{{ $subscription->id }}" wire:model="endAction" class="input max-w-sm">
                                            <option value="keep">{{ __('streams.end_keep') }}</option>
                                            <option value="edit">{{ __('streams.end_edit') }}</option>
                                            <option value="delete">{{ __('streams.end_delete') }}</option>
                                        </select>
                                    </div>
                                @endif
                            </fieldset>

                            <div class="flex justify-end gap-2">
                                <button type="button" wire:click="cancel" class="btn-ghost">{{ __('tags.cancel') }}</button>
                                <button type="submit" class="btn-primary">{{ __('admin.save') }}</button>
                            </div>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
