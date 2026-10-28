@props(['title' => null, 'description' => null, 'guild' => null, 'guilds' => []])

@php
    $pageTitle = $title ? $title.' · Toxbot' : 'Toxbot Dashboard';
    $pageDescription = $description ?? __('login.intro');
    $shareImage = $botAvatar ? str_replace('size=128', 'size=512', $botAvatar) : null;
    $fallbackIcon = 'data:image/svg+xml,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><circle cx="32" cy="32" r="32" fill="'.$palette['accent'].'"/><text x="32" y="44" text-anchor="middle" font-family="sans-serif" font-size="36" font-weight="700" fill="'.$palette['on'].'">t</text></svg>');
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}"
      style="--color-accent: {{ $palette['accent'] }}; --color-on-accent: {{ $palette['on'] }}; --color-accent-fg: {{ $palette['fg'] }};">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="theme-color" content="{{ $palette['accent'] }}">
    <link rel="canonical" href="{{ url()->current() }}">
    @auth
        <meta name="robots" content="noindex, nofollow">
    @endauth

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Toxbot">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">

    @if ($shareImage)
        <meta property="og:image" content="{{ $shareImage }}">
        <meta name="twitter:image" content="{{ $shareImage }}">
        <link rel="icon" type="image/png" href="{{ $botAvatar }}">
        <link rel="apple-touch-icon" href="{{ $shareImage }}">
    @else
        <link rel="icon" type="image/svg+xml" href="{{ $fallbackIcon }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
