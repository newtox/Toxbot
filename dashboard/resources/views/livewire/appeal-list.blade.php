<div class="space-y-4">
    <section class="card">
        <div class="flex items-start gap-4">
            <div class="flex-1">
                <h2 class="text-lg font-semibold text-dc-header">{{ __('tickets.appeals_title') }}</h2>
                <p class="text-sm text-dc-muted">{{ __('tickets.appeals_description') }}</p>
            </div>
            @if ($manage)
            <label class="relative mt-1 inline-flex shrink-0 cursor-pointer items-center">
                <input type="checkbox" wire:click="toggleEnabled" @checked($enabled) class="peer sr-only">
                <span class="h-6 w-10 rounded-full bg-dc-400 transition-colors peer-checked:bg-accent peer-focus-visible:ring-2 peer-focus-visible:ring-accent-fg peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-dc-800"></span>
                <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                <span class="sr-only">{{ __('guild.enable', ['name' => __('tickets.appeals_title')]) }}</span>
            </label>
            @endif
        </div>
        @if ($enabled)
            <div class="mt-4 space-y-2 text-sm">
                <p class="text-dc-muted">{{ __('tickets.appeals_link') }}</p>
                <p class="rounded-md bg-dc-900 px-3 py-2 font-mono text-xs break-all text-dc-header">{{ route('appeals.show', $guildId) }}</p>
                <p class="text-dc-faint">{{ __('tickets.appeals_hint') }}</p>
            </div>
        @endif
    </section>

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
                        <button type="button" wire:click="reject({{ $appeal->id }})" data-confirm="{{ __('tickets.appeal_confirm_reject') }}" class="btn px-3 py-1.5 text-dc-red hover:bg-dc-red hover:text-white">{{ __('tickets.appeal_reject') }}</button>
                        <button type="button" wire:click="accept({{ $appeal->id }})" data-confirm="{{ __('tickets.appeal_confirm_accept') }}" data-confirm-tone="primary" class="btn-primary px-3 py-1.5">{{ __('tickets.appeal_accept') }}</button>
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
