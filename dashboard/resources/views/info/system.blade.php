<x-layouts.app :title="__('info.system_title')">
    @php
        $online = $status?->online() ?? false;
        $short = fn (?string $commit) => $commit ? substr($commit, 0, 7) : null;
        $botCommit = $status?->commit;
        $dashCommit = $dashboard['commit'];
        $number = fn ($value) => $value === null ? '–' : number_format((int) $value, 0, '', ' ');
        $megabytes = fn (int $bytes) => number_format($bytes / 1048576, 2, '.', '');
    @endphp
    <div class="space-y-6">
        @include('info.header', ['title' => __('info.system_title'), 'description' => __('info.system_description')])

        <section class="card">
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <h2 class="flex-1 text-lg font-semibold text-dc-header">{{ __('info.bot_title') }}</h2>
                <span @class([
                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold',
                    'bg-accent/20 text-accent-fg' => $online,
                    'bg-dc-red/15 text-dc-red' => ! $online,
                ])>
                    <span @class(['size-2 rounded-full', 'bg-accent-fg' => $online, 'bg-dc-red' => ! $online])></span>
                    {{ $online ? __('info.online') : __('info.offline') }}
                </span>
                @if (! $online && $status?->heartbeat_at)
                    <span class="w-full text-right text-xs text-dc-faint">{{ __('info.last_seen', ['time' => $status->heartbeat_at->diffForHumans()]) }}</span>
                @endif
            </div>
            <dl class="divide-y divide-white/5 text-sm">
                @foreach ([
                    'version' => $botCommit ? ['text' => $short($botCommit), 'url' => $repository.'/commit/'.$botCommit, 'built' => $botBuilt] : null,
                    'uptime' => $online && $status?->started_at ? $status->started_at->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE, parts: 2) : null,
                    'latency' => $online && $status?->latency_ms ? $status->latency_ms.' ms' : null,
                    'memory' => $online && $status?->memory_bytes ? $megabytes($status->memory_bytes).($status->memory_peak_bytes ? ' / '.$megabytes($status->memory_peak_bytes) : '').' MB' : null,
                    'dart' => $status?->dart_version,
                    'bot_id' => $status?->user_id,
                ] as $key => $value)
                    <div class="grid grid-cols-[minmax(0,11rem)_1fr] gap-3 py-2.5">
                        <dt class="text-xs font-medium tracking-wide text-dc-faint uppercase">{{ __('info.field_'.$key) }}</dt>
                        <dd class="min-w-0 break-words text-dc-header">
                            @if (is_array($value))
                                <a href="{{ $value['url'] }}" target="_blank" rel="noopener" class="font-mono text-dc-link hover:underline">{{ $value['text'] }}</a>
                                @if ($value['built'])
                                    <span class="text-dc-muted">· {{ __('info.built_ago', ['time' => $value['built']->diffForHumans()]) }}</span>
                                @endif
                            @else
                                <span @class(['font-mono' => $key === 'bot_id'])>{{ $value ?? '–' }}</span>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
            @unless ($status)
                <p class="mt-3 text-sm text-dc-faint">{{ __('info.no_status') }}</p>
            @endunless
        </section>

        <section class="card">
            <h2 class="mb-4 text-lg font-semibold text-dc-header">{{ __('info.dashboard_title') }}</h2>
            <dl class="divide-y divide-white/5 text-sm">
                @foreach ([
                    'version' => $dashCommit ? ['text' => $short($dashCommit), 'url' => $repository.'/commit/'.$dashCommit, 'built' => $dashboard['built']] : null,
                    'php' => $dashboard['php'],
                    'laravel' => $dashboard['laravel'],
                    'os' => $dashboard['os'],
                ] as $key => $value)
                    <div class="grid grid-cols-[minmax(0,11rem)_1fr] gap-3 py-2.5">
                        <dt class="text-xs font-medium tracking-wide text-dc-faint uppercase">{{ __('info.field_'.$key) }}</dt>
                        <dd class="min-w-0 break-words text-dc-header">
                            @if (is_array($value))
                                <a href="{{ $value['url'] }}" target="_blank" rel="noopener" class="font-mono text-dc-link hover:underline">{{ $value['text'] }}</a>
                                @if ($value['built'])
                                    <span class="text-dc-muted">· {{ __('info.built_ago', ['time' => $value['built']->diffForHumans()]) }}</span>
                                @endif
                            @else
                                {{ $value ?? '–' }}
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach (['servers' => $stats['servers'] ?? null, 'users' => $stats['users'] ?? null, 'tags' => $stats['tags'] ?? null, 'starboard' => $stats['starboard'] ?? null] as $key => $value)
                <div class="tile">
                    <div class="text-xs text-dc-muted">{{ __('info.stat_'.$key) }}</div>
                    <div class="mt-1 text-xl font-semibold text-dc-header tabular-nums">{{ $number($value) }}</div>
                </div>
            @endforeach
        </section>
    </div>
</x-layouts.app>
