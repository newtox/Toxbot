<x-layouts.app :title="$guild['name']" :guild="$guild" :guilds="$guilds">
    <div class="space-y-8">
        <div class="flex items-center gap-4">
            <x-guild-icon :guild="$guild" class="rounded-2xl" size="size-16" />
            <div class="min-w-0">
                <p class="text-sm font-medium text-dc-muted">{{ __('menu.overview') }}</p>
                <h1 class="truncate text-2xl font-bold text-dc-header">{{ $guild['name'] }}</h1>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="tile">
                <div class="text-2xl font-bold text-dc-header tabular-nums">{{ $members !== null ? number_format($members, 0, '', ' ') : '–' }}</div>
                <div class="text-sm text-dc-muted">{{ __('overview.members') }}</div>
            </div>
            <div class="tile">
                <div class="text-2xl font-bold text-dc-header tabular-nums">{{ $online !== null ? number_format($online, 0, '', ' ') : '–' }}</div>
                <div class="text-sm text-dc-muted">{{ __('overview.online') }}</div>
            </div>
            <a href="{{ route('servers.settings', [$guild['id'], 'streams']) }}" class="tile transition hover:brightness-125">
                <div class="text-2xl font-bold text-dc-header tabular-nums">{{ $streamers }} <span class="text-base font-medium text-dc-muted">/ {{ $streamLimit }}</span></div>
                <div class="text-sm text-dc-muted">{{ __('streams.streamers') }}</div>
            </a>
            <div @class(['tile', 'border-dc-yellow/60! from-dc-yellow/15! to-dc-yellow/[0.03]!' => $premium, 'border-dc-500! from-dc-600/40! to-transparent!' => ! $premium])>
                <div class="text-2xl font-bold text-dc-header">{{ $premium ? __('overview.premium_active') : __('overview.premium_free') }}</div>
                <div class="text-sm text-dc-muted">Server Premium</div>
            </div>
        </div>

        <section>
            <h2 class="mb-3 text-lg font-semibold text-dc-header">{{ __('overview.modules') }}</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($modules as $module)
                    <a href="{{ route('servers.settings', [$guild['id'], $module['section']]) }}"
                       class="group card flex gap-4 transition hover:bg-dc-700 hover:ring-accent/50">
                        <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-dc-600 text-accent-fg">
                            <x-icon :name="$module['icon']" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-dc-header">{{ $module['title'] }}</span>
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-xs font-semibold',
                                    'bg-accent/20 text-accent-fg' => $module['active'],
                                    'bg-dc-600 text-dc-muted' => ! $module['active'],
                                ])>{{ $module['active'] ? __('overview.on') : __('overview.off') }}</span>
                            </div>
                            <p class="mt-1 line-clamp-2 text-sm text-dc-muted">{{ $module['description'] }}</p>
                            @isset($module['extra'])
                                <p class="mt-1 text-xs text-dc-faint">{{ $module['extra'] }}</p>
                            @endisset
                        </div>
                        <span class="self-center text-dc-muted transition group-hover:translate-x-0.5 group-hover:text-dc-header">→</span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.app>
