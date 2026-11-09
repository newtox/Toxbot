<x-layouts.app :title="__('menu.admin')">
    <div class="space-y-8">
        <div>
            <h1 class="text-2xl font-bold text-dc-header">{{ __('admin.title') }}</h1>
            <p class="mt-1 text-dc-muted">{{ __('admin.intro') }}</p>
        </div>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ($stats as $stat)
                @php($tag = isset($stat['route']) ? 'a' : 'div')
                <{{ $tag }} @if (isset($stat['route'])) href="{{ route($stat['route']) }}" @endif
                    @class(['tile', 'transition hover:brightness-125' => isset($stat['route']), 'border-dc-red/60! from-dc-red/15! to-dc-red/[0.03]!' => $stat['alert'] ?? false])>
                    <div class="text-2xl font-bold text-dc-header tabular-nums">{{ $stat['value'] !== null ? number_format($stat['value'], 0, '', "\u{00A0}") : '–' }}</div>
                    <div class="text-sm text-dc-muted">{{ $stat['label'] }}</div>
                </{{ $tag }}>
            @endforeach
        </div>

        <section>
            <h2 class="mb-3 text-lg font-semibold text-dc-header">{{ __('admin.overview_sections') }}</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($sections as $section)
                    <a href="{{ route($section['route']) }}" class="group card flex gap-4 transition hover:bg-dc-700 hover:ring-accent/50">
                        <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-dc-600 text-accent-fg">
                            <x-icon :name="$section['icon']" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-dc-header">{{ $section['title'] }}</span>
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-xs font-semibold',
                                    'bg-dc-red/20 text-dc-red' => $section['alert'] ?? false,
                                    'bg-dc-600 text-dc-muted' => ! ($section['alert'] ?? false),
                                ])>{{ $section['badge'] }}</span>
                            </div>
                            <p class="mt-1 line-clamp-2 text-sm text-dc-muted">{{ $section['description'] }}</p>
                        </div>
                        <span class="self-center text-dc-muted transition group-hover:translate-x-0.5 group-hover:text-dc-header">→</span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.app>
