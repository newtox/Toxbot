<x-layouts.app :title="__('admin.users_title').' · '.__('menu.admin')">
    <div class="space-y-6">
        <a href="{{ route('admin.users') }}" class="inline-flex items-center gap-1 text-sm text-dc-muted hover:text-dc-header">
            <x-icon name="chevron-left" class="size-4" />{{ __('admin.users_back') }}
        </a>
        <livewire:admin-economy :user-id="$userId" />
    </div>
</x-layouts.app>
