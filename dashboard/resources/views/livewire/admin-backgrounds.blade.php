<section class="card space-y-5">
    <div>
        <h2 class="text-lg font-semibold text-dc-header">{{ __('admin.backgrounds_title') }}</h2>
        <p class="text-sm text-dc-muted">{{ __('admin.backgrounds_description') }}</p>
    </div>

    <form novalidate wire:submit="add" class="grid gap-4 rounded-lg bg-dc-900 p-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label class="label" for="bg-type">{{ __('admin.item_type') }}</label>
            <select id="bg-type" wire:model.live="newType" class="input max-w-xs">
                @foreach (\App\Models\ShopItem::TYPES as $type)
                    <option value="{{ $type }}">{{ __('shop.tab_'.$type) }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="label" for="bg-upload">{{ __('admin.image') }}</label>
            <input id="bg-upload" type="file" wire:model="upload" accept="image/png,image/jpeg,image/webp,image/gif"
                   class="block w-full text-sm text-dc-muted file:mr-3 file:rounded-md file:border-0 file:bg-dc-600 file:px-3 file:py-2 file:text-dc-header hover:file:bg-dc-500">
            <p class="mt-2 text-xs text-dc-faint">{{ __('admin.image_hint_'.$newType) }}</p>
            @error('upload') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="bg-name">{{ __('admin.name') }}</label>
            <input id="bg-name" type="text" wire:model="newName" maxlength="64" class="input">
            @error('newName') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="bg-price">{{ __('admin.price') }} ({{ config('toxbot.currency') }})</label>
            <input id="bg-price" type="number" min="0" wire:model="newPrice" class="input">
            @error('newPrice') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="newPremium" class="accent-[var(--color-accent)]"> {{ __('admin.premium_only') }}
        </label>
        <div class="flex justify-end">
            <button type="submit" wire:loading.attr="disabled" class="btn-primary">{{ __('admin.add') }}</button>
        </div>
    </form>

    <ul class="space-y-3">
        @foreach ($rows as $id => $row)
            @php($item = $items[$id] ?? null)
            @continue(! $item)
            <li wire:key="admin-bg-{{ $id }}" class="grid gap-3 rounded-lg bg-dc-900 p-3 sm:grid-cols-[10rem_1fr]">
                <div class="relative overflow-hidden rounded sm:w-40">
                    @include('livewire.partials.shop-thumbnail', ['type' => $item->type, 'url' => $item->url()])
                    <span class="absolute top-1.5 left-1.5 flex gap-1">
                        @if ($item->animated)
                            <span class="rounded-full bg-dc-950/80 px-2 py-0.5 text-xs font-semibold text-dc-header">{{ __('shop.animated') }}</span>
                        @endif
                    </span>
                </div>
                <div class="grid gap-3 sm:grid-cols-4">
                    <input type="text" wire:model="rows.{{ $id }}.name" aria-label="{{ __('admin.name') }}" class="input sm:col-span-2">
                    <label class="flex items-center gap-2 text-sm text-dc-muted">
                        <span class="shrink-0">{{ config('toxbot.currency') }}</span>
                        <input type="number" min="0" wire:model="rows.{{ $id }}.price" aria-label="{{ __('admin.price') }}" class="input">
                    </label>
                    <label class="flex items-center gap-2 text-sm text-dc-muted">
                        <span class="shrink-0">{{ __('admin.sort') }}</span>
                        <input type="number" wire:model="rows.{{ $id }}.sort" class="input">
                    </label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="rows.{{ $id }}.premium_only" class="accent-[var(--color-accent)]"> {{ __('admin.premium_only') }}</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="rows.{{ $id }}.active" class="accent-[var(--color-accent)]"> {{ __('admin.active') }}</label>
                    <span class="self-center text-xs text-dc-faint">{{ __('admin.owners', ['count' => $owners[$id] ?? 0]) }}</span>
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="saveRow({{ $id }})" class="btn-secondary px-3 py-1.5 whitespace-nowrap">{{ __('admin.save') }}</button>
                        <button type="button" wire:click="delete({{ $id }})" data-confirm="{{ __('admin.confirm_delete_background', ['name' => $row['name']]) }}"
                                class="btn px-3 py-1.5 text-dc-red hover:bg-dc-red hover:text-white">{{ __('admin.delete') }}</button>
                    </div>
                    @error("rows.$id.*") <p class="text-sm text-dc-red sm:col-span-4">{{ $message }}</p> @enderror
                </div>
            </li>
        @endforeach
    </ul>
</section>
