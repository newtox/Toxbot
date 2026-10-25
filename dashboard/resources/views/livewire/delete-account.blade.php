<section class="card space-y-5 shadow-[inset_0_2px_0_var(--color-dc-red)]">
    <h2 class="text-lg font-semibold text-dc-header">{{ __('data.account_title') }}</h2>

    <form novalidate wire:submit="delete" class="space-y-4">
        <ul class="list-disc space-y-1 pl-5 text-sm text-dc-muted">
            <li>{{ __('data.account_deletes') }}</li>
            <li>{{ __('data.account_anonymized') }}</li>
            <li>{{ __('data.account_keeps') }}</li>
        </ul>
        <div>
            <label class="label" for="confirm-account">{{ __('data.confirm_label', ['name' => auth()->user()->username]) }}</label>
            <input id="confirm-account" type="text" wire:model="confirmName" autocomplete="off" class="input max-w-md" placeholder="{{ auth()->user()->username }}">
            @error('confirmName') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
        </div>
        <button type="submit" wire:loading.attr="disabled" class="btn bg-dc-red text-white hover:brightness-110 max-sm:w-full">{{ __('data.account_button') }}</button>
    </form>
</section>
