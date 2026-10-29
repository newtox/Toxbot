<div class="space-y-6">
    <section class="card space-y-4">
        <div class="flex flex-wrap items-center gap-3">
            <h2 class="flex-1 text-lg font-semibold text-dc-header">{{ __('home.premium_server_t') }}</h2>
            <span class="rounded-full bg-dc-yellow px-2.5 py-0.5 text-xs font-semibold text-dc-950">{{ __('profile.premium_per_server') }}</span>
        </div>
        <p class="text-sm text-dc-muted">{{ __('profile.premium_intro') }}</p>
        <ul class="space-y-2 text-sm text-dc-text">
            @foreach (range(1, 5) as $i)
                <li class="flex gap-2"><x-icon name="check" class="mt-0.5 size-4 shrink-0 text-accent-fg" />{{ __('home.premium_server_'.$i) }}</li>
            @endforeach
        </ul>
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
            @if ($storeUrl)
                <a href="{{ $storeUrl }}" target="_blank" rel="noopener" class="btn bg-dc-yellow font-semibold text-dc-950 hover:brightness-110">
                    {{ __('home.premium_buy') }} <x-icon name="external" class="size-4" />
                </a>
            @endif
            <p class="min-w-0 flex-1 basis-64 text-sm text-dc-faint">{{ __('home.premium_hint') }}</p>
        </div>
    </section>

    <section class="card space-y-3">
        <div>
            <h2 class="text-lg font-semibold text-dc-header">{{ __('profile.premium_servers_title') }}</h2>
            <p class="text-sm text-dc-muted">{{ __('profile.premium_servers_description') }}</p>
        </div>
        @if ($failed)
            <p class="text-sm text-dc-red">{{ __('profile.premium_servers_failed') }}</p>
        @elseif ($guilds === [])
            <p class="text-sm text-dc-faint">{{ __('profile.premium_servers_empty') }}</p>
        @else
            <ul class="divide-y divide-white/5 rounded-lg bg-dc-900">
                @foreach ($guilds as $guild)
                    <li wire:key="premium-guild-{{ $guild['id'] }}" class="flex items-center gap-3 px-3 py-2.5">
                        <x-guild-icon :guild="$guild" size="size-9" class="rounded-xl" />
                        <span class="min-w-0 flex-1 truncate font-medium text-dc-header">{{ $guild['name'] }}</span>
                        @if ($guild['premium'])
                            <span class="shrink-0 rounded-full bg-dc-yellow px-2.5 py-0.5 text-xs font-semibold text-dc-950">
                                {{ $guild['until'] ? __('profile.premium_until', ['date' => \App\Support\Timezone::format($guild['until'], 'll')]) : __('profile.premium_active') }}
                            </span>
                        @else
                            <span class="shrink-0 rounded-full bg-dc-600 px-2.5 py-0.5 text-xs font-semibold text-dc-muted">{{ __('profile.premium_inactive') }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
