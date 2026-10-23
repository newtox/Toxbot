<x-layouts.app :title="__('moderation.members_title').' · '.$guild['name']" :guild="$guild" :guilds="$guilds">
    <livewire:member-profile :guild-id="$guild['id']" :user-id="$userId" />
</x-layouts.app>
