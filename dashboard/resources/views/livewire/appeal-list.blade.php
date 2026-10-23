<div class="space-y-4">
    @unless ($enabled)
        <p class="rounded-lg border-l-4 border-dc-yellow bg-dc-800 px-4 py-3 text-sm">{{ __('tickets.appeals_off') }}</p>
    @endunless

    <div class="flex flex-wrap gap-2">
        @foreach (['pending' => __('tickets.filter_pending'), 'all' => __('tickets.filter_all')] as $value => $label)
            <button type="button" wire:click="$set('filter', '{{ $value }}')"
                    @class([
                        'rounded-full px-3 py-1 text-sm font-medium transition',
                        'bg-accent text-on-accent' => $filter === $value,
                        'bg-dc-800 text-dc-muted hover:text-dc-header' => $filter !== $value,
                    ])>{{ $label }}</button>
        @endforeach
    </div>

    @forelse ($appeals as $appeal)
        <section wire:key="appeal-{{ $appeal->id }}" class="card space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('servers.member', [$guildId, $appeal->user_id]) }}" class="font-semibold text-dc-header hover:underline">{{ $appeal->user_name ?? $appeal->user_id }}</a>
                <span class="font-mono text-xs text-dc-faint">{{ $appeal->user_id }}</span>
                <span @class([
                    'rounded-full px-2 py-0.5 text-xs font-semibold',
                    'bg-dc-yellow/20 text-dc-yellow' => $appeal->status === 'pending',
                    'bg-accent/20 text-accent-fg' => $appeal->status === 'accepted',
                    'bg-dc-red/20 text-dc-red' => $appeal->status === 'rejected',
                ])>{{ __('tickets.appeal_status_'.$appeal->status) }}</span>
                <span class="ml-auto text-xs text-dc-faint">{{ \App\Support\Timezone::format($appeal->created_at) }}</span>
            </div>
            <p class="text-sm break-words whitespace-pre-wrap text-dc-text">{{ $appeal->reason }}</p>

            @if ($appeal->status === 'pending')
                <div class="space-y-3 border-t border-white/5 pt-3">
                    <textarea wire:model="responses.{{ $appeal->id }}" rows="2" maxlength="1000" placeholder="{{ __('tickets.appeal_response_hint') }}" class="input resize-y text-sm" aria-label="{{ __('tickets.appeal_response') }}"></textarea>
                    @error('responses.'.$appeal->id) <p class="text-sm text-dc-red">{{ $message }}</p> @enderror
                    <div class="flex flex-wrap justify-end gap-2">
                        <button type="button" wire:click="reject({{ $appeal->id }})" wire:confirm="{{ __('tickets.appeal_confirm_reject') }}" class="btn px-3 py-1.5 text-dc-red hover:bg-dc-red hover:text-white">{{ __('tickets.appeal_reject') }}</button>
                        <button type="button" wire:click="accept({{ $appeal->id }})" wire:confirm="{{ __('tickets.appeal_confirm_accept') }}" class="btn-primary px-3 py-1.5">{{ __('tickets.appeal_accept') }}</button>
                    </div>
                </div>
            @else
                <p class="text-xs text-dc-muted">
                    {{ __('tickets.appeal_handled', ['name' => $appeal->handled_by_name ?? '–', 'date' => \App\Support\Timezone::format($appeal->handled_at)]) }}
                    @if ($appeal->response) · {{ $appeal->response }} @endif
                </p>
            @endif
        </section>
    @empty
        <p class="rounded-xl bg-dc-800 px-4 py-8 text-center text-sm text-dc-muted">{{ __('tickets.appeals_empty') }}</p>
    @endforelse
</div>
