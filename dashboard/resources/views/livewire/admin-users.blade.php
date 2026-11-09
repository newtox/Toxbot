<div class="space-y-6">
    <form novalidate wire:submit="open" class="card space-y-3">
        <label class="label" for="admin-user-search">{{ __('admin.users_search_label') }}</label>
        <div class="flex flex-wrap gap-3">
            <input id="admin-user-search" type="search" wire:model.live.debounce.300ms="search" autocomplete="off"
                   placeholder="{{ __('admin.users_search_placeholder') }}" class="input min-w-0 flex-1 basis-56">
            <button type="submit" class="btn-primary max-sm:w-full">{{ __('admin.users_open') }}</button>
        </div>
        @error('search') <p class="text-sm text-dc-red">{{ $message }}</p> @enderror
        @if ($matches !== [])
            <ul class="divide-y divide-black/25 overflow-hidden rounded-lg bg-dc-900">
                @foreach ($matches as $match)
                    <li wire:key="match-{{ $match['id'] }}">
                        <a href="{{ route('admin.user', $match['id']) }}" class="flex items-center gap-3 px-3 py-2 hover:bg-dc-700">
                            <img src="{{ $match['avatar'] }}" alt="" class="size-8 shrink-0 rounded-full" loading="lazy">
                            <span class="min-w-0 flex-1 truncate text-sm">{{ $match['name'] }} <span class="text-dc-faint">{{ '@'.$match['username'] }}</span></span>
                            <span class="shrink-0 font-mono text-xs text-dc-faint">{{ $match['id'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @elseif (mb_strlen(trim($search)) >= 2 && ! ctype_digit(trim($search)))
            <p class="text-sm text-dc-muted">{{ __('admin.users_search_empty') }}</p>
        @endif
    </form>

    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold text-dc-header">{{ __('admin.active_title') }}</h2>
            <p class="text-sm text-dc-muted">{{ __('admin.active_intro') }}</p>
            <p class="mt-1 text-sm text-dc-faint tabular-nums">{{ __('admin.active_total', ['count' => number_format($total, 0, '', "\u{00A0}"), 'users' => number_format($users, 0, '', "\u{00A0}")]) }}</p>
        </div>

        @if ($entries->isEmpty())
            <div class="card text-center text-dc-muted">{{ __('admin.active_empty') }}</div>
        @else
            <ol class="divide-y divide-black/25 overflow-hidden rounded-xl bg-dc-800">
                @foreach ($entries as $entry)
                    <li wire:key="cmd-{{ $entry['id'] }}">
                        <a href="{{ route('admin.user', $entry['id']) }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-dc-700">
                            <span class="w-8 shrink-0 text-center font-semibold tabular-nums text-dc-muted">#{{ $entry['rank'] }}</span>
                            <img src="{{ $entry['avatar'] }}" alt="" class="size-9 shrink-0 rounded-full" loading="lazy">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-dc-header">{{ $entry['name'] }}</p>
                                <p class="truncate text-xs text-dc-faint">{{ $entry['username'] ? '@'.$entry['username'] : $entry['id'] }}</p>
                            </div>
                            <div class="shrink-0 text-right text-sm tabular-nums">
                                <p class="font-medium text-dc-header">{{ __('admin.active_count', ['count' => number_format($entry['commands'], 0, '', "\u{00A0}")]) }}</p>
                                <p class="text-xs text-dc-muted">{{ __('leaderboard.level', ['level' => $entry['level']]) }} · {{ number_format($entry['credits'], 0, '', "\u{00A0}") }}&nbsp;{{ config('toxbot.currency') }}</p>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</div>
