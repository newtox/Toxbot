<x-layouts.app :title="__('admin.premium_title').' · '.__('menu.admin')">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-dc-header">{{ __('admin.title') }}</h1>
            <p class="mt-1 text-dc-muted">{{ __('admin.intro') }}</p>
        </div>
        <livewire:admin-premium />
    </div>
</x-layouts.app>
