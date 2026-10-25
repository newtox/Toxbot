<section x-data="{ open: false }" class="card space-y-5 shadow-[inset_0_2px_0_var(--color-dc-red)]">
    <div class="flex flex-wrap items-start gap-4">
        <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-dc-red/15 text-dc-red">
            <x-icon name="alert" />
        </span>
        <div class="min-w-0 flex-1 basis-60">
            <h2 class="text-lg font-semibold text-dc-header">{{ __('data.server_title') }}</h2>
            <p class="text-sm text-dc-muted">{{ __('data.server_description') }}</p>
        </div>
        <button type="button" x-on:click="open = !open" class="btn-secondary max-sm:w-full" :aria-expanded="open">
            <span x-show="!open">{{ __('data.show') }}</span>
            <span x-show="open" x-cloak>{{ __('setup.close') }}</span>
        </button>
    </div>

    <div x-show="open" x-cloak x-collapse.duration.200ms class="space-y-4">
        <div class="flex flex-wrap items-center gap-4 rounded-lg bg-dc-900 p-4">
            <div class="min-w-0 flex-1 basis-64">
                <h3 class="font-medium text-dc-header">{{ __('data.reset_title') }}</h3>
                <p class="text-sm text-dc-muted">{{ __('data.reset_text') }}</p>
            </div>
            <button type="button" wire:click="resetSettings" data-confirm="{{ __('data.reset_confirm') }}" wire:loading.attr="disabled"
                    class="btn text-dc-red ring-1 ring-dc-red/40 hover:bg-dc-red hover:text-white max-sm:w-full">{{ __('data.reset_button') }}</button>
        </div>

        <div class="space-y-3 rounded-lg bg-dc-900 p-4">
            <div>
                <h3 class="font-medium text-dc-header">{{ __('data.delete_title') }}</h3>
                <p class="text-sm text-dc-muted">{{ __('data.delete_text') }}</p>
            </div>
            @if ($owner)
                <form novalidate wire:submit="deleteServer" class="space-y-3">
                    <div>
                        <label class="label" for="confirm-server">{{ __('data.confirm_label', ['name' => $guildName]) }}</label>
                        <input id="confirm-server" type="text" wire:model="confirmName" autocomplete="off" class="input max-w-md" placeholder="{{ $guildName }}">
                        @error('confirmName') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" wire:loading.attr="disabled" class="btn bg-dc-red text-white hover:brightness-110 max-sm:w-full">{{ __('data.delete_button') }}</button>
                </form>
            @else
                <p class="text-sm text-dc-faint">{{ __('data.owner_only') }}</p>
            @endif
        </div>
    </div>
</section>
