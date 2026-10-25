<x-layouts.app :title="__('info.general_title')">
    @php
        $link = 'flex items-center gap-2.5 rounded-lg bg-dc-900 px-4 py-3 text-sm font-medium text-dc-header ring-1 ring-white/5 transition hover:bg-dc-700 hover:ring-accent/50';
    @endphp
    <div class="space-y-6">
        <section class="card flex flex-wrap items-center gap-5">
            <img src="{{ $avatar ?? 'https://cdn.discordapp.com/embed/avatars/0.png' }}" alt="" class="size-16 rounded-2xl">
            <div class="min-w-0 flex-1 basis-64">
                <h1 class="text-2xl font-bold text-dc-header">Toxbot</h1>
                <p class="text-sm text-dc-muted">{{ __('info.intro') }}</p>
            </div>
            <div class="flex flex-wrap gap-2 max-sm:w-full">
                <a href="{{ $inviteUrl }}" target="_blank" rel="noopener" class="btn-primary max-sm:flex-1">{{ __('info.invite') }}</a>
                <a href="{{ $topggUrl }}" target="_blank" rel="noopener" class="btn-secondary max-sm:flex-1">{{ __('info.vote') }}</a>
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-dc-header">{{ __('info.about_title') }}</h2>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                <a href="{{ $repository }}" target="_blank" rel="noopener" class="{{ $link }}"><x-icon name="github" class="size-4" />{{ __('info.repository') }}</a>
                <a href="{{ $repository }}/issues" target="_blank" rel="noopener" class="{{ $link }}"><x-icon name="alert" class="size-4" />{{ __('info.issues') }}</a>
                <a href="{{ route('info.commands') }}" class="{{ $link }}"><x-icon name="terminal" class="size-4" />{{ __('info.commands_title') }}</a>
                @if ($supportInvite)
                    <a href="{{ $supportInvite }}" target="_blank" rel="noopener" class="{{ $link }}"><x-icon name="users" class="size-4" />{{ __('info.support_server') }}</a>
                @endif
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-dc-header">{{ __('info.privacy_title') }}</h2>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                <a href="{{ $repository }}/blob/main/PRIVACY_POLICY.md" target="_blank" rel="noopener" class="{{ $link }}"><x-icon name="shield" class="size-4" />{{ __('home.footer_privacy') }}</a>
                <a href="{{ $repository }}/blob/main/TERMS_OF_SERVICE.md" target="_blank" rel="noopener" class="{{ $link }}"><x-icon name="list" class="size-4" />{{ __('home.footer_terms') }}</a>
                <a href="{{ route('profile.data') }}" class="{{ $link }}"><x-icon name="database" class="size-4" />{{ __('info.delete_data') }}</a>
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold text-dc-header">{{ __('info.contact_title') }}</h2>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @if ($contactEmail)
                    <a href="mailto:{{ $contactEmail }}" class="{{ $link }}"><x-icon name="mail" class="size-4" />{{ $contactEmail }}</a>
                @endif
                <a href="{{ $repository }}/issues/new" target="_blank" rel="noopener" class="{{ $link }}"><x-icon name="github" class="size-4" />{{ __('info.new_issue') }}</a>
            </div>
        </section>
    </div>
</x-layouts.app>
