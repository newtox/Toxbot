<x-layouts.app :title="__('menu.section_'.$section).' · '.$guild['name']" :guild="$guild" :guilds="$guilds">
    <livewire:guild-settings :guild="$guild" :section="$section" />
</x-layouts.app>
