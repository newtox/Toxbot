<section class="card space-y-4">
    <div>
        <h2 class="text-lg font-semibold text-dc-header">{{ __('admin.blacklist_title') }}</h2>
        <p class="text-sm text-dc-muted">{{ __('admin.blacklist_description') }}</p>
    </div>

    @if ($notice)
        <p class="rounded-lg border-l-4 border-accent bg-dc-900 px-4 py-3 text-sm" role="status">{{ $notice }}</p>
    @endif

    <form novalidate wire:submit="add" class="grid gap-3 rounded-lg bg-dc-900 p-4 sm:grid-cols-[9rem_1fr]">
        <div>
            <label class="label" for="blacklist-type">{{ __('admin.blacklist_type') }}</label>
            <select id="blacklist-type" wire:model="type" class="input">
                <option value="user">{{ __('admin.blacklist_user') }}</option>
                <option value="guild">{{ __('admin.blacklist_guild') }}</option>
            </select>
        </div>
        <div class="min-w-0">
            <label class="label" for="blacklist-target">{{ __('admin.blacklist_id') }}</label>
            <input id="blacklist-target" type="text" inputmode="numeric" wire:model="target" placeholder="123456789012345678" class="input font-mono">
            @error('target') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
        </div>
        <div class="min-w-0 sm:col-span-2">
            <label class="label" for="blacklist-reason">{{ __('admin.reason') }}</label>
            <input id="blacklist-reason" type="text" wire:model="reason" maxlength="200" class="input">
        </div>
        <div class="flex flex-wrap items-center justify-end gap-3 sm:col-span-2">
            <p class="mr-auto text-sm text-dc-faint">{{ __('admin.blacklist_hint') }}</p>
            <button type="submit" class="btn bg-dc-red text-white hover:brightness-110 max-sm:w-full">{{ __('admin.blacklist_add') }}</button>
        </div>
    </form>

    @if ($entries->isEmpty())
        <p class="text-sm text-dc-faint">{{ __('admin.blacklist_empty') }}</p>
    @else
        <ul class="divide-y divide-white/5 rounded-lg bg-dc-900">
            @foreach ($entries as $entry)
                <li wire:key="blacklist-{{ $entry->id }}" class="flex flex-wrap items-center gap-x-3 gap-y-1 px-3 py-2.5">
                    <span class="rounded-full bg-dc-700 px-2 py-0.5 text-xs font-semibold text-dc-muted">{{ $entry->guild ? __('admin.blacklist_guild') : __('admin.blacklist_user') }}</span>
                    <span class="font-mono text-sm text-dc-header">{{ $entry->guild ?? $entry->user }}</span>
                    @if ($entry->reason)<span class="min-w-0 truncate text-sm text-dc-muted">{{ $entry->reason }}</span>@endif
                    <span class="ml-auto text-xs text-dc-faint">{{ $entry->created_at?->diffForHumans() }}</span>
                    <button type="button" wire:click="remove({{ $entry->id }})" class="btn px-3 py-1.5 text-dc-red hover:bg-dc-red hover:text-white">{{ __('admin.blacklist_remove') }}</button>
                </li>
            @endforeach
        </ul>
    @endif
</section>
