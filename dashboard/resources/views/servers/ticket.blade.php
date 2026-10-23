<x-layouts.app :title="__('tickets.ticket_title', ['number' => $ticket->number]).' · '.$guild['name']" :guild="$guild" :guilds="$guilds">
    <div class="space-y-6">
        <a href="{{ route('servers.tickets', $guild['id']) }}" class="inline-flex items-center gap-1 text-sm text-dc-muted hover:text-dc-header">← {{ __('tickets.history_title') }}</a>

        <section class="card space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold text-dc-header">{{ __('tickets.ticket_title', ['number' => str_pad((string) $ticket->number, 4, '0', STR_PAD_LEFT)]) }}</h1>
                <span @class([
                    'rounded-full px-2 py-0.5 text-xs font-semibold',
                    'bg-accent/20 text-accent-fg' => $ticket->status === 'open',
                    'bg-dc-600 text-dc-muted' => $ticket->status !== 'open',
                ])>{{ __('tickets.status_'.$ticket->status) }}</span>
            </div>
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-dc-muted">{{ __('tickets.opened_by') }}</dt><dd class="text-dc-header">{{ $ticket->user_name ?? $ticket->user_id }} <span class="font-mono text-xs text-dc-faint">{{ $ticket->user_id }}</span></dd></div>
                <div><dt class="text-dc-muted">{{ __('tickets.type') }}</dt><dd class="text-dc-header">{{ $ticket->type_name ?? '–' }}</dd></div>
                <div><dt class="text-dc-muted">{{ __('tickets.opened_at') }}</dt><dd class="text-dc-header">{{ \App\Support\Timezone::format($ticket->created_at) }}</dd></div>
                @if ($ticket->closed_at)
                    <div><dt class="text-dc-muted">{{ __('tickets.closed_at') }}</dt><dd class="text-dc-header">{{ \App\Support\Timezone::format($ticket->closed_at) }} · {{ $ticket->closed_by_name ?? '–' }}</dd></div>
                @endif
                @if ($ticket->close_reason)
                    <div class="sm:col-span-2"><dt class="text-dc-muted">{{ __('tickets.close_reason') }}</dt><dd class="text-dc-header">{{ $ticket->close_reason }}</dd></div>
                @endif
            </dl>
        </section>

        <section class="card">
            <h2 class="mb-4 text-lg font-semibold text-dc-header">{{ __('tickets.transcript') }}</h2>
            @if ($ticket->messages->isEmpty())
                <p class="text-sm text-dc-faint">{{ $ticket->status === 'open' ? __('tickets.transcript_open') : __('tickets.transcript_empty') }}</p>
            @else
                <ol class="space-y-4">
                    @foreach ($ticket->messages as $message)
                        <li class="flex gap-3">
                            <img src="{{ $message->author_avatar ?? 'https://cdn.discordapp.com/embed/avatars/0.png' }}" alt="" class="size-9 shrink-0 rounded-full" loading="lazy">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline gap-2">
                                    <span class="font-medium text-dc-header">{{ $message->author_name }}</span>
                                    @if ($message->author_bot)
                                        <span class="rounded bg-blurple px-1.5 text-[0.65rem] font-bold text-white uppercase">Bot</span>
                                    @endif
                                    <span class="text-xs text-dc-faint">{{ \App\Support\Timezone::format($message->sent_at) }}</span>
                                </div>
                                @if ($message->content)
                                    <p class="text-sm break-words whitespace-pre-wrap text-dc-text">{{ $message->content }}</p>
                                @endif
                                @foreach ($message->attachments ?? [] as $attachment)
                                    <a href="{{ $attachment['url'] }}" target="_blank" rel="noopener" class="block truncate text-sm text-dc-link hover:underline">{{ $attachment['name'] }}</a>
                                @endforeach
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>
</x-layouts.app>
