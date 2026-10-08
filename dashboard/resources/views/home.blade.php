<x-layouts.app>
    <section class="grid items-center gap-10 py-10 md:grid-cols-2">
        <div>
            <h1 class="max-w-[16ch] text-4xl leading-[1.1] font-bold tracking-tight text-dc-header md:text-5xl">
                {{ __('login.headline') }}
            </h1>
            <p class="mt-5 max-w-[48ch] text-lg text-dc-muted">
                {{ __('login.intro') }}
            </p>
            <a href="{{ route('login') }}" class="btn-primary mt-8 px-6 py-3 text-base">
                <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.3 4.4A19.8 19.8 0 0 0 15.4 3l-.6 1.3a18.3 18.3 0 0 0-5.6 0L8.6 3a19.7 19.7 0 0 0-4.9 1.4C.6 9 0 13.6.3 18.1a19.9 19.9 0 0 0 6 3l1.3-2.1a12.9 12.9 0 0 1-2-1l.5-.4a14.2 14.2 0 0 0 12.2 0l.5.4c-.6.4-1.3.7-2 1l1.3 2.1a19.8 19.8 0 0 0 6-3c.5-5.2-.8-9.7-3.8-13.7ZM8.5 15.4c-1.2 0-2.2-1.1-2.2-2.4s1-2.4 2.2-2.4 2.2 1.1 2.2 2.4-1 2.4-2.2 2.4Zm7 0c-1.2 0-2.2-1.1-2.2-2.4s1-2.4 2.2-2.4 2.2 1.1 2.2 2.4-1 2.4-2.2 2.4Z"/></svg>
                {{ __('login.button') }}
            </a>
            <p class="mt-3 text-xs text-dc-faint">{{ __('login.privacy') }}</p>
        </div>

        <div class="rounded-xl bg-dc-700 p-5 shadow-2xl ring-1 ring-black/30 md:rotate-1">
            <div class="mb-4 flex items-center gap-2 border-b border-dc-500 pb-3 text-sm font-semibold text-dc-header">
                <span class="text-xl text-dc-muted">#</span> {{ __('login.sample_channel') }}
            </div>
            <div class="flex gap-4">
                @if ($botAvatar)
                    <img src="{{ $botAvatar }}" alt="" class="size-10 shrink-0 rounded-full">
                @else
                    <div class="grid size-10 shrink-0 place-items-center rounded-full bg-accent text-sm font-bold text-on-accent">t</div>
                @endif
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-dc-header">Toxbot</span>
                        <span class="rounded-[3px] bg-blurple px-1 text-[10px] leading-4 font-semibold text-white">APP</span>
                        <span class="text-xs text-dc-muted">{{ __('guild.today_at', ['time' => '20:15']) }}</span>
                    </div>
                    <p class="text-dc-text">{!! __('login.sample_message', ['mention' => '<span class="dc-mention">@newtox</span>', 'server' => '<strong>Toxic Lounge</strong>']) !!}</p>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
