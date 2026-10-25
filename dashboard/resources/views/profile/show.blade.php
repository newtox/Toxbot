<x-layouts.app :title="__('menu.profile')">
    <div class="space-y-6">
        @include('profile.header', ['title' => __('menu.profile')])
        <livewire:profile-overview />
    </div>
</x-layouts.app>
