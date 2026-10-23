<div class="space-y-4">
    <div class="flex flex-wrap gap-2">
        <div class="relative min-w-60 flex-1">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-dc-faint" />
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="{{ __('moderation.modlog_search') }}"
                   class="input pl-9" aria-label="{{ __('moderation.modlog_search') }}">
        </div>
        <select wire:model.live="type" class="input w-auto" aria-label="{{ __('moderation.filter') }}">
            <option value="">{{ __('moderation.filter_all_types') }}</option>
            @foreach (\App\Models\ModCase::TYPES as $option)
                <option value="{{ $option }}">{{ __('moderation.type_'.$option) }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-xl bg-dc-800 ring-1 ring-white/5" wire:loading.class="opacity-60">
        <table class="w-full min-w-[42rem] text-left text-sm">
            <thead class="border-b border-white/5 text-xs text-dc-faint">
                <tr>
                    <th class="px-4 py-3 font-semibold">{{ __('moderation.col_case') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('moderation.col_user') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('moderation.col_reason') }}</th>
                    <th class="px-4 py-3 font-semibold">{{ __('moderation.col_moderator') }}</th>
                    <th class="px-4 py-3 text-right font-semibold">{{ __('moderation.col_date') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse ($cases as $case)
                    <tr wire:key="case-{{ $case->id }}" class="align-top hover:bg-dc-700/50">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="font-semibold text-dc-header">#{{ $case->number }}</span>
                            <span class="ml-1.5 inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium"
                                  style="background: {{ \App\Models\ModCase::COLORS[$case->type] }}26; color: {{ \App\Models\ModCase::COLORS[$case->type] }}">
                                {{ __('moderation.type_'.$case->type) }}
                            </span>
                            @if ($case->duration)
                                <div class="mt-1 text-xs text-dc-muted">{{ \App\Support\Duration::format($case->duration) }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('servers.member', [$guildId, $case->user_id]) }}" class="font-medium text-dc-header hover:underline">{{ $case->user_name ?? $case->user_id }}</a>
                        </td>
                        <td class="max-w-80 px-4 py-3 text-dc-text">
                            <span class="line-clamp-2">{{ $case->reason ?? __('moderation.no_reason') }}</span>
                            @if ($case->type === 'warn' && ! $case->active)
                                <span class="mt-1 inline-block rounded-full bg-dc-600 px-2 text-xs text-dc-muted">{{ __('moderation.revoked') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-dc-muted">
                            {{ $case->moderator_name ?? '–' }}
                            <div class="text-xs text-dc-faint">{{ __('moderation.source_'.$case->source) }}</div>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap text-dc-muted">{{ $case->created_at->locale(app()->getLocale())->isoFormat('lll') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-dc-muted">{{ __('moderation.no_cases') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($pages > 1)
        <div class="flex items-center justify-between text-sm">
            <button type="button" wire:click="previousPage" @disabled($pageNumber <= 1) class="btn-secondary">←</button>
            <span class="text-dc-muted tabular-nums">{{ __('moderation.page', ['page' => $pageNumber, 'pages' => $pages, 'total' => $total]) }}</span>
            <button type="button" wire:click="nextPage" @disabled($pageNumber >= $pages) class="btn-secondary">→</button>
        </div>
    @endif
</div>
