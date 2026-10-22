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

    @if ($backgrounds->isEmpty())
        <div class="card text-center text-dc-muted">{{ __('shop.empty') }}</div>
    @else
        @if ($previewUrl)
            <section class="card space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-medium text-dc-muted">{{ __('shop.preview') }}</h2>
                    @if ($hasBackground)
                        <button type="button" wire:click="useDefault" wire:loading.attr="disabled" class="btn-secondary px-3 py-1.5">{{ __('shop.use_default') }}</button>
                    @endif
                </div>
                <div class="relative overflow-hidden rounded-lg bg-dc-900 ring-1 ring-black/30">
                    <img src="{{ $previewUrl }}" alt="{{ __('shop.preview') }}" width="1000" height="600"
                         class="aspect-[5/3] w-full object-cover" wire:key="preview-{{ $previewId }}">
                    <div wire:loading.flex wire:target="preview" class="absolute inset-0 items-center justify-center bg-dc-900/60">
                        <x-icon name="refresh" class="size-6 animate-spin text-dc-header" />
                    </div>
                </div>
            </section>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($backgrounds as $bg)
                <article wire:key="bg-{{ $bg['id'] }}" @class([
                    'overflow-hidden rounded-xl bg-dc-800 ring-2 transition',
                    'ring-accent' => $bg['id'] === $previewId,
                    'ring-transparent hover:ring-dc-500' => $bg['id'] !== $previewId,
                ])>
                    <button type="button" wire:click="preview({{ $bg['id'] }})" class="relative block w-full" title="{{ __('shop.show_preview') }}">
                        <img src="{{ $bg['url'] }}" alt="" class="aspect-[5/3] w-full object-cover" loading="lazy">
                        @if ($bg['premium_only'])
                            <span class="absolute top-2 left-2 rounded-full bg-dc-yellow px-2 py-0.5 text-xs font-semibold text-dc-950">Premium</span>
                        @endif
                        @if ($bg['equipped'])
                            <span class="absolute top-2 right-2 rounded-full bg-accent px-2 py-0.5 text-xs font-semibold text-on-accent">{{ __('shop.equipped_badge') }}</span>
                        @endif
                    </button>
                    <div class="flex items-center gap-3 p-3">
                        <div class="min-w-0 flex-1">
                            <div class="truncate font-semibold text-dc-header">{{ $bg['name'] }}</div>
                            <div class="text-sm text-dc-muted tabular-nums">
                                @if ($bg['owned'])
                                    {{ __('shop.owned') }}
                                @elseif ($bg['premium_only'] && $bg['price'] === 0)
                                    {{ __('shop.free_with_premium') }}
                                @else
                                    {{ number_format($bg['price'], 0, '', ' ') }} {{ $currency }}
                                @endif
                            </div>
                        </div>

                        @if ($bg['equipped'])
                            <span class="text-sm text-dc-faint">{{ __('shop.in_use') }}</span>
                        @elseif ($bg['locked'])
                            <span class="text-sm text-dc-yellow">{{ __('shop.premium_label') }}</span>
                        @elseif ($bg['owned'])
                            <button type="button" wire:click="equip({{ $bg['id'] }})" wire:loading.attr="disabled" class="btn-secondary px-3 py-1.5">{{ __('shop.use') }}</button>
                        @else
                            <button type="button" wire:click="buy({{ $bg['id'] }})" wire:loading.attr="disabled"
                                    wire:confirm="{{ __('shop.confirm', ['name' => $bg['name'], 'price' => number_format($bg['price'], 0, '', ' ').' '.$currency]) }}"
                                    @disabled($bg['price'] > $credits)
                                    class="btn-primary px-3 py-1.5">{{ __('shop.buy') }}</button>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    <p class="text-sm text-dc-faint">{{ __('shop.earn_hint') }}</p>
</div>
