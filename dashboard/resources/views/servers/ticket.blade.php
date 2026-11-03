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
                <ol x-data="{ ctx: @js($context) }">
                    @foreach ($ticket->messages as $message)
                        @php
                            $previous = $loop->index > 0 ? $ticket->messages[$loop->index - 1] : null;
                            $grouped = $previous && $previous->author_id === $message->author_id && $previous->sent_at && $message->sent_at
                                && $message->sent_at->diffInMinutes($previous->sent_at, true) < 7;
                        @endphp
                        <li @class(['group relative flex gap-4 rounded px-2 hover:bg-black/[0.07]', 'mt-4 py-0.5' => ! $grouped && ! $loop->first, 'py-0.5' => $grouped || $loop->first])>
                            @if ($grouped)
                                <span class="w-10 shrink-0 pt-1 text-right text-[10px] text-dc-faint opacity-0 group-hover:opacity-100">{{ \App\Support\Timezone::format($message->sent_at, 'LT') }}</span>
                            @else
                                <img src="{{ $message->author_avatar ?? 'https://cdn.discordapp.com/embed/avatars/0.png' }}" alt="" class="mt-0.5 size-10 shrink-0 rounded-full" loading="lazy">
                            @endif
                            <div class="min-w-0 flex-1">
                                @unless ($grouped)
                                    <div class="flex flex-wrap items-center gap-x-1.5">
                                        <span class="font-medium text-dc-header">{{ $message->author_name }}</span>
                                        @if ($message->author_bot)
                                            <x-app-tag :verified="$botVerified && $message->author_id === $botId" />
                                        @endif
                                        <span class="ml-0.5 text-xs text-dc-faint">{{ \App\Support\Timezone::format($message->sent_at) }}</span>
                                    </div>
                                @endunless
                                @if ($message->content)
                                    <div class="break-words whitespace-pre-wrap text-dc-text" x-html="toxbot.renderDiscordMessage(@js($message->content), ctx)">{{ $message->content }}</div>
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
