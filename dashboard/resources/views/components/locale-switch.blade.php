@props(['compact' => false])

@php
    $locales = config('app.locales');
    $current = app()->getLocale();
@endphp

<div x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape="open = false"
     @class(['relative', 'w-full' => ! $compact])>
    <button type="button" x-on:click="open = !open" :aria-expanded="open" title="{{ __('menu.switch') }}"
            @class([
                'flex items-center gap-2 rounded-lg bg-dc-900 text-sm font-medium text-dc-header transition hover:bg-dc-600',
                'w-full px-3 py-2' => ! $compact,
                'px-2.5 py-1.5' => $compact,
            ])>
        <x-icon name="globe" class="size-4 text-dc-muted" />
        <span @class(['flex-1 text-left' => ! $compact])>{{ $locales[$current]['name'] }}</span>
        <x-icon name="chevrons" class="size-4 text-dc-muted" />
    </button>

    <div x-show="open" x-transition.opacity x-cloak
         @class([
             'absolute z-20 max-h-80 w-48 overflow-y-auto rounded-lg bg-dc-950 p-1.5 shadow-2xl ring-1 ring-black/40',
             'bottom-full left-0 mb-2 w-full' => ! $compact,
             'top-full right-0 mt-2' => $compact,
         ])>
        @foreach ($locales as $code => $locale)
            <a href="{{ route('language.switch', $code) }}" lang="{{ $code }}"
               @class([
                   'flex items-center justify-between rounded-md px-2.5 py-1.5 text-sm',
                   'bg-accent text-on-accent' => $code === $current,
                   'text-dc-text hover:bg-dc-600' => $code !== $current,
               ])>
                {{ $locale['name'] }}
                <span class="text-xs uppercase opacity-60">{{ $code }}</span>
            </a>
        @endforeach
    </div>
</div>
