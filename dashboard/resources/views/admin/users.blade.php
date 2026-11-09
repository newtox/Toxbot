<x-layouts.app :title="__('admin.users_page_title').' · '.__('menu.admin')">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-dc-header">{{ __('admin.users_page_title') }}</h1>
            <p class="mt-1 text-dc-muted">{{ __('admin.users_page_description') }}</p>
        </div>
        <livewire:admin-users />
    </div>
</x-layouts.app>
