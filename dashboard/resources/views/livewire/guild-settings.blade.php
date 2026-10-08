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

    <x-unsaved-bar />

    @if ($errors->any())
        <div class="fixed inset-x-0 bottom-24 z-30 px-4 md:left-70">
            <div class="mx-auto max-w-3xl rounded-lg bg-dc-red px-4 py-2 text-sm font-medium text-white shadow-lg">
                {{ __('common.not_saved') }}
            </div>
        </div>
    @endif
</div>
