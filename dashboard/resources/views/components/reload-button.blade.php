@props(['label', 'action' => null, 'href' => null])

@if ($action)
    <button type="button" wire:click="{{ $action }}" wire:loading.attr="disabled" wire:target="{{ $action }}" {{ $attributes->class('btn-secondary max-sm:w-full') }}>
        <x-icon name="refresh" class="size-4" wire:loading.class="animate-spin" wire:target="{{ $action }}" />
        {{ $label }}
    </button>
@elseif ($href)
    <a href="{{ $href }}" x-data="{ busy: false }" x-on:click="busy = true" x-bind:aria-busy="busy" {{ $attributes->class('btn-secondary max-sm:w-full') }}>
        <x-icon name="refresh" class="size-4" x-bind:class="busy && 'animate-spin'" />
        {{ $label }}
    </a>
@else
    <button type="button" x-data="{ busy: false }" x-on:click="busy = true; window.location.reload()" x-bind:disabled="busy" {{ $attributes->class('btn-secondary max-sm:w-full') }}>
        <x-icon name="refresh" class="size-4" x-bind:class="busy && 'animate-spin'" />
        {{ $label }}
    </button>
@endif
