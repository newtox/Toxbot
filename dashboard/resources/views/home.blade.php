@php
    $features = [
        ['icon' => 'level', 'key' => 'levels'],
        ['icon' => 'coin', 'key' => 'credits'],
        ['icon' => 'card', 'key' => 'profile'],
        ['icon' => 'tag', 'key' => 'tags'],
        ['icon' => 'stream', 'key' => 'streams'],
        ['icon' => 'live', 'key' => 'live'],
        ['icon' => 'welcome', 'key' => 'welcome'],
        ['icon' => 'heart', 'key' => 'fun'],
        ['icon' => 'globe', 'key' => 'lang'],
    ];
    $statItems = array_filter([
        'stat_servers' => $stats['servers'],
        'stat_users' => $stats['users'],
        'stat_tags' => $stats['tags'],
    ], fn ($value) => (int) $value > 0);
    $repo = 'https://github.com/newtox/Toxbot';
@endphp

<x-layouts.app :description="__('home.hero_text')">
    <section class="relative isolate overflow-hidden">
        <div class="pointer-events-none absolute inset-x-0 -top-40 -z-10 mx-auto h-[28rem] max-w-3xl rounded-full bg-accent/20 blur-3xl"></div>

        <div class="mx-auto flex max-w-6xl flex-col items-center gap-12 px-4 pt-12 pb-20 md:pt-20 lg:flex-row lg:gap-16">
            <div class="flex-1 text-center lg:text-left">
                <span class="inline-flex items-center gap-2 rounded-full bg-dc-800 px-3 py-1 text-sm font-medium text-dc-muted ring-1 ring-white/5">
                    <span class="size-2 rounded-full bg-accent"></span>{{ __('home.hero_badge') }}
                </span>
                <h1 class="mt-6 text-4xl leading-[1.08] font-bold tracking-tight text-balance text-dc-header md:text-6xl">
                    {{ __('home.hero_title') }}
                </h1>
                <p class="mx-auto mt-6 max-w-[54ch] text-lg text-pretty text-dc-muted lg:mx-0">
                    {{ __('home.hero_text') }}
                </p>
                <div class="mt-9 flex flex-wrap justify-center gap-3 lg:justify-start">
                    <a href="{{ $inviteUrl }}" target="_blank" rel="noopener" class="btn-primary px-6 py-3 text-base">
                        <x-discord-logo />{{ __('home.invite') }}
                    </a>
                    <a href="{{ route('login') }}" class="btn-secondary px-6 py-3 text-base">
                        {{ __('login.button') }}
                    </a>
                </div>
                <p class="mt-3 text-xs text-dc-faint">{{ __('login.privacy') }}</p>
            </div>

            <div class="relative shrink-0 max-lg:order-first">
                <div class="absolute inset-0 -z-10 rounded-full bg-accent/30 blur-2xl"></div>
                @if ($botAvatar)
                    <img src="{{ str_replace('size=128', 'size=512', $botAvatar) }}" alt="" class="size-32 rounded-full ring-8 ring-dc-800 lg:size-64">
                @else
                    <div class="grid size-32 place-items-center rounded-full bg-accent text-6xl font-bold text-on-accent ring-8 ring-dc-800 lg:size-64 lg:text-8xl">t</div>
                @endif
            </div>
        </div>

        @if ($statItems)
            <div class="mx-auto max-w-6xl px-4 pb-16">
                <dl class="grid grid-cols-3 gap-2 sm:gap-3">
                    @foreach ($statItems as $label => $value)
                        <div class="tile text-center">
                            <dd class="text-xl font-bold text-dc-header tabular-nums sm:text-3xl">{{ number_format($value, 0, '', ' ') }}</dd>
                            <dt class="mt-1 text-xs text-dc-muted sm:text-sm">{{ __('home.'.$label) }}</dt>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endif
    </section>

    <section id="features" class="scroll-mt-20 bg-dc-950/60 py-20">
        <div class="mx-auto max-w-6xl px-4">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-dc-header md:text-4xl">{{ __('home.features_title') }}</h2>
                <p class="mt-4 text-lg text-dc-muted">{{ __('home.features_text') }}</p>
            </div>

            <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($features as $feature)
                    <div class="card">
                        <span class="grid size-11 place-items-center rounded-lg bg-accent/15 text-accent-fg">
                            <x-icon :name="$feature['icon']" />
                        </span>
                        <h3 class="mt-4 font-semibold text-dc-header">{{ __('home.f_'.$feature['key'].'_t') }}</h3>
                        <p class="mt-1.5 text-sm text-dc-muted">{{ __('home.f_'.$feature['key'].'_d') }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="premium" class="scroll-mt-20 py-20">
        <div class="mx-auto max-w-5xl px-4">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-dc-header md:text-4xl">{{ __('home.premium_title') }}</h2>
                <p class="mt-4 text-lg text-dc-muted">{{ __('home.premium_text') }}</p>
            </div>

            <div class="mt-12 grid gap-4 md:grid-cols-2">
                @foreach (['user', 'server'] as $plan)
                    <div @class([
                        'rounded-xl p-6 ring-1',
                        'bg-linear-to-br from-dc-yellow/15 to-dc-800 ring-dc-yellow/50' => $plan === 'user',
                        'bg-dc-800 ring-white/5' => $plan === 'server',
                    ])>
                        <div class="flex items-center gap-3">
                            <span @class([
                                'grid size-11 place-items-center rounded-lg',
                                'bg-dc-yellow/20 text-dc-yellow' => $plan === 'user',
                                'bg-accent/15 text-accent-fg' => $plan === 'server',
                            ])>
                                <x-icon :name="$plan === 'user' ? 'sparkles' : 'servers'" />
                            </span>
                            <div>
                                <h3 class="text-lg font-semibold text-dc-header">{{ __('home.premium_'.$plan.'_t') }}</h3>
                                <p class="text-sm text-dc-muted">{{ __('home.premium_'.$plan.'_s') }}</p>
                            </div>
                        </div>
                        <ul class="mt-6 space-y-3">
                            @foreach (range(1, 4) as $i)
                                <li class="flex gap-3 text-sm text-dc-text">
                                    <x-icon name="check" @class(['mt-0.5 size-4', 'text-dc-yellow' => $plan === 'user', 'text-accent-fg' => $plan === 'server']) />
                                    {{ __('home.premium_'.$plan.'_'.$i) }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
            <p class="mt-6 text-center text-sm text-dc-faint">{{ __('home.premium_hint') }}</p>
        </div>
    </section>

    <section class="px-4 pb-20">
        <div class="relative isolate mx-auto max-w-5xl overflow-hidden rounded-2xl bg-dc-800 px-6 py-14 text-center ring-1 ring-accent/40">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-linear-to-br from-accent/20 to-transparent"></div>
            <h2 class="text-3xl font-bold tracking-tight text-dc-header">{{ __('home.cta_title') }}</h2>
            <p class="mt-3 text-lg text-dc-muted">{{ __('home.cta_text') }}</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ $inviteUrl }}" target="_blank" rel="noopener" class="btn-primary px-6 py-3 text-base">
                    <x-discord-logo />{{ __('home.invite') }}
                </a>
                <a href="{{ route('login') }}" class="btn-secondary px-6 py-3 text-base">{{ __('home.dashboard') }}</a>
            </div>
        </div>
    </section>

    <footer class="border-t border-white/5">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 text-sm text-dc-muted sm:flex-row">
            <x-brand :bot-avatar="$botAvatar" />
            <nav class="flex flex-wrap justify-center gap-x-6 gap-y-2">
                <a href="{{ $repo }}/blob/main/TERMS_OF_SERVICE.md" target="_blank" rel="noopener" class="hover:text-dc-header">{{ __('home.footer_terms') }}</a>
                <a href="{{ $repo }}/blob/main/PRIVACY_POLICY.md" target="_blank" rel="noopener" class="hover:text-dc-header">{{ __('home.footer_privacy') }}</a>
                <a href="{{ $repo }}" target="_blank" rel="noopener" class="hover:text-dc-header">GitHub</a>
                <a href="https://top.gg/bot/{{ config('services.discord.client_id') }}" target="_blank" rel="noopener" class="hover:text-dc-header">Top.gg</a>
            </nav>
        </div>
    </footer>
</x-layouts.app>
