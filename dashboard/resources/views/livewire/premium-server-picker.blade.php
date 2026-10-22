<section class="card">
    <h2 class="flex items-center gap-2 text-lg font-semibold text-dc-header">
        {{ __('profile.premium_server_title') }}
        <span class="rounded-full bg-dc-yellow px-2 py-0.5 text-xs font-semibold text-dc-950">Premium</span>
    </h2>
    <p class="mb-5 text-sm text-dc-muted">{{ __('profile.premium_server_description') }}</p>

    @if ($premium)
        <form wire:submit="save" class="flex flex-wrap items-center gap-2">
            <select wire:model="guildId" class="input max-w-sm" aria-label="{{ __('profile.premium_server_title') }}">
                <option value="">{{ __('profile.premium_server_none') }}</option>
                @foreach ($guilds as $guild)
                    <option value="{{ $guild['id'] }}">{{ $guild['name'] }}</option>
                @endforeach
            </select>
            <button type="submit" wire:loading.attr="disabled" class="btn-primary">{{ __('admin.save') }}</button>
        </form>
        @error('guildId') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
    @else
        <p class="rounded-lg bg-dc-900 px-3 py-2 text-sm text-dc-muted">{{ __('profile.premium_server_locked') }}</p>
    @endif
</section>
