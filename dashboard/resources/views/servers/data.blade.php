<x-layouts.app :title="__('menu.section_data').' · '.$guild['name']" :guild="$guild" :guilds="$guilds">
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <x-guild-icon :guild="$guild" class="rounded-2xl" size="size-14" />
            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-dc-muted">{{ $guild['name'] }}</p>
                <h1 class="truncate text-2xl font-bold text-dc-header">{{ __('menu.section_data') }}</h1>
            </div>
        </div>
        <p class="text-sm text-dc-muted">{{ __('data.server_description') }}</p>
        <livewire:server-data :guild-id="$guild['id']" :guild-name="$guild['name']" />
    </div>
</x-layouts.app>
