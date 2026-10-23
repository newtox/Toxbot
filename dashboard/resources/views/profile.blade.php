<x-layouts.app :title="__('menu.profile')">
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <img src="{{ auth()->user()->avatar_url }}" alt="" class="size-14 rounded-full">
            <div>
                <h1 class="text-2xl font-bold text-dc-header">{{ auth()->user()->display_name }}</h1>
                <p class="text-sm text-dc-muted">{{ __('profile.intro') }}</p>
            </div>
        </div>

        <livewire:profile-overview />
        <livewire:premium-server-picker />
        <livewire:profile-settings />
        <livewire:delete-account />
    </div>
</x-layouts.app>
