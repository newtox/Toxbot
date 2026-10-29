<section class="card space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-dc-header">{{ __('levels.card_title') }}</h2>
            <p class="text-sm text-dc-muted">{{ __('levels.card_description') }}</p>
        </div>
        <a href="{{ route('shop') }}" class="btn-secondary">
            <x-icon name="shop" class="size-4" />{{ __('levels.to_shop') }}
        </a>
    </div>

    <div class="relative overflow-hidden rounded-lg bg-dc-900 ring-1 ring-black/30" wire:key="card-{{ md5($cardUrl) }}" x-data="{ loaded: false }">
        <img src="{{ $cardUrl }}" alt="{{ __('levels.card_alt') }}" width="1000" height="600"
             class="aspect-[5/3] w-full object-cover" x-init="loaded = $el.complete && $el.naturalWidth > 0" x-on:load="loaded = true" x-on:error="loaded = true">
        <div x-show="!loaded" class="absolute inset-0 flex items-center justify-center bg-dc-900/60" role="status" aria-label="{{ __('shop.preview_loading') }}">
            <x-icon name="refresh" class="size-6 animate-spin text-dc-header" />
        </div>
        <div wire:loading.flex wire:target="equipped" class="absolute inset-0 items-center justify-center bg-dc-900/60">
            <x-icon name="refresh" class="size-6 animate-spin text-dc-header" />
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        @foreach (\App\Models\ShopItem::TYPES as $type)
            <div>
                <label class="label" for="equip-{{ $type }}">{{ __('levels.equip_'.$type) }}</label>
                <select id="equip-{{ $type }}" wire:model.live="equipped.{{ $type }}" class="input">
                    <option value="">{{ $type === \App\Models\ShopItem::FRAME ? __('levels.equip_no_frame') : __('levels.equip_default') }}</option>
                    @foreach ($usable->get($type, collect()) as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach
    </div>
    @if ($error)
        <p class="text-sm text-dc-red" role="alert">{{ $error }}</p>
    @endif

    <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-lg bg-dc-900 p-3">
            <dt class="text-xs text-dc-muted">{{ __('levels.level') }}</dt>
            <dd class="mt-1 text-xl font-semibold text-dc-header tabular-nums">{{ $level['level'] }}</dd>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-dc-600">
                <div class="h-full rounded-full bg-accent" style="width: {{ round($level['current'] / $level['needed'] * 100, 1) }}%"></div>
            </div>
            <div class="mt-1 text-xs text-dc-faint tabular-nums">{{ number_format($level['current'], 0, '', ' ') }} / {{ number_format($level['needed'], 0, '', ' ') }} XP</div>
        </div>
        <div class="rounded-lg bg-dc-900 p-3">
            <dt class="text-xs text-dc-muted">{{ __('levels.rank') }}</dt>
            <dd class="mt-1 text-xl font-semibold text-dc-header tabular-nums">{{ $rank ? '#'.number_format($rank, 0, '', ' ') : '–' }}</dd>
        </div>
        <div class="rounded-lg bg-dc-900 p-3">
            <dt class="text-xs text-dc-muted">{{ __('levels.credits') }}</dt>
            <dd class="mt-1 text-xl font-semibold text-dc-header tabular-nums">{{ number_format($credits, 0, '', ' ') }}</dd>
        </div>
        <div class="rounded-lg bg-dc-900 p-3">
            <dt class="text-xs text-dc-muted">{{ __('levels.commands') }}</dt>
            <dd class="mt-1 text-xl font-semibold text-dc-header tabular-nums">{{ number_format($commands, 0, '', ' ') }}</dd>
        </div>
    </dl>

    <p class="text-sm text-dc-faint">
        {{ __('levels.earn_hint') }}
    </p>
</section>
