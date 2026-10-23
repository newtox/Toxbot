<x-layouts.app :title="__('protection.verify_page_title')">
    @if ($status === 'pending' && $siteKey)
        @push('head')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endpush
    @endif

    <div class="mx-auto max-w-md py-6">
        <section class="card space-y-5 text-center">
            <div class="flex flex-col items-center gap-3">
                @if ($guildIcon)
                    <img src="{{ $guildIcon }}" alt="" class="size-20 rounded-3xl">
                @else
                    <span class="grid size-20 place-items-center rounded-3xl bg-dc-600 text-2xl font-bold text-dc-header">{{ mb_substr($guildName ?? '?', 0, 1) }}</span>
                @endif
                <div>
                    <p class="text-sm text-dc-muted">{{ __('protection.verify_page_title') }}</p>
                    <h1 class="text-2xl font-bold text-dc-header">{{ $guildName ?? __('protection.verify_unknown_server') }}</h1>
                </div>
            </div>

            @if ($error)
                <p class="rounded-lg border-l-4 border-dc-red bg-dc-900 px-4 py-3 text-left text-sm" role="alert">{{ $error }}</p>
            @endif

            @switch($status)
                @case('pending')
                    <p class="text-sm text-dc-muted">{{ __('protection.verify_intro', ['server' => $guildName]) }}</p>
                    <form method="POST" action="{{ route('verify.submit', $guildId) }}" class="space-y-4">
                        @csrf
                        @if ($siteKey)
                            <div class="cf-turnstile flex justify-center" data-sitekey="{{ $siteKey }}" data-theme="dark" data-language="{{ app()->getLocale() }}"></div>
                        @endif
                        <button type="submit" class="btn-primary w-full">{{ __('protection.verify_button') }}</button>
                    </form>
                    @break
                @case('done')
                    <x-icon name="check" class="mx-auto size-10 text-accent-fg" />
                    <p class="text-dc-header">{{ __('protection.verify_done', ['server' => $guildName]) }}</p>
                    <a href="https://discord.com/channels/{{ $guildId }}" class="btn-primary w-full">{{ __('protection.verify_open_discord') }}</a>
                    @break
                @case('verified')
                    <p class="text-dc-header">{{ __('protection.verify_already', ['server' => $guildName]) }}</p>
                    <a href="https://discord.com/channels/{{ $guildId }}" class="btn-secondary w-full">{{ __('protection.verify_open_discord') }}</a>
                    @break
                @case('not_member')
                    <p class="text-dc-muted">{{ __('protection.verify_not_member', ['server' => $guildName]) }}</p>
                    @break
                @default
                    <p class="text-dc-muted">{{ __('protection.verify_disabled') }}</p>
            @endswitch
        </section>
    </div>
</x-layouts.app>
