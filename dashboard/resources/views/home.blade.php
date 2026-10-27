@php
    $features = [
        ['icon' => 'level', 'key' => 'levels'],
        ['icon' => 'coin', 'key' => 'credits'],
        ['icon' => 'card', 'key' => 'profile'],
        ['icon' => 'tag', 'key' => 'tags'],
        ['icon' => 'star', 'key' => 'starboard'],
        ['icon' => 'stream', 'key' => 'streams'],
        ['icon' => 'shield', 'key' => 'mod'],
        ['icon' => 'lock', 'key' => 'protect'],
        ['icon' => 'ticket', 'key' => 'tickets'],
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
    <section>
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 pt-12 pb-16 md:pt-20 lg:grid-cols-[1.15fr_1fr] lg:gap-16">
            <div>
                <p class="flex items-center gap-2 text-sm font-medium text-dc-muted">
                    <span class="size-2 rounded-full bg-accent"></span>{{ __('home.hero_badge') }}
                </p>
                <h1 class="mt-5 text-[2.6rem] leading-[1.02] font-bold text-balance text-dc-header md:text-7xl">
                    {{ __('home.hero_title') }}
                </h1>
                <p class="mt-6 max-w-[52ch] text-lg text-pretty text-dc-muted">
                    {{ __('home.hero_text') }}
                </p>
                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="{{ $inviteUrl }}" target="_blank" rel="noopener" class="btn-primary px-6 py-3 text-base">
                        <x-discord-logo />{{ __('home.invite') }}
                    </a>
                    <a href="{{ route('login') }}" class="btn-secondary px-6 py-3 text-base">
                        {{ __('login.button') }}
                    </a>
                </div>
                <p class="mt-3 text-xs text-dc-faint">{{ __('login.privacy') }}</p>

                @if ($statItems)
                    <dl class="mt-10 flex flex-wrap gap-x-10 gap-y-4">
                        @foreach ($statItems as $label => $value)
                            <div>
                                <dd class="font-display text-3xl font-bold text-dc-header tabular-nums">{{ number_format($value, 0, '', ' ') }}</dd>
                                <dt class="text-sm text-dc-muted">{{ __('home.'.$label) }}</dt>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>

            <div class="hidden justify-self-center lg:block">
                @if ($botAvatar)
                    <img src="{{ str_replace('size=128', 'size=512', $botAvatar) }}" alt="" class="size-72 rounded-full border border-white/10">
                @else
                    <div class="grid size-72 place-items-center rounded-full bg-accent font-display text-9xl font-bold text-on-accent">t</div>
                @endif
            </div>
        </div>
    </section>

    <section id="features" class="scroll-mt-20 border-y border-white/[0.06] bg-dc-950 py-20">
        <div class="mx-auto max-w-6xl px-4">
            <div class="max-w-2xl">
                <h2 class="text-3xl font-bold text-dc-header md:text-5xl">{{ __('home.features_title') }}</h2>
                <p class="mt-4 text-lg text-dc-muted">{{ __('home.features_text') }}</p>
            </div>

            <div class="mt-12 grid gap-x-10 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($features as $feature)
                    <div class="border-t border-white/[0.08] pt-5">
                        <x-icon :name="$feature['icon']" class="size-6 text-accent-fg" />
                        <h3 class="mt-3 text-lg font-semibold text-dc-header">{{ __('home.f_'.$feature['key'].'_t') }}</h3>
                        <p class="mt-1.5 text-sm text-dc-muted">{{ __('home.f_'.$feature['key'].'_d') }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="premium" class="scroll-mt-20 py-20">
        <div class="mx-auto max-w-6xl px-4">
            <div class="max-w-2xl">
                <h2 class="text-3xl font-bold text-dc-header md:text-5xl">{{ __('home.premium_title') }}</h2>
                <p class="mt-4 text-lg text-dc-muted">{{ __('home.premium_text') }}</p>
            </div>

            <div class="mt-12 grid gap-4 md:grid-cols-2">
                @foreach (['user', 'server'] as $plan)
                    <div @class([
                        'rounded-lg p-6 ring-1',
                        'bg-dc-800 ring-dc-yellow/40 shadow-[inset_0_3px_0_var(--color-dc-yellow)]' => $plan === 'user',
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
                            @foreach (range(1, $plan === 'user' ? 4 : 5) as $i)
                                <li class="flex gap-3 text-sm text-dc-text">
                                    <x-icon name="check" @class(['mt-0.5 size-4', 'text-dc-yellow' => $plan === 'user', 'text-accent-fg' => $plan === 'server']) />
                                    {{ __('home.premium_'.$plan.'_'.$i) }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
            <p class="mt-6 text-sm text-dc-faint">{{ __('home.premium_hint') }}</p>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 pb-20">
        <div class="flex flex-col gap-6 rounded-lg border border-white/[0.07] bg-dc-800 px-6 py-10 shadow-[inset_0_3px_0_var(--color-accent)] md:flex-row md:items-center md:justify-between md:px-10">
            <div>
            <h2 class="text-3xl font-bold text-dc-header">{{ __('home.cta_title') }}</h2>
            <p class="mt-2 text-lg text-dc-muted">{{ __('home.cta_text') }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
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
