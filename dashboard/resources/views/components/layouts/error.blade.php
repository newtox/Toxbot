@props(['code', 'title', 'text'])

@php
    $palette = rescue(fn () => \App\Support\Accent::forUser(Auth::user()), \App\Support\Accent::palette(\App\Support\Accent::DEFAULT), report: false);
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}"
      style="--color-accent: {{ $palette['accent'] }}; --color-on-accent: {{ $palette['on'] }}; --color-accent-fg: {{ $palette['fg'] }};">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="{{ $palette['accent'] }}">
    <title>{{ $code }} · Toxbot</title>
    <link rel="icon" type="image/svg+xml" href="{{ 'data:image/svg+xml,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><circle cx="32" cy="32" r="32" fill="'.$palette['accent'].'"/><text x="32" y="44" text-anchor="middle" font-family="sans-serif" font-size="36" font-weight="700" fill="'.$palette['on'].'">t</text></svg>') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-dvh">
    <main class="flex min-h-dvh flex-col items-center justify-center px-4 py-12 text-center">
        <span class="mb-8 flex items-center gap-2.5">
            <span class="grid size-9 place-items-center rounded-full bg-accent text-base font-bold text-on-accent">t</span>
            <span class="text-xl font-bold tracking-tight text-accent-fg">toxbot</span>
        </span>
        <p class="text-7xl font-extrabold tracking-tight text-accent-fg tabular-nums sm:text-8xl">{{ $code }}</p>
        <h1 class="mt-4 text-2xl font-bold text-dc-header">{{ $title }}</h1>
        <p class="mt-2 max-w-md text-dc-muted">{{ $text }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ url('/') }}" class="btn-primary">{{ __('errors.home') }}</a>
            <button type="button" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/') }}')" class="btn-secondary">{{ __('errors.back') }}</button>
        </div>
    </main>
</body>
</html>
