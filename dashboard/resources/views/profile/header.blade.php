<div class="flex items-center gap-4">
    <img src="{{ auth()->user()->avatar_url }}" alt="" class="size-14 rounded-full">
    <div class="min-w-0">
        <p class="truncate text-sm font-medium text-dc-muted">{{ auth()->user()->display_name }}</p>
        <h1 class="truncate text-2xl font-bold text-dc-header">{{ $title }}</h1>
    </div>
</div>
@isset($description)
    <p class="text-sm text-dc-muted">{{ $description }}</p>
@endisset
