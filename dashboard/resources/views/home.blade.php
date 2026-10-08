<x-layouts.app>
    <section class="mx-auto flex min-h-[70dvh] max-w-xl flex-col items-center justify-center text-center">
        @if ($botAvatar)
            <img src="{{ str_replace('size=128', 'size=256', $botAvatar) }}" alt="" class="size-24 rounded-full ring-4 ring-accent">
        @else
            <div class="grid size-24 place-items-center rounded-full bg-accent text-4xl font-bold text-on-accent">t</div>
        @endif

        <h1 class="mt-8 text-4xl leading-[1.1] font-bold tracking-tight text-balance text-dc-header md:text-5xl">
            {{ __('login.headline') }}
        </h1>
        <p class="mt-5 max-w-[48ch] text-lg text-pretty text-dc-muted">
            {{ __('login.intro') }}
        </p>

        <a href="{{ route('login') }}" class="btn-primary mt-8 px-6 py-3 text-base">
            <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.3 4.4A19.8 19.8 0 0 0 15.4 3l-.6 1.3a18.3 18.3 0 0 0-5.6 0L8.6 3a19.7 19.7 0 0 0-4.9 1.4C.6 9 0 13.6.3 18.1a19.9 19.9 0 0 0 6 3l1.3-2.1a12.9 12.9 0 0 1-2-1l.5-.4a14.2 14.2 0 0 0 12.2 0l.5.4c-.6.4-1.3.7-2 1l1.3 2.1a19.8 19.8 0 0 0 6-3c.5-5.2-.8-9.7-3.8-13.7ZM8.5 15.4c-1.2 0-2.2-1.1-2.2-2.4s1-2.4 2.2-2.4 2.2 1.1 2.2 2.4-1 2.4-2.2 2.4Zm7 0c-1.2 0-2.2-1.1-2.2-2.4s1-2.4 2.2-2.4 2.2 1.1 2.2 2.4-1 2.4-2.2 2.4Z"/></svg>
            {{ __('login.button') }}
        </a>
        <p class="mt-3 text-xs text-dc-faint">{{ __('login.privacy') }}</p>
    </section>
</x-layouts.app>
