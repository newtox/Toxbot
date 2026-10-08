<x-layouts.app :title="__('menu.servers')">
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-dc-header">{{ __('servers.title') }}</h1>
            <p class="mt-1 text-dc-muted">{{ __('servers.subtitle') }}</p>
        </div>
        <a href="{{ route('servers.index', ['refresh' => 1]) }}" class="btn-secondary">{{ __('servers.reload') }}</a>
    </div>

    @if (empty($withBot) && empty($withoutBot))
        <div class="card text-center text-dc-muted">
            {{ __('servers.empty') }}
        </div>
    @endif

    @if ($withBot)
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($withBot as $guild)
                <a href="{{ route('servers.show', $guild['id']) }}"
                   class="group card flex items-center gap-4 ring-1 ring-transparent transition hover:bg-dc-600 hover:ring-accent/60">
                    <x-guild-icon :guild="$guild" class="rounded-2xl transition-[border-radius] group-hover:rounded-xl" />
                    <div class="min-w-0">
                        <div class="truncate font-semibold text-dc-header">{{ $guild['name'] }}</div>
                        <div class="text-sm text-dc-muted">{{ $guild['owner'] ? __('servers.owner') : __('servers.manager') }}</div>
                    </div>
                    <span class="ml-auto text-dc-muted transition group-hover:translate-x-0.5 group-hover:text-dc-header">→</span>
                </a>
            @endforeach
        </div>
    @endif

    @if ($withoutBot)
        <h2 class="mt-12 mb-4 text-xs font-bold tracking-wide text-dc-muted uppercase">{{ __('servers.without_bot') }}</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($withoutBot as $guild)
                <div class="card flex items-center gap-4 opacity-70 transition hover:opacity-100">
                    <x-guild-icon :guild="$guild" class="rounded-2xl grayscale" />
                    <div class="min-w-0 flex-1 truncate font-semibold text-dc-header">{{ $guild['name'] }}</div>
                    <a href="{{ $discord->inviteUrl($guild['id']) }}" target="_blank" rel="noopener" class="btn-primary px-3 py-1.5">{{ __('servers.invite') }}</a>
                </div>
            @endforeach
        </div>
        <p class="mt-4 text-sm text-dc-faint">{{ __('servers.after_invite') }}</p>
    @endif
</x-layouts.app>
