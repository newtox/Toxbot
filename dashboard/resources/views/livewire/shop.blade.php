@php($currency = config('toxbot.currency'))
<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-dc-header">{{ __('shop.title') }}</h1>
            <p class="mt-1 text-dc-muted">{{ __('shop.intro') }}</p>
        </div>
        <div class="rounded-lg bg-dc-800 px-4 py-2 text-right">
            <div class="text-xs text-dc-muted">{{ __('shop.balance') }}</div>
            <div class="text-lg font-semibold text-dc-header tabular-nums" data-test="balance">{{ number_format($credits, 0, '', ' ') }} {{ $currency }}</div>
        </div>
    </div>

    @if ($message)
        <div role="status" @class([
            'rounded-lg border-l-4 bg-dc-800 px-4 py-3 text-sm',
            'border-accent' => $success,
            'border-dc-red' => ! $success,
        ])>{{ $message }}</div>
    @endif

    <section class="card space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-medium text-dc-muted">{{ __('shop.preview') }}</h2>
            @if ($hasEquipped)
                <button type="button" wire:click="useDefault" wire:loading.attr="disabled" class="btn-secondary px-3 py-1.5">{{ __('shop.use_default') }}</button>
            @endif
        </div>
        <div class="relative overflow-hidden rounded-lg bg-dc-900 ring-1 ring-black/30">
            <img src="{{ $previewUrl }}" alt="{{ __('shop.preview') }}" width="1000" height="600"
                 class="aspect-[5/3] w-full object-cover" wire:key="preview-{{ md5($previewUrl) }}">
            <div wire:loading.flex wire:target="preview,selectTab" class="absolute inset-0 items-center justify-center bg-dc-900/60">
                <x-icon name="refresh" class="size-6 animate-spin text-dc-header" />
            </div>
        </div>
    </section>

    <div class="flex flex-wrap gap-2" role="tablist">
        @foreach (\App\Models\ShopItem::TYPES as $type)
            <button type="button" role="tab" wire:click="selectTab('{{ $type }}')" aria-selected="{{ $tab === $type ? 'true' : 'false' }}"
                    @class([
                        'rounded-full px-4 py-1.5 text-sm font-medium transition',
                        'bg-accent text-on-accent' => $tab === $type,
                        'bg-dc-800 text-dc-muted hover:text-dc-header' => $tab !== $type,
                    ])>
                {{ __('shop.tab_'.$type) }}
                <span class="ml-1 tabular-nums opacity-70">{{ $counts[$type] ?? 0 }}</span>
            </button>
        @endforeach
    </div>

    @if ($items->isEmpty())
        <div class="card text-center text-dc-muted">{{ __('shop.empty') }}</div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($items as $item)
                <article wire:key="item-{{ $item['id'] }}" @class([
                    'overflow-hidden rounded-xl bg-dc-800 ring-2 transition',
                    'ring-accent' => $item['id'] === $selected,
                    'ring-transparent hover:ring-dc-500' => $item['id'] !== $selected,
                ])>
                    <button type="button" wire:click="preview({{ $item['id'] }})" class="relative block w-full" title="{{ __('shop.show_preview') }}">
                        @include('livewire.partials.shop-thumbnail', ['type' => $tab, 'url' => $item['url']])
                        <span class="absolute top-2 left-2 flex gap-1.5">
                            @if ($item['premium_only'])
                                <span class="rounded-full bg-dc-yellow px-2 py-0.5 text-xs font-semibold text-dc-950">Premium</span>
                            @endif
                            @if ($item['animated'])
                                <span class="rounded-full bg-dc-950/80 px-2 py-0.5 text-xs font-semibold text-dc-header">{{ __('shop.animated') }}</span>
                            @endif
                        </span>
                        @if ($item['equipped'])
                            <span class="absolute top-2 right-2 rounded-full bg-accent px-2 py-0.5 text-xs font-semibold text-on-accent">{{ __('shop.equipped_badge') }}</span>
                        @endif
                    </button>
                    <div class="flex items-center gap-3 p-3">
                        <div class="min-w-0 flex-1">
                            <div class="truncate font-semibold text-dc-header">{{ $item['name'] }}</div>
                            <div class="text-sm text-dc-muted tabular-nums">
                                @if ($item['owned'])
                                    {{ __('shop.owned') }}
                                @elseif ($item['premium_only'] && $item['price'] === 0)
                                    {{ __('shop.free_with_premium') }}
                                @else
                                    {{ number_format($item['price'], 0, '', ' ') }} {{ $currency }}
                                @endif
                            </div>
                        </div>

                        @if ($item['equipped'])
                            <span class="text-sm text-dc-faint">{{ __('shop.in_use') }}</span>
                        @elseif ($item['locked'])
                            <span class="text-sm text-dc-yellow">{{ __('shop.premium_label') }}</span>
                        @elseif ($item['owned'])
                            <button type="button" wire:click="equip({{ $item['id'] }})" wire:loading.attr="disabled" class="btn-secondary px-3 py-1.5">{{ __('shop.use') }}</button>
                        @else
                            <button type="button" wire:click="buy({{ $item['id'] }})" wire:loading.attr="disabled"
                                    data-confirm="{{ __('shop.confirm', ['name' => $item['name'], 'price' => number_format($item['price'], 0, '', ' ').' '.$currency]) }}" data-confirm-tone="primary"
                                    @disabled($item['price'] > $credits)
                                    class="btn-primary px-3 py-1.5">{{ __('shop.buy') }}</button>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    <p class="text-sm text-dc-faint">{{ __('shop.earn_hint') }}</p>
</div>
