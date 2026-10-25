<x-layouts.app :title="__('menu.section_data')">
    <div class="space-y-6">
        @include('profile.header', ['title' => __('menu.section_data'), 'description' => __('data.account_description')])
        <livewire:delete-account />
    </div>
</x-layouts.app>
