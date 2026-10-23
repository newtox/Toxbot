<x-layouts.app :title="__('tickets.history_title').' · '.$guild['name']" :guild="$guild" :guilds="$guilds">
    <div class="space-y-6">
        <div>
            <p class="text-sm font-medium text-dc-muted">{{ $guild['name'] }}</p>
            <h1 class="text-2xl font-bold text-dc-header">{{ __('tickets.history_title') }}</h1>
            <p class="mt-1 text-sm text-dc-muted">{{ __('tickets.history_description') }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach (['' => __('tickets.filter_all'), 'open' => __('tickets.filter_open'), 'closed' => __('tickets.filter_closed')] as $value => $label)
                <a href="{{ route('servers.tickets', array_filter([$guild['id'], 'status' => $value ?: null])) }}"
                   @class([
                       'rounded-full px-3 py-1 text-sm font-medium transition',
                       'bg-accent text-on-accent' => ($status ?? '') === $value,
                       'bg-dc-800 text-dc-muted hover:text-dc-header' => ($status ?? '') !== $value,
                   ])>{{ $label }}</a>
            @endforeach
        </div>

        <div class="overflow-hidden rounded-xl bg-dc-800 ring-1 ring-white/5">
            @forelse ($tickets as $ticket)
                <a href="{{ route('servers.ticket', [$guild['id'], $ticket->id]) }}"
                   class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b border-white/5 px-4 py-3 transition last:border-0 hover:bg-dc-700">
                    <span class="font-mono text-sm text-dc-muted">#{{ str_pad((string) $ticket->number, 4, '0', STR_PAD_LEFT) }}</span>
                    <span class="min-w-0 flex-1 basis-40 truncate font-medium text-dc-header">{{ $ticket->user_name ?? $ticket->user_id }}</span>
                    <span class="text-sm text-dc-muted">{{ $ticket->type_name }}</span>
                    <span @class([
                        'rounded-full px-2 py-0.5 text-xs font-semibold',
                        'bg-accent/20 text-accent-fg' => $ticket->status === 'open',
                        'bg-dc-600 text-dc-muted' => $ticket->status !== 'open',
                    ])>{{ __('tickets.status_'.$ticket->status) }}</span>
                    <span class="text-xs text-dc-faint">{{ \App\Support\Timezone::format($ticket->created_at) }}</span>
                </a>
            @empty
                <p class="px-4 py-8 text-center text-sm text-dc-muted">{{ __('tickets.history_empty') }}</p>
            @endforelse
        </div>

        @if ($tickets->hasPages())
            <div class="flex items-center justify-between text-sm">
                <a href="{{ $tickets->previousPageUrl() ?? '#' }}" @class(['btn-secondary', 'pointer-events-none opacity-50' => $tickets->onFirstPage()])>←</a>
                <span class="text-dc-muted tabular-nums">{{ $tickets->currentPage() }} / {{ $tickets->lastPage() }}</span>
                <a href="{{ $tickets->nextPageUrl() ?? '#' }}" @class(['btn-secondary', 'pointer-events-none opacity-50' => ! $tickets->hasMorePages()])>→</a>
            </div>
        @endif
    </div>
</x-layouts.app>
