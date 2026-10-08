@props(['title' => null, 'guild' => null, 'guilds' => []])

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}"
      style="--color-accent: {{ $palette['accent'] }}; --color-on-accent: {{ $palette['on'] }}; --color-accent-fg: {{ $palette['fg'] }};">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1e1f22">
    <title>{{ $title ? $title.' · ' : '' }}Toxbot</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=rubik:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-dvh" x-data="{ nav: false }" x-on:keydown.escape.window="nav = false">
    @auth
        <div class="md:flex">
            <div x-show="nav" x-transition.opacity x-on:click="nav = false" x-cloak
                 class="fixed inset-0 z-30 bg-black/60 md:hidden"></div>

            <aside class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col bg-dc-800 transition-transform duration-200 max-md:-translate-x-full md:sticky md:top-3 md:bottom-auto md:m-3 md:h-[calc(100dvh-1.5rem)] md:w-64 md:shrink-0 md:rounded-xl"
                   :class="nav && 'max-md:translate-x-0!'">
                <div class="flex items-center justify-between px-4 pt-4 pb-3">
                    <x-brand :bot-avatar="$botAvatar" />
                    <button type="button" x-on:click="nav = false" class="rounded-md p-1.5 text-dc-muted hover:bg-dc-600 hover:text-dc-header md:hidden" aria-label="{{ __('menu.close_menu') }}">
                        <x-icon name="close" />
                    </button>
                </div>

                <nav class="flex-1 space-y-6 overflow-y-auto px-3 pb-4">
                    @if ($guild)
                        <div x-data="{ open: false }" class="relative" x-on:click.outside="open = false">
                            <button type="button" x-on:click="open = !open"
                                    class="flex w-full items-center gap-2.5 rounded-lg bg-dc-900 p-2 text-left ring-1 ring-accent/50 transition hover:ring-accent">
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

                        <div class="space-y-0.5">
                            <p class="px-3 pb-1.5 text-xs font-medium text-dc-faint">{{ __('menu.server_settings') }}</p>
                            <a href="#welcome" x-on:click="nav = false" class="nav-item"><x-icon name="welcome" />{{ __('guild.welcome_title') }}</a>
                            <a href="#bye" x-on:click="nav = false" class="nav-item"><x-icon name="bye" />{{ __('guild.bye_title') }}</a>
                            <a href="#autorole" x-on:click="nav = false" class="nav-item"><x-icon name="autorole" />{{ __('guild.autorole_title') }}</a>
                        </div>
                    @endif

                    <div class="space-y-0.5">
                        @if ($guild)
                            <p class="px-3 pb-1.5 text-xs font-medium text-dc-faint">{{ __('menu.general') }}</p>
                        @endif
                        @foreach ([
                            ['route' => 'servers.index', 'active' => 'servers.index', 'icon' => 'servers', 'label' => __('menu.servers')],
                            ['route' => 'profile', 'active' => 'profile', 'icon' => 'profile', 'label' => __('menu.profile')],
                        ] as $item)
                            <a href="{{ route($item['route']) }}"
                               @class(['nav-item', 'bg-accent! text-on-accent!' => request()->routeIs($item['active'])])>
                                <x-icon :name="$item['icon']" />{{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </nav>

                <div class="space-y-3 border-t border-black/25 p-3">
                    <x-locale-switch />

                    <div class="flex items-center gap-3 rounded-lg bg-dc-900 p-2">
                        <img src="{{ auth()->user()->avatar_url }}" alt="" class="size-9 rounded-full">
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-semibold text-dc-header">{{ auth()->user()->display_name }}</div>
                            <div class="truncate text-xs text-dc-muted">{{ auth()->user()->username }}</div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="rounded-md p-2 text-dc-muted transition hover:bg-dc-red hover:text-white" title="{{ __('menu.logout') }}" aria-label="{{ __('menu.logout') }}">
                                <x-icon name="logout" class="size-4.5" />
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <div class="min-w-0 flex-1">
                <header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-black/30 bg-dc-900/95 px-4 backdrop-blur md:hidden">
                    <button type="button" x-on:click="nav = true" class="-ml-1.5 rounded-md p-1.5 text-dc-header hover:bg-dc-600" aria-label="{{ __('menu.open_menu') }}">
                        <x-icon name="menu" />
                    </button>
                    <x-brand :bot-avatar="$botAvatar" />
                    <img src="{{ auth()->user()->avatar_url }}" alt="" class="ml-auto size-8 rounded-full">
                </header>

                @include('components.layouts.partials.status')

                <main class="mx-auto w-full max-w-4xl px-4 py-6 md:px-8 md:py-9">
                    {{ $slot }}
                </main>
            </div>
        </div>
    @else
        <header class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4">
            <x-brand :bot-avatar="$botAvatar" />
            <x-locale-switch compact />
        </header>

        @include('components.layouts.partials.status')

        <main class="mx-auto w-full max-w-5xl px-4 py-8">
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

    <style>[x-cloak]{display:none!important}</style>
    @livewireScripts
</body>
</html>
