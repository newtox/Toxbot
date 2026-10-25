@foreach ($groups as $group)
    @if ($group['collapsible'])
        <div x-data="{ open: toxbot.navGroupOpen(@js($group['key']), @js($group['active'])) }" class="space-y-1.5">
            <button type="button" x-on:click="open = !open; toxbot.rememberNavGroup(@js($group['key']), open)" x-bind:aria-expanded="open"
                    class="nav-heading flex w-full items-center gap-1 rounded-md pt-1 text-left transition hover:text-dc-muted">
                <span class="flex-1">{{ $group['label'] }}</span>
                <x-icon name="chevron-down" class="size-3.5 transition-transform" x-bind:class="open || '-rotate-90'" />
            </button>
            <div x-show="open" @unless ($group['active']) x-cloak @endunless x-collapse.duration.150ms class="space-y-1.5">
                @foreach ($group['items'] as $item)
                    <a href="{{ $item['url'] }}" @class(['nav-item', 'nav-item-active' => $item['active']])>
                        <x-icon :name="$item['icon']" />{{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @else
        <div class="space-y-1.5">
            <p class="nav-heading">{{ $group['label'] }}</p>
            @foreach ($group['items'] as $item)
                <a href="{{ $item['url'] }}" @class(['nav-item', 'nav-item-active' => $item['active']])>
                    <x-icon :name="$item['icon']" />{{ $item['label'] }}
                </a>
            @endforeach
        </div>
    @endif
@endforeach