</head>
<body class="min-h-dvh" x-data="{ nav: false }" x-on:keydown.escape.window="nav = false"
      @auth x-bind:data-nav="nav ? 'open' : 'closed'" x-effect="document.documentElement.classList.toggle('overflow-hidden', nav)"
      x-on:toxbot-nav.window="nav = $event.detail" x-on:resize.window="if (innerWidth >= 768) nav = false" @endauth>
    @auth
        <div class="md:flex">
            <div x-show="nav" x-transition.opacity x-on:click="nav = false" x-cloak
                 class="fixed inset-0 z-30 touch-none bg-black/60 md:hidden"></div>

            <aside class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-white/[0.06] bg-dc-950 transition-transform duration-200 max-md:-translate-x-full md:sticky md:top-0 md:h-dvh md:w-64 md:shrink-0"
                   :class="nav && 'max-md:translate-x-0!'">
                <div class="flex items-center justify-between px-4 pt-4 pb-3">
                    <x-brand :bot-avatar="$botAvatar" />
                    <button type="button" x-on:click="nav = false" class="rounded-md p-1.5 text-dc-muted hover:bg-dc-600 hover:text-dc-header md:hidden" aria-label="{{ __('menu.close_menu') }}">
                        <x-icon name="close" />
                    </button>
                </div>

                <nav class="flex-1 space-y-5 overflow-y-auto overscroll-contain px-3 pb-4">
                    @if ($guild)
                        <div x-data="{ open: false }" class="relative" x-on:click.outside="open = false">
                            <button type="button" x-on:click="open = !open"
                                    class="flex w-full items-center gap-2.5 rounded-md border border-white/10 bg-dc-800 p-2 text-left transition hover:border-accent">
                                <x-guild-icon :guild="$guild" size="size-8" class="rounded-lg" />
                                <span class="min-w-0 flex-1 truncate text-sm font-semibold text-dc-header">{{ $guild['name'] }}</span>
                                <x-icon name="chevrons" class="size-4 text-dc-muted" />
                            </button>

                            <div x-show="open" x-transition.origin.top x-cloak
                                 class="absolute inset-x-0 z-10 mt-2 max-h-80 overflow-y-auto rounded-lg bg-dc-950 p-1.5 shadow-2xl ring-1 ring-black/40">
                                @foreach ($guilds as $g)
                                    <a href="{{ route('servers.show', $g['id']) }}"
                                       @class([
                                           'flex items-center gap-2.5 rounded-md p-1.5 text-sm',
                                           'bg-accent/15 text-dc-header' => $g['id'] === $guild['id'],
                                           'text-dc-text hover:bg-dc-600' => $g['id'] !== $guild['id'],
                                       ])>
                                        <x-guild-icon :guild="$g" size="size-7" class="rounded-md text-xs" />
                                        <span class="truncate">{{ $g['name'] }}</span>
                                    </a>
                                @endforeach
                                <a href="{{ route('servers.index') }}" class="mt-1 block rounded-md border-t border-dc-600 p-2 pt-2.5 text-sm text-accent-fg hover:underline">
                                    {{ __('menu.all_servers') }}
                                </a>
                            </div>
                        </div>

                        @include('components.layouts.partials.nav-groups', ['groups' => \App\Support\Navigation::forGuild($guild)])
                    @elseif (request()->routeIs(...\App\Support\Navigation::USER_ROUTES))
                        @include('components.layouts.partials.nav-groups', ['groups' => \App\Support\Navigation::forUser()])
                    @elseif (request()->routeIs(...\App\Support\Navigation::INFO_ROUTES))
                        @include('components.layouts.partials.nav-groups', ['groups' => \App\Support\Navigation::forInfo()])
                    @elseif (request()->routeIs(...\App\Support\Navigation::ADMIN_ROUTES))
                        @include('components.layouts.partials.nav-groups', ['groups' => \App\Support\Navigation::forAdmin()])
                    @else
                        @php
                            $sidebarGuilds = rescue(
                                fn () => app(\App\Services\GuildAccess::class)->dashboardGuilds(auth()->user()),
                                [],
                                report: false,
                            );
                        @endphp
                        <div class="space-y-1.5">
                            <p class="nav-heading">{{ __('menu.servers') }}</p>
                            @foreach ($sidebarGuilds as $g)
                                <a href="{{ route('servers.show', $g['id']) }}" class="nav-item">
                                    <x-guild-icon :guild="$g" size="size-6" class="rounded-md text-[0.6rem]" />
                                    <span class="truncate">{{ $g['name'] }}</span>
                                </a>
                            @endforeach
                            <a href="{{ route('servers.index') }}"
                               @class(['nav-item', 'nav-item-active' => request()->routeIs('servers.index')])>
                                <x-icon name="servers" />{{ __('menu.all_servers') }}
                            </a>
                        </div>
                    @endif
                </nav>

                <div class="space-y-3 border-t border-white/[0.06] p-3">
                    <x-locale-switch />

                    <div x-data="{ open: false }" class="relative" x-on:click.outside="open = false" x-on:keydown.escape.stop="open = false">
                        <div x-show="open" x-transition.origin.bottom x-cloak
                             class="absolute inset-x-0 bottom-full z-10 mb-2 space-y-0.5 rounded-lg bg-dc-900 p-1.5 shadow-2xl ring-1 ring-black/40">
                            @foreach (\App\Support\Navigation::userMenu() as $item)
                                <a href="{{ $item['url'] }}" @class(['nav-item', 'nav-item-active' => $item['active']])>
                                    <x-icon :name="$item['icon']" />{{ $item['label'] }}
                                </a>
                            @endforeach
                            <div class="my-1 border-t border-white/10"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="nav-item w-full text-dc-red hover:bg-dc-red hover:text-white">
                                    <x-icon name="logout" />{{ __('menu.logout') }}
                                </button>
                            </form>
                        </div>

                        <button type="button" x-on:click="open = !open" :aria-expanded="open"
                                class="flex w-full items-center gap-3 rounded-md bg-dc-800 p-2 text-left transition hover:bg-dc-700">
                            <img src="{{ auth()->user()->avatar_url }}" alt="" class="size-9 rounded-full">
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-dc-header">{{ auth()->user()->display_name }}</span>
                                <span class="block truncate text-xs text-dc-muted">{{ auth()->user()->username }}</span>
                            </span>
                            <x-icon name="chevrons" class="size-4 text-dc-muted" />
                        </button>
                    </div>
                </div>
            </aside>

            <div class="min-w-0 flex-1">
                <header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-black/30 bg-dc-900/95 px-4 backdrop-blur md:hidden">
                    <button type="button" x-on:click="nav = true" class="-ml-1.5 rounded-md p-1.5 text-dc-header hover:bg-dc-600" aria-label="{{ __('menu.open_menu') }}">
                        <x-icon name="menu" />
                    </button>
                    <x-brand :bot-avatar="$botAvatar" />
                    <button type="button" x-on:click="nav = true" class="ml-auto rounded-full" aria-label="{{ __('menu.open_menu') }}">
                        <img src="{{ auth()->user()->avatar_url }}" alt="" class="size-8 rounded-full">
                    </button>
                </header>

                @include('components.layouts.partials.status')

                <main class="mx-auto w-full max-w-5xl px-4 py-6 md:px-8 md:py-9">
                    {{ $slot }}
                </main>
            </div>
        </div>
    @else
        <header class="sticky top-0 z-30 border-b border-white/5 bg-dc-900/85 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center gap-6 px-4">
                <x-brand :bot-avatar="$botAvatar" />
                @if (request()->routeIs('home'))
                    <nav class="hidden gap-5 text-sm font-medium text-dc-muted sm:flex">
                        <a href="#features" class="hover:text-dc-header">{{ __('home.nav_features') }}</a>
                        <a href="#premium" class="hover:text-dc-header">{{ __('home.nav_premium') }}</a>
                    </nav>
                @endif
                <div class="ml-auto flex items-center gap-2">
                    <x-locale-switch compact />
                    <a href="{{ route('login') }}" class="btn-primary max-sm:hidden">{{ __('home.dashboard') }}</a>
                </div>
            </div>
        </header>

        @include('components.layouts.partials.status')

        <main>
            {{ $slot }}
        </main>
    @endauth

    <div x-data="{ show: @js((bool) session('saved')), timer: null }"
         x-init="show && (timer = setTimeout(() => show = false, 2500))"
         x-on:saved.window="show = true; clearTimeout(timer); timer = setTimeout(() => show = false, 2500)"
         x-show="show" x-transition.opacity x-cloak role="status"
         class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-lg bg-accent px-4 py-2 text-sm font-medium text-on-accent shadow-lg md:left-[calc(50%+8.75rem)]">
        {{ __('common.saved') }}
    </div>

    <dialog id="confirm-dialog" aria-labelledby="confirm-title" aria-describedby="confirm-message"
            class="m-auto w-[calc(100%-2rem)] max-w-md rounded-xl border border-white/10 bg-dc-800 p-0 text-dc-text shadow-2xl backdrop:bg-black/60 backdrop:backdrop-blur-sm">
        <form method="dialog" class="space-y-5 p-6">
            <div class="space-y-2">
                <h2 id="confirm-title" class="text-lg font-semibold text-dc-header">{{ __('common.confirm_title') }}</h2>
                <p id="confirm-message" class="text-sm whitespace-pre-line text-dc-muted"></p>
            </div>
            <div class="flex flex-wrap justify-end gap-2">
                <button value="cancel" class="btn-secondary max-sm:flex-1">{{ __('common.cancel') }}</button>
                <button value="confirm" data-confirm-ok class="btn max-sm:flex-1"></button>
            </div>
        </form>
    </dialog>

    <style>[x-cloak]{display:none!important}</style>
    @livewireScripts
</body>
</html>
