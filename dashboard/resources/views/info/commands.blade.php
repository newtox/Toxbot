<x-layouts.app :title="__('info.commands_title')">
    <div class="space-y-6">
        @include('info.header', ['title' => __('info.commands_title'), 'description' => __('info.commands_description')])
        <livewire:command-list />
    </div>
</x-layouts.app>
