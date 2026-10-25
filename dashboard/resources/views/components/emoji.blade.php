@props(['code'])

@if (preg_match('/^<(a?):(\w+):(\d+)>$/', $code, $m))
    <img src="https://cdn.discordapp.com/emojis/{{ $m[3] }}.{{ $m[1] ? 'gif' : 'webp' }}?size=48" alt=":{{ $m[2] }}:" {{ $attributes->class('inline-block size-[1.25em] object-contain align-[-0.2em]') }}>
@else
    <span {{ $attributes }}>{{ $code }}</span>
@endif
