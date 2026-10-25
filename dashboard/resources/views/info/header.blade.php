<div>
    <p class="text-sm font-medium text-dc-muted">{{ __('menu.info') }}</p>
    <h1 class="text-2xl font-bold text-dc-header">{{ $title }}</h1>
    @isset($description)
        <p class="mt-1 text-sm text-dc-muted">{{ $description }}</p>
    @endisset
</div>
