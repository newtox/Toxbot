@props(['guild', 'size' => 'size-12'])

@if ($guild['icon_url'])
    <img src="{{ $guild['icon_url'] }}" alt="" {{ $attributes->class([$size, 'shrink-0 object-cover']) }}>
@else
    @php
        $initials = collect(preg_split('/\s+/u', trim($guild['name'])))
            ->filter()
            ->map(fn ($word) => mb_substr($word, 0, 1))
            ->take(3)
            ->implode('');
    @endphp
    <div {{ $attributes->class([$size, 'grid shrink-0 place-items-center bg-dc-600 text-sm font-medium text-dc-header']) }}>
        {{ $initials }}
    </div>
@endif
