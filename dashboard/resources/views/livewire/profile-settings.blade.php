<div class="space-y-6 pb-32">
    <div class="flex items-center gap-4">
        <img src="{{ $user->avatar_url }}" alt="" class="size-14 rounded-full">
        <div>
            <h1 class="text-2xl font-bold text-dc-header">{{ $user->display_name }}</h1>
            <p class="text-sm text-dc-muted">{{ __('profile.intro') }}</p>
        </div>
    </div>

    <section class="card">
        <h2 class="text-lg font-semibold text-dc-header">{{ __('profile.language_title') }}</h2>
        <p class="mb-5 text-sm text-dc-muted">{{ __('profile.language_description') }}</p>

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-5">
            @foreach ($languages as $code => $name)
                <label class="cursor-pointer">
                    <input type="radio" wire:model="language" value="{{ $code }}" class="peer sr-only">
                    <span class="block rounded-md bg-dc-900 px-3 py-2.5 text-center text-sm font-medium ring-2 ring-transparent transition peer-checked:bg-accent/15 peer-checked:text-dc-header peer-checked:ring-accent peer-focus-visible:ring-accent-fg/60 hover:bg-dc-600">
                        {{ $name }}
                    </span>
                </label>
            @endforeach
        </div>
        @error('language') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
    </section>

    <section class="card"
             x-data="{
                presets: ['#6dbe33', '#7289da', '#5865f2', '#57f287', '#fee75c', '#eb459e', '#ed4245', '#f47b67', '#1abc9c', '#9b59b6', '#ffffff'],
                long(hex) {
                    return /^#[0-9a-f]{3}$/i.test(hex) ? '#' + [...hex.slice(1)].map(c => c + c).join('') : hex;
                },
                valid(hex) { return /^#([0-9a-f]{6}|[0-9a-f]{3})$/i.test(hex ?? '') },
             }"
             x-effect="valid($wire.color) && toxbot.setAccent(long($wire.color))">
        <h2 class="text-lg font-semibold text-dc-header">{{ __('profile.color_title') }}</h2>
        <p class="mb-5 text-sm text-dc-muted">
            {{ __('profile.color_description') }}
            {{ __('profile.color_accent') }}
        </p>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="space-y-4">
                <div class="flex flex-wrap gap-2">
                    <template x-for="preset in presets" :key="preset">
                        <button type="button" x-on:click="$wire.color = preset"
                                class="size-9 rounded-md ring-2 ring-offset-2 ring-offset-dc-800 transition hover:scale-110"
                                :class="long($wire.color)?.toLowerCase() === preset ? 'ring-white' : 'ring-transparent'"
                                :style="{ backgroundColor: preset }" :title="preset"></button>
                    </template>
                </div>

                <div class="flex items-center gap-3">
                    <input type="color" :value="valid($wire.color) ? long($wire.color) : '#7289da'"
                           x-on:input="$wire.color = $event.target.value"
                           class="size-11 shrink-0 cursor-pointer rounded-md border-0 bg-transparent p-0">
                    <input type="text" wire:model="color" maxlength="7" spellcheck="false"
                           class="input max-w-36 font-mono uppercase">
                </div>
                @error('color') <p class="text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-md bg-dc-700 p-4 ring-1 ring-black/30">
                <div class="flex max-w-md overflow-hidden rounded bg-dc-800">
                    <div class="w-1 shrink-0" :style="{ backgroundColor: valid($wire.color) ? long($wire.color) : '#7289da' }"></div>
                    <div class="p-3">
                        <div class="text-sm font-semibold text-dc-header">{{ __('profile.embed_title') }}</div>
                        <div class="mt-1 text-sm text-dc-text">{{ __('profile.embed_text') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <x-unsaved-bar />
</div>
