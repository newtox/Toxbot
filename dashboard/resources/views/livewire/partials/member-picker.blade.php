<div class="space-y-3">
    @if ($pickGuilds === [])
        <p class="text-sm text-dc-muted">{{ __('credits.no_servers') }}</p>
    @else
        <div class="grid gap-3 sm:grid-cols-3">
            <div>
                <label class="label" for="{{ $pickerId }}-guild">{{ __('credits.server') }}</label>
                <select id="{{ $pickerId }}-guild" wire:model.live="pickGuild" class="input">
                    @foreach ($pickGuilds as $guild)
                        <option value="{{ $guild['id'] }}">{{ $guild['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="label" for="{{ $pickerId }}-search">{{ __('credits.recipient') }}</label>
                @if ($picked !== [])
                    <div class="flex items-center gap-2.5 rounded-lg bg-dc-900 px-3 py-2">
                        <img src="{{ $picked['avatar'] }}" alt="" class="size-7 shrink-0 rounded-full">
                        <span class="min-w-0 flex-1 truncate text-sm">{{ $picked['name'] }} <span class="text-dc-faint">{{ '@'.$picked['username'] }}</span></span>
                        <button type="button" wire:click="clearPick" class="rounded-full p-1 text-dc-muted hover:bg-white/10 hover:text-dc-header" aria-label="{{ __('credits.clear') }}">
                            <x-icon name="close" class="size-4" />
                        </button>
                    </div>
                @else
                    <div class="relative">
                        <input id="{{ $pickerId }}-search" type="search" wire:model.live.debounce.300ms="pickSearch" autocomplete="off"
                               placeholder="{{ __('credits.search') }}" class="input">
                        @if ($pickMatches !== [])
                            <ul class="absolute inset-x-0 top-full z-20 mt-1 overflow-hidden rounded-lg bg-dc-800 shadow-xl ring-1 ring-black/40">
                                @foreach ($pickMatches as $match)
                                    <li>
                                        <button type="button" wire:click="pickMember('{{ $match['id'] }}')"
                                                class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm hover:bg-dc-700">
                                            <img src="{{ $match['avatar'] }}" alt="" class="size-7 shrink-0 rounded-full" loading="lazy">
                                            <span class="truncate">{{ $match['name'] }} <span class="text-dc-faint">{{ '@'.$match['username'] }}</span></span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
                @error('picked') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>
        </div>
    @endif
</div>
