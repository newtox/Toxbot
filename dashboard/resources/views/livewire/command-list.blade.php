<div class="space-y-4">
    <div class="relative">
        <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-dc-faint" />
        <input type="search" wire:model.live.debounce.250ms="search" placeholder="{{ __('info.commands_search') }}"
               class="input pl-9" aria-label="{{ __('info.commands_search') }}">
    </div>

    @if ($error)
        <p class="rounded-lg border-l-4 border-dc-red bg-dc-800 px-4 py-3 text-sm">{{ $error }}</p>
    @endif

    <p class="text-sm text-dc-faint">{{ __('info.commands_count', ['shown' => count($commands), 'total' => $total]) }}</p>

    <div class="space-y-3" wire:loading.class="opacity-60">
        @forelse ($commands as $command)
            <section wire:key="cmd-{{ $command['name'] }}" class="card space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="font-mono text-lg font-semibold text-dc-header">/{{ $command['name'] }}</h2>
                    @if ($command['restricted'])
                        <span class="rounded-full bg-dc-600 px-2 py-0.5 text-xs font-medium text-dc-muted">{{ __('info.commands_restricted') }}</span>
                    @endif
                </div>
                <p class="text-sm text-dc-muted">{{ $command['description'] }}</p>

                @if (count($command['entries']) > 1 || $command['entries'][0]['options'])
                    <div class="space-y-2">
                        @foreach ($command['entries'] as $entry)
                            <div class="rounded-lg bg-dc-900 p-3">
                                @if (count($command['entries']) > 1)
                                    <div class="font-mono text-sm font-semibold text-dc-header">{{ $entry['path'] }}</div>
                                    <p class="mt-0.5 text-sm text-dc-muted">{{ $entry['description'] }}</p>
                                @endif
                                @if ($entry['options'])
                                    <div @class(['overflow-x-auto', 'mt-2' => count($command['entries']) > 1])>
                                        <table class="w-full min-w-[28rem] text-left text-sm">
                                            <thead class="text-xs text-dc-faint">
                                                <tr class="border-b border-white/10">
                                                    <th class="py-1.5 pr-3 font-medium">{{ __('info.option_name') }}</th>
                                                    <th class="py-1.5 pr-3 font-medium">{{ __('info.option_type') }}</th>
                                                    <th class="py-1.5 pr-3 font-medium">{{ __('info.option_required') }}</th>
                                                    <th class="py-1.5 font-medium">{{ __('info.option_description') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($entry['options'] as $option)
                                                    <tr class="border-b border-white/5 last:border-0">
                                                        <td class="py-1.5 pr-3 font-mono text-dc-header">{{ $option['name'] }}</td>
                                                        <td class="py-1.5 pr-3"><span class="rounded bg-dc-700 px-1.5 py-0.5 font-mono text-xs text-dc-text">{{ __('info.type_'.$option['type']) }}</span></td>
                                                        <td class="py-1.5 pr-3 text-dc-text">{{ $option['required'] ? __('info.yes') : __('info.no') }}</td>
                                                        <td class="py-1.5 text-dc-muted">{{ $option['description'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @empty
            <p class="card text-center text-sm text-dc-muted">{{ __('info.commands_empty') }}</p>
        @endforelse
    </div>
</div>
