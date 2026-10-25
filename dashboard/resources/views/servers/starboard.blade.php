<x-layouts.app :title="__('menu.section_starboard').' · '.$guild['name']" :guild="$guild" :guilds="$guilds">
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <x-guild-icon :guild="$guild" class="rounded-2xl" size="size-14" />
            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-dc-muted">{{ $guild['name'] }}</p>
                <h1 class="truncate text-2xl font-bold text-dc-header">{{ __('menu.section_starboard') }}</h1>
            </div>
        </div>
        <p class="text-sm text-dc-muted">
            @if ($threshold)
                {!! __('starboard.member_description', ['count' => $threshold, 'emoji' => $emoji]) !!}
            @else
                {{ __('starboard.not_set_up') }}
            @endif
        </p>
        <livewire:starboard-list :guild-id="$guild['id']" />
    </div>
</x-layouts.app>
