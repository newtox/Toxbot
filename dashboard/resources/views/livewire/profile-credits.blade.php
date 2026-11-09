<section class="card space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-dc-header">{{ __('credits.title') }}</h2>
            <p class="text-sm text-dc-muted">{{ __('credits.description') }}</p>
        </div>
        <p class="text-2xl font-bold text-dc-header tabular-nums">{{ \App\Livewire\ProfileCredits::credits($overview['credits']) }}</p>
    </div>

    @if ($message)
        <div role="status" @class([
            'rounded-lg border-l-4 bg-dc-900 px-4 py-3 text-sm',
            'border-accent' => $success,
            'border-dc-red' => ! $success,
        ])>{{ $message }}</div>
    @endif

    <div class="flex flex-wrap items-center gap-x-6 gap-y-3 rounded-lg bg-dc-900 px-4 py-3">
        <div class="min-w-0 flex-1 basis-56">
            <p class="font-medium text-dc-header">{{ __('credits.daily_title') }}</p>
            <p class="text-sm text-dc-muted">
                {{ __('credits.streak', ['count' => $overview['streak']]) }}
                ·
                {{ $nextDaily ? __('credits.daily_next', ['time' => $nextDaily]) : __('credits.daily_ready') }}
            </p>
        </div>
        <button type="button" wire:click="claim" wire:loading.attr="disabled" wire:target="claim" class="btn-primary" @disabled($nextDaily)>
            <x-icon name="coin" class="size-4" />{{ __('credits.daily_claim') }}
        </button>
    </div>

    <form novalidate wire:submit="pay" class="space-y-4">
        <h3 class="font-semibold text-dc-header">{{ __('credits.pay_title') }}</h3>
        @include('livewire.partials.member-picker', ['pickerId' => 'pay'])
        <div class="flex flex-wrap items-end gap-3">
            <div class="w-48">
                <label class="label" for="pay-amount">{{ __('credits.amount') }}</label>
                <input id="pay-amount" type="number" min="1" inputmode="numeric" wire:model="amount" class="input tabular-nums">
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="pay" class="btn-primary" @disabled($picked === [])
                    data-confirm="{{ __('credits.pay_confirm') }}" data-confirm-tone="primary">{{ __('credits.pay_button') }}</button>
        </div>
        @error('amount') <p class="text-sm text-dc-red">{{ $message }}</p> @enderror
    </form>

    <div>
        <h3 class="mb-2 font-semibold text-dc-header">{{ __('credits.history') }}</h3>
        @if ($overview['history']->isEmpty())
            <p class="text-sm text-dc-muted">{{ __('admin.users_no_history') }}</p>
        @else
            <ul class="divide-y divide-black/25 overflow-hidden rounded-lg bg-dc-900">
                @foreach ($overview['history'] as $entry)
                    <li class="flex items-center gap-3 px-4 py-2 text-sm">
                        <span @class(['w-16 shrink-0 font-mono tabular-nums sm:w-24', 'text-accent-fg' => $entry->amount >= 0, 'text-dc-red' => $entry->amount < 0])>
                            {{ ($entry->amount >= 0 ? '+' : '−').number_format(abs($entry->amount), 0, '', "\u{00A0}") }}
                        </span>
                        <span class="min-w-0 flex-1 truncate">{{ \Illuminate\Support\Facades\Lang::has('admin.reason_'.$entry->reason) ? __('admin.reason_'.$entry->reason) : $entry->reason }}</span>
                        <time class="shrink-0 text-dc-faint" datetime="{{ $entry->created_at?->toIso8601String() }}">{{ $entry->created_at?->diffForHumans() }}</time>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
