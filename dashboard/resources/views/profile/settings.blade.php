<x-layouts.app :title="__('menu.preferences')">
    <div class="space-y-6">
        @include('profile.header', ['title' => __('menu.preferences'), 'description' => __('profile.intro')])
        <livewire:profile-settings />
    </div>
</x-layouts.app>
