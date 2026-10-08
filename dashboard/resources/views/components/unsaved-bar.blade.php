<div wire:dirty class="fixed inset-x-0 bottom-4 z-[25] px-4 md:left-70">
    <div class="mx-auto flex max-w-3xl flex-wrap items-center gap-x-3 gap-y-2 rounded-xl bg-dc-950 px-4 py-3 shadow-2xl ring-1 ring-black/40">
        <span class="font-medium text-dc-header">{{ __('common.unsaved') }}</span>
        <div class="ml-auto flex gap-2">
            <button type="button" wire:click="discard" class="btn-ghost">{{ __('common.reset') }}</button>
            <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="btn-primary">
                <span wire:loading.remove wire:target="save">{{ __('common.save') }}</span>
                <span wire:loading wire:target="save">{{ __('common.saving') }}</span>
            </button>
        </div>
    </div>
</div>
