@php($currency = config('toxbot.currency'))
<section class="card space-y-5">
    <div>
        <h2 class="text-lg font-semibold text-dc-header">{{ __('admin.users_title') }}</h2>
        <p class="text-sm text-dc-muted">{{ __('admin.users_description') }}</p>
    </div>

    <form novalidate wire:submit="load" class="flex flex-wrap items-end gap-3 rounded-lg bg-dc-900 p-4">
        <div class="min-w-0 flex-1 basis-56">
            <label class="label" for="economy-user">{{ __('admin.blacklist_id') }}</label>
            <input id="economy-user" type="text" inputmode="numeric" wire:model="lookup" placeholder="123456789012345678" class="input font-mono">
            @error('lookup') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="btn-primary max-sm:w-full">{{ __('admin.users_load') }}</button>
    </form>

    @if ($notice)
        <p class="rounded-lg border-l-4 border-accent bg-dc-900 px-4 py-3 text-sm" role="status">{{ $notice }}</p>
    @endif

    @if ($data)
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
            <div class="min-w-0 flex-1">
                <div class="truncate text-lg font-semibold text-dc-header">{{ $data['name'] ?? __('admin.users_unknown') }}</div>
                <div class="font-mono text-xs text-dc-muted">{{ $userId }}</div>
            </div>
            <div class="rounded-lg bg-dc-900 px-4 py-2 text-right">
                <div class="text-xs text-dc-muted">{{ __('shop.balance') }}</div>
                <div class="text-lg font-semibold text-dc-header tabular-nums">{{ number_format($data['credits'], 0, '', ' ') }} {{ $currency }}</div>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <form novalidate wire:submit="adjust" class="space-y-3 rounded-lg bg-dc-900 p-4">
                <h3 class="text-sm font-semibold text-dc-header">{{ __('admin.users_adjust') }}</h3>
                <div class="flex gap-2">
                    <input id="economy-amount" type="number" step="1" wire:model="amount" placeholder="500 / -500" aria-label="{{ __('admin.users_adjust') }}" class="input min-w-0 flex-1 tabular-nums">
                    <button type="submit" class="btn-primary">{{ __('admin.users_apply') }}</button>
                </div>
                @error('amount') <p class="text-sm text-dc-red">{{ $message }}</p> @enderror
                <p class="text-xs text-dc-faint">{{ __('admin.users_adjust_hint') }}</p>
            </form>

            <form novalidate wire:submit="give" class="space-y-3 rounded-lg bg-dc-900 p-4">
                <h3 class="text-sm font-semibold text-dc-header">{{ __('admin.users_give') }}</h3>
                <div class="flex gap-2">
                    <select id="economy-item" wire:model="giveId" aria-label="{{ __('admin.users_give') }}" class="input min-w-0 flex-1">
                        <option value="">{{ __('admin.users_pick_item') }}</option>
                        @foreach ($data['available'] as $type => $items)
                            <optgroup label="{{ __('shop.tab_'.$type) }}">
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}{{ $item->active ? '' : ' · '.__('shop.retired') }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-primary">{{ __('admin.users_give_button') }}</button>
                </div>
                @error('giveId') <p class="text-sm text-dc-red">{{ $message }}</p> @enderror
                <p class="text-xs text-dc-faint">{{ __('admin.users_give_hint') }}</p>
            </form>
        </div>

        <div class="space-y-2">
            <h3 class="text-sm font-semibold text-dc-header">{{ __('admin.users_items') }}</h3>
            @if ($data['owned']->isEmpty())
                <p class="text-sm text-dc-faint">{{ __('admin.users_no_items') }}</p>
            @else
                <ul class="divide-y divide-white/5 rounded-lg bg-dc-900">
                    @foreach ($data['owned'] as $item)
                        <li wire:key="owned-{{ $item->id }}" class="flex items-center gap-3 px-3 py-2.5">
                            <div class="min-w-0 flex-1">
                                <div class="truncate font-medium text-dc-header">{{ $item->name }}</div>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <span class="rounded-full bg-dc-700 px-2 py-0.5 text-xs font-semibold text-dc-muted">{{ __('shop.tab_'.$item->type) }}</span>
                                    @if (in_array($item->id, $data['equipped'], true))
                                        <span class="rounded-full bg-accent/20 px-2 py-0.5 text-xs font-semibold text-accent-fg">{{ __('shop.equipped_badge') }}</span>
                                    @endif
                                </div>
                            </div>
                            <button type="button" wire:click="take({{ $item->id }})" data-confirm="{{ __('admin.users_take_confirm', ['name' => $item->name]) }}"
                                    class="btn shrink-0 px-3 py-1.5 text-dc-red hover:bg-dc-red hover:text-white">{{ __('admin.users_take') }}</button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="space-y-2">
            <h3 class="text-sm font-semibold text-dc-header">{{ __('admin.users_history') }}</h3>
            @if ($data['history']->isEmpty())
                <p class="text-sm text-dc-faint">{{ __('admin.users_no_history') }}</p>
            @else
                <ul class="divide-y divide-white/5 rounded-lg bg-dc-900 text-sm">
                    @foreach ($data['history'] as $entry)
                        <li wire:key="tx-{{ $entry->id }}" class="flex items-start gap-3 px-3 py-2">
                            <span @class(['w-20 shrink-0 font-mono tabular-nums', 'text-accent-fg' => $entry->amount >= 0, 'text-dc-red' => $entry->amount < 0])>{{ $entry->amount >= 0 ? '+' : '−' }}{{ number_format(abs($entry->amount), 0, '', ' ') }}</span>
                            <div class="flex min-w-0 flex-1 flex-wrap justify-between gap-x-3">
                                <span class="text-dc-text">{{ \Illuminate\Support\Facades\Lang::has('admin.reason_'.$entry->reason) ? __('admin.reason_'.$entry->reason) : $entry->reason }}</span>
                                <span class="text-xs leading-5 text-dc-faint">{{ \App\Support\Timezone::format($entry->created_at) }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
</section>
