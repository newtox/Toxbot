<div class="space-y-4">
    <div class="card flex flex-wrap items-center gap-4">
        <div class="min-w-0 flex-1 basis-64">
            <h2 class="text-lg font-semibold text-dc-header">{{ __('data.reset_title') }}</h2>
            <p class="text-sm text-dc-muted">{{ __('data.reset_text') }}</p>
        </div>
        <button type="button" wire:click="resetSettings" data-confirm="{{ __('data.reset_confirm') }}" wire:loading.attr="disabled"
                class="btn text-dc-red ring-1 ring-dc-red/40 hover:bg-dc-red hover:text-white max-sm:w-full">{{ __('data.reset_button') }}</button>
    </div>

    <div class="card space-y-4 shadow-[inset_0_2px_0_var(--color-dc-red)]">
        <div>
            <h2 class="text-lg font-semibold text-dc-header">{{ __('data.delete_title') }}</h2>
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
