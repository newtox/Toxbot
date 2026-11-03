<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]"
     x-data="{ avatar: toxbot.imageCropper(1), banner: toxbot.imageCropper(680 / 240) }"
     x-on:bot-profile-saved.window="avatar.clear(); banner.clear(); $refs.avatarInput.value = ''; $refs.bannerInput.value = ''">
    <form novalidate x-on:submit.prevent="$wire.avatarCrop = avatar.crop(); $wire.bannerCrop = banner.crop(); $wire.save()" class="order-2 space-y-5 lg:order-1">
        <fieldset @class(['space-y-5', 'opacity-60' => ! $premium]) @disabled(! $premium)>
            @unless ($premium)
                <p class="rounded-lg bg-dc-900 px-4 py-3 text-sm text-dc-muted">{{ __('botprofile.locked') }}</p>
            @endunless

            @foreach (['avatar', 'banner'] as $key)
                <div>
                    <label class="label" for="bot-{{ $key }}">{{ __('botprofile.'.$key) }}</label>
                    <div class="flex flex-wrap items-center gap-3">
                        <input id="bot-{{ $key }}" type="file" x-ref="{{ $key }}Input" wire:model="{{ $key }}" x-on:change="{{ $key }}.pick($event)"
                               accept="image/png,image/jpeg,image/webp,image/gif"
                               class="block min-w-0 flex-1 basis-64 text-sm text-dc-muted file:mr-3 file:rounded-md file:border-0 file:bg-dc-600 file:px-3 file:py-2 file:text-dc-header hover:file:bg-dc-500">
                        @if ($current[$key])
                            <button type="button" wire:click="remove('{{ $key }}')" class="btn px-3 py-1.5 text-dc-red hover:bg-dc-red hover:text-white">{{ __('botprofile.remove_'.$key) }}</button>
                        @endif
                    </div>
                    <p class="mt-2 text-xs text-dc-faint">{{ __('botprofile.'.$key.'_hint') }}</p>

                    <div x-show="{{ $key }}.src" x-cloak class="mt-3 space-y-3 rounded-lg bg-dc-900 p-4">
                        <div @class(['cursor-move touch-none select-none bg-dc-950 bg-no-repeat', 'mx-auto size-48 rounded-full' => $key === 'avatar', 'aspect-[17/6] w-full rounded-lg' => $key === 'banner'])
                             :style="{{ $key }}.style()"
                             x-on:pointerdown="{{ $key }}.start($event)" x-on:pointermove="{{ $key }}.move($event)"
                             x-on:pointerup="{{ $key }}.stop()" x-on:pointercancel="{{ $key }}.stop()"
                             x-on:wheel.prevent="{{ $key }}.wheel($event)"></div>
                        <div class="flex items-center gap-3">
                            <label class="text-xs text-dc-muted" for="bot-{{ $key }}-zoom">{{ __('botprofile.zoom') }}</label>
                            <input id="bot-{{ $key }}-zoom" type="range" min="1" max="5" step="0.01" :value="{{ $key }}.zoom"
                                   x-on:input="{{ $key }}.setZoom($event.target.value)" class="flex-1 accent-[var(--color-accent)]">
                        </div>
                        <p class="text-xs text-dc-faint">{{ __('botprofile.crop_hint') }}</p>
                    </div>

                    <p wire:loading wire:target="{{ $key }}" class="mt-2 text-xs text-dc-muted">{{ __('botprofile.uploading') }}</p>
                    @error($key) <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
                </div>
            @endforeach

            <div>
                <div class="mb-2 flex items-end justify-between">
                    <label class="label mb-0" for="bot-bio">{{ __('botprofile.bio') }}</label>
                    <span class="text-xs tabular-nums"
                          :class="($wire.bio ?? '').length > {{ $bioMax }} ? 'text-dc-red' : 'text-dc-faint'"
                          x-text="($wire.bio ?? '').length + ' / {{ $bioMax }}'"></span>
                </div>
                <textarea id="bot-bio" wire:model="bio" rows="3" maxlength="{{ $bioMax }}" placeholder="{{ __('botprofile.bio_placeholder') }}" class="input resize-y"></textarea>
                @error('bio') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>

            @error('profile') <p class="rounded-lg border-l-4 border-dc-red bg-dc-900 px-4 py-3 text-sm">{{ $message }}</p> @enderror

            <div class="flex flex-wrap items-center justify-end gap-3">
                <p class="min-w-0 flex-1 basis-56 text-xs text-dc-faint">{{ __('botprofile.rate_hint') }}</p>
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save,avatar,banner">{{ __('admin.save') }}</button>
            </div>
        </fieldset>
    </form>

    <div class="order-1 w-full max-w-xs lg:order-2">
        <p class="label">{{ __('botprofile.preview') }}</p>
        <div class="overflow-hidden rounded-xl bg-dc-950 shadow-lg">
            <div class="relative aspect-[17/6] bg-accent bg-cover bg-center" @if ($current['banner']) style="background-image: url('{{ $current['banner'] }}')" @endif>
                <div x-show="banner.src" x-cloak class="absolute inset-0 bg-no-repeat" :style="banner.style()"></div>
            </div>
            <div class="relative px-4 pb-4">
                <div class="relative -mt-10 size-20">
                    <div class="relative size-full overflow-hidden rounded-full border-[6px] border-dc-950 bg-dc-700">
                        @if ($current['avatar'] ?? $current['default_avatar'])
                            <img src="{{ $current['avatar'] ?? $current['default_avatar'] }}" alt="" class="size-full object-cover">
                        @endif
                        <div x-show="avatar.src" x-cloak class="absolute inset-0 bg-no-repeat" :style="avatar.style()"></div>
                    </div>
                    <span class="absolute right-0.5 bottom-0.5 size-5 rounded-full border-4 border-dc-950 bg-[#23a55a]"></span>
                </div>
                <div class="mt-2 flex items-center gap-2">
                    <span class="truncate text-lg font-bold text-dc-header">{{ $current['name'] }}</span>
                    <x-app-tag :verified="$current['verified']" />
                </div>
                <p class="truncate text-sm text-dc-text">{{ $current['tag'] }}</p>
                <p class="mt-3 whitespace-pre-line break-words text-sm text-dc-text" x-text="$wire.bio || @js(__('botprofile.bio_empty'))"></p>
            </div>
        </div>
    </div>
</div>
