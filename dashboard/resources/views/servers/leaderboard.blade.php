<x-layouts.app :title="__('leaderboard.title').' · '.$guild['name']" :guild="$guild" :guilds="$guilds">
    <div class="space-y-6">
        <div>
            <p class="text-sm font-medium text-dc-muted">{{ $guild['name'] }}</p>
            <h1 class="text-2xl font-bold text-dc-header">{{ __('leaderboard.title') }}</h1>
            <p class="mt-1 text-sm text-dc-muted">{{ __('leaderboard.description') }}</p>
        </div>
        <livewire:leaderboard :guild-id="$guild['id']" />
    </div>
</x-layouts.app>
