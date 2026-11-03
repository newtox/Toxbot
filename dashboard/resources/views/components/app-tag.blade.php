@props(['verified' => false])
<span {{ $attributes->merge(['class' => 'inline-flex h-[15px] shrink-0 items-center gap-0.5 rounded-[3px] bg-blurple px-[5px] text-[10px] leading-none font-semibold text-white']) }}>
    @if ($verified)
        <svg viewBox="0 0 16 16" class="size-2.5" aria-hidden="true"><path fill="currentColor" d="M6.3 12.1 2.2 8l1.4-1.4 2.7 2.7 6.1-6.1 1.4 1.4z"/></svg>
    @endif
    APP
</span>
