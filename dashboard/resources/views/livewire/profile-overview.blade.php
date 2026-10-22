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

    <div class="overflow-hidden rounded-lg bg-dc-900 ring-1 ring-black/30">
        <img src="{{ $cardUrl }}" alt="{{ __('levels.card_alt') }}" width="1000" height="600"
             class="aspect-[5/3] w-full object-cover">
    </div>

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
        {{ $premium ? __('levels.premium_active') : __('levels.earn_hint') }}
    </p>
</section>
