<x-layouts.app :title="__('menu.premium')">
    <div class="space-y-6">
        @include('profile.header', ['title' => __('menu.premium')])

        <livewire:premium-servers />
    </div>
</x-layouts.app>
