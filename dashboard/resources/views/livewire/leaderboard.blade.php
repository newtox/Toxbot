<div class="space-y-4">
    @if (! $enabled)
        <div class="card space-y-3 text-center text-dc-muted">
            <p>{{ __('leaderboard.disabled') }}</p>
            @if ($manage)
                <a href="{{ route('servers.settings', [$guildId, 'levels']) }}" class="btn-primary">{{ __('leaderboard.enable') }}</a>
            @endif
        </div>
    @elseif ($entries->isEmpty())
        <div class="card text-center text-dc-muted">{{ __('leaderboard.empty') }}</div>
    @else
        <ol class="divide-y divide-black/25 overflow-hidden rounded-xl bg-dc-800">
            @foreach ($entries as $entry)
                <li wire:key="rank-{{ $entry['id'] }}" @class(['flex items-center gap-3 px-4 py-2.5', 'bg-accent/10' => $entry['self']])>
                    <span class="w-8 shrink-0 text-center font-semibold tabular-nums text-dc-muted">
                        {{ ['🥇', '🥈', '🥉'][$entry['rank'] - 1] ?? '#'.$entry['rank'] }}
                    </span>
                    <img src="{{ $entry['avatar'] }}" alt="" class="size-9 shrink-0 rounded-full" loading="lazy">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-dc-header">{{ $entry['name'] }}</p>
                        @if ($entry['username'])
                            <p class="truncate text-xs text-dc-faint">{{ '@'.$entry['username'] }}</p>
                        @endif
                    </div>
                    <div class="shrink-0 text-right text-sm tabular-nums">
                        <p class="font-medium text-dc-header">{{ __('leaderboard.level', ['level' => $entry['level']]) }}</p>
                        <p class="text-xs text-dc-muted">{{ number_format($entry['xp'], 0, '', "\u{00A0}") }}&nbsp;XP</p>
                    </div>
                </li>
            @endforeach
        </ol>

        @if ($ownRank && $ownRank > $entries->count())
            <p class="text-sm text-dc-muted">{{ __('leaderboard.own_rank', ['rank' => $ownRank, 'xp' => number_format($ownXp, 0, '', "\u{00A0}")]) }}</p>
        @elseif (! $ownRank)
            <p class="text-sm text-dc-muted">{{ __('leaderboard.unranked') }}</p>
        @endif
    @endif
</div>
