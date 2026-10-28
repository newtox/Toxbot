<x-layouts.app :title="__('menu.premium')">
    @php($isPremium = app(\App\Services\Premium::class)->isPremium((string) auth()->id()))
    <div class="space-y-6">
        @include('profile.header', ['title' => __('menu.premium')])

        <section class="card space-y-4">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="flex-1 text-lg font-semibold text-dc-header">{{ __('home.premium_user_t') }}</h2>
                <span @class([
                    'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                    'bg-dc-yellow text-dc-950' => $isPremium,
                    'bg-dc-600 text-dc-muted' => ! $isPremium,
                ])>{{ $isPremium ? __('profile.premium_active') : __('profile.premium_inactive') }}</span>
            </div>
            <ul class="space-y-2 text-sm text-dc-text">
                @foreach (range(1, 4) as $i)
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 size-4 text-accent-fg" />{{ __('home.premium_user_'.$i) }}</li>
                @endforeach
            </ul>
            @unless ($isPremium)
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    @if ($storeUrl = \App\Services\Premium::storeUrl())
                        <a href="{{ $storeUrl }}" target="_blank" rel="noopener" class="btn bg-dc-yellow font-semibold text-dc-950 hover:brightness-110">
                            {{ __('home.premium_buy') }} <x-icon name="external" class="size-4" />
                        </a>
                    @endif
                    <p class="min-w-0 flex-1 basis-64 text-sm text-dc-faint">{{ __('home.premium_hint') }}</p>
                </div>
            @endunless
        </section>

        <livewire:premium-server-picker />
    </div>
</x-layouts.app>
