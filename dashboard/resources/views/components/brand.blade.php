@props(['botAvatar' => null])

<a href="{{ route('home') }}" class="flex items-center gap-2.5">
    @if ($botAvatar)
        <img src="{{ $botAvatar }}" alt="" class="size-9 rounded-full ring-2 ring-accent">
    @else
        <span class="grid size-9 place-items-center rounded-full bg-accent text-base font-bold text-on-accent">t</span>
    @endif
    <span class="text-xl font-bold tracking-tight text-accent-fg">toxbot</span>
</a>
