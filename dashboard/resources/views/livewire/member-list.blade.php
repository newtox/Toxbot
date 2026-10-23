<div class="space-y-4">
    <div class="flex flex-wrap gap-2">
        <div class="relative min-w-60 flex-1">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-dc-faint" />
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="{{ __('moderation.search_placeholder') }}"
                   class="input pl-9" aria-label="{{ __('moderation.search_placeholder') }}">
        </div>
        <select wire:model.live="filter" class="input w-auto" aria-label="{{ __('moderation.filter') }}">
            @foreach (['all', 'cases', 'timeout', 'bots'] as $option)
                <option value="{{ $option }}">{{ __('moderation.filter_'.$option) }}</option>
            @endforeach
        </select>
    </div>

    @if ($error)
        <p class="rounded-lg border-l-4 border-dc-red bg-dc-800 px-4 py-3 text-sm">{{ $error }}</p>
    @endif

    @if ($truncated)
        <p class="text-sm text-dc-faint">{{ __('moderation.members_truncated', ['count' => \App\Services\Discord::MEMBER_LIMIT]) }}</p>
    @endif

    <div class="overflow-hidden rounded-xl bg-dc-800 ring-1 ring-white/5" wire:loading.class="opacity-60">
        @forelse ($members as $member)
            <a href="{{ route('servers.member', [$guildId, $member['id']]) }}" wire:key="m-{{ $member['id'] }}"
               class="flex items-center gap-3 border-b border-white/5 px-4 py-3 transition last:border-0 hover:bg-dc-700">
                <img src="{{ $member['avatar'] }}" alt="" class="size-10 shrink-0 rounded-full" loading="lazy">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="truncate font-medium text-dc-header">{{ $member['name'] }}</span>
                        @if ($member['bot'])
                            <span class="rounded bg-blurple px-1.5 text-[0.65rem] font-bold text-white uppercase">Bot</span>
                        @endif
                        @if ($member['timeout_until'])
                            <span class="rounded-full bg-[#f57c00]/20 px-2 py-0.5 text-xs font-medium text-[#ffb366]">{{ __('moderation.timed_out') }}</span>
                        @endif
                    </div>
                    <div class="truncate text-xs text-dc-muted">
                        {{ $member['username'] }}
                        @if ($member['joined_at'])
                            · {{ __('moderation.joined', ['date' => $member['joined_at']->locale(app()->getLocale())->isoFormat('ll')]) }}
                        @endif
                    </div>
                </div>
                <div class="hidden max-w-64 flex-wrap justify-end gap-1 md:flex">
                    @foreach (array_slice($member['roles'], 0, 3) as $role)
                        <span class="inline-flex items-center gap-1 rounded-full bg-dc-900 px-2 py-0.5 text-xs text-dc-text">
                            <span class="size-2 rounded-full" style="background: {{ $role['color'] ?? '#99aab5' }}"></span>{{ $role['name'] }}
                        </span>
                    @endforeach
                    @if (count($member['roles']) > 3)
                        <span class="rounded-full bg-dc-900 px-2 py-0.5 text-xs text-dc-muted">+{{ count($member['roles']) - 3 }}</span>
                    @endif
                </div>
                @if ($member['cases'] > 0)
                    <span class="shrink-0 rounded-full bg-dc-red/15 px-2 py-0.5 text-xs font-semibold text-dc-red">{{ trans_choice('moderation.cases_count', $member['cases']) }}</span>
                @endif
            </a>
        @empty
            <p class="px-4 py-8 text-center text-sm text-dc-muted">{{ __('moderation.no_members') }}</p>
        @endforelse
    </div>

    @if ($pages > 1)
        <div class="flex items-center justify-between text-sm">
            <button type="button" wire:click="previousPage" @disabled($pageNumber <= 1) class="btn-secondary">←</button>
            <span class="text-dc-muted tabular-nums">{{ __('moderation.page', ['page' => $pageNumber, 'pages' => $pages, 'total' => $total]) }}</span>
            <button type="button" wire:click="nextPage" @disabled($pageNumber >= $pages) class="btn-secondary">→</button>
        </div>
    @endif
</div>
