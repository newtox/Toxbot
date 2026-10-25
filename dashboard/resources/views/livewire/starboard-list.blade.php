<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-3">
        <p class="flex-1 text-sm text-dc-muted">{{ trans_choice('starboard.count', $total) }}</p>
        <div class="inline-flex rounded-lg bg-dc-900 p-1 ring-1 ring-white/5" role="group" aria-label="{{ __('starboard.sort') }}">
            @foreach (\App\Livewire\StarboardList::SORTS as $option)
                <button type="button" wire:click="$set('sort', '{{ $option }}')" aria-pressed="{{ $sort === $option ? 'true' : 'false' }}"
                        @class(['rounded-md px-3 py-1.5 text-sm font-medium transition', 'bg-dc-600 text-dc-header' => $sort === $option, 'text-dc-muted hover:text-dc-header' => $sort !== $option])>
                    {{ __('starboard.sort_'.$option) }}
                </button>
            @endforeach
        </div>
    </div>

    <div class="grid items-start gap-4 md:grid-cols-2" wire:loading.class="opacity-60">
        @forelse ($posts as $post)
            <article wire:key="star-{{ $post->id }}" class="flex flex-col gap-3 rounded-xl bg-dc-900 p-4 ring-1 ring-white/5">
                <header class="flex items-center gap-3">
                    <img src="{{ $post->author_avatar ?: 'https://cdn.discordapp.com/embed/avatars/0.png' }}" alt="" class="size-9 shrink-0 rounded-full" loading="lazy">
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-medium text-dc-header">{{ $post->author_name ?? __('guild.unknown_user') }}</div>
                        <div class="truncate text-xs text-dc-faint">
                            @isset($channels[$post->channel_id]) #{{ $channels[$post->channel_id] }} · @endisset
                            {{ \App\Support\Timezone::format($post->message_created_at ?? $post->created_at) }}
                        </div>
                    </div>
                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-dc-yellow/15 px-2.5 py-1 text-sm font-semibold text-dc-header tabular-nums">
                        <x-emoji :code="$emoji" />{{ $post->stars }}
                    </span>
                </header>
                @if ($post->content)
                    <p class="line-clamp-6 text-sm break-words whitespace-pre-line text-dc-text">{{ $post->content }}</p>
                @endif
                @if ($post->image_url)
                    <img src="{{ $post->image_url }}" alt="" class="max-h-72 w-full rounded-lg bg-dc-950 object-contain" loading="lazy">
                @endif
                <a href="{{ $post->jumpUrl() }}" target="_blank" rel="noopener" class="mt-auto self-start text-sm text-dc-link hover:underline">{{ __('starboard.jump') }} ↗</a>
            </article>
        @empty
            <p class="rounded-xl bg-dc-900 px-4 py-8 text-center text-sm text-dc-muted md:col-span-2">{{ __('starboard.empty') }}</p>
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
