<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-dc-header">{{ __('tags.title') }}</h1>
            <p class="mt-1 text-dc-muted">{{ __('tags.intro') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm text-dc-muted tabular-nums">{{ __('tags.count', ['count' => $tags->count(), 'limit' => $limit]) }}</span>
            @unless ($formOpen)
                <button type="button" wire:click="create" class="btn-primary" @disabled($tags->count() >= $limit)>{{ __('tags.new') }}</button>
            @endunless
        </div>
    </div>

    @if ($formOpen)
        <form novalidate wire:submit="save" class="card space-y-5">
            <h2 class="text-lg font-semibold text-dc-header">
                {{ $editingId ? __('tags.edit_title', ['name' => $name]) : __('tags.new') }}
            </h2>

            @unless ($editingId)
                <div>
                    <label class="label" for="tag-name">{{ __('tags.name') }}</label>
                    <input id="tag-name" type="text" wire:model="name" maxlength="32" autocomplete="off" spellcheck="false"
                           placeholder="newtox" class="input max-w-sm font-mono lowercase">
                    <p class="mt-2 text-xs text-dc-faint">{{ __('tags.name_hint') }}</p>
                    @error('name') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
                </div>
            @endunless

            <div>
                <div class="mb-2 flex items-end justify-between">
                    <label class="label mb-0" for="tag-content">{{ __('tags.content') }}</label>
                    <span class="text-xs tabular-nums"
                          :class="($wire.content ?? '').length > {{ \App\Models\Tag::MAX_LENGTH }} ? 'text-dc-red' : 'text-dc-faint'"
                          x-text="($wire.content ?? '').length + ' / {{ \App\Models\Tag::MAX_LENGTH }}'"></span>
                </div>
                <textarea id="tag-content" wire:model="content" rows="6" class="input resize-y font-mono text-sm leading-relaxed"></textarea>
                @error('content') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            </div>

            <fieldset @class(['space-y-4 rounded-lg bg-dc-900 p-4', 'opacity-60' => ! $premium]) @disabled(! $premium)>
                <legend class="sr-only">{{ __('tags.embed_title') }}</legend>
                <div class="flex items-start gap-3">
                    <label class="relative mt-0.5 inline-flex cursor-pointer items-center">
                        <input type="checkbox" wire:model="embed" class="peer sr-only">
                        <span class="h-6 w-10 rounded-full bg-dc-400 transition-colors peer-checked:bg-accent"></span>
                        <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                    </label>
                    <div>
                        <div class="font-medium text-dc-header">{{ __('tags.embed_title') }} <span class="ml-1 rounded-full bg-dc-yellow px-2 py-0.5 text-xs font-semibold text-dc-950">Premium</span></div>
                        <p class="text-sm text-dc-muted">{{ $premium ? __('tags.embed_description') : __('tags.embed_locked') }}</p>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="tag-color">{{ __('tags.embed_color') }}</label>
                        <input id="tag-color" type="text" wire:model="embedColor" maxlength="7" placeholder="#7289da" class="input font-mono uppercase">
                        @error('embedColor') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="tag-image">{{ __('tags.embed_image') }}</label>
                        <input id="tag-image" type="url" wire:model="embedImage" placeholder="https://i.imgur.com/…" class="input">
                        @error('embedImage') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
                    </div>
                </div>
            </fieldset>

            <div class="flex justify-end gap-2">
                <button type="button" wire:click="cancel" class="btn-ghost">{{ __('tags.cancel') }}</button>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="btn-primary">{{ __('common.save') }}</button>
            </div>
        </form>
    @endif

    @if ($tags->isEmpty())
        <div class="card text-center text-dc-muted">{{ __('tags.empty') }}</div>
    @else
        <div class="overflow-hidden rounded-xl bg-dc-800">
            <ul class="divide-y divide-black/25">
                @foreach ($tags as $tag)
                    <li wire:key="tag-{{ $tag->id }}" class="flex flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3">
                        <div class="min-w-0 flex-1 basis-56">
                            <div class="font-mono font-medium text-dc-header">{{ $tag->name }}
                                @if ($tag->embed)
                                    <span class="ml-1 rounded bg-dc-600 px-1.5 py-0.5 font-sans text-xs text-dc-muted">Embed</span>
                                @endif
                            </div>
                            <div class="truncate text-sm text-dc-muted">{{ \Illuminate\Support\Str::limit($tag->content, 120) }}</div>
                        </div>
                        <div class="text-sm text-dc-faint tabular-nums">{{ __('tags.uses', ['count' => number_format($tag->uses, 0, '', ' ')]) }}</div>
                        <div class="ml-auto flex shrink-0 gap-2">
                            <button type="button" wire:click="edit({{ $tag->id }})" class="btn-secondary px-3 py-1.5">{{ __('tags.edit') }}</button>
                            <button type="button" wire:click="delete({{ $tag->id }})" data-confirm="{{ __('tags.confirm_delete', ['name' => $tag->name]) }}"
                                    class="btn px-3 py-1.5 text-dc-red hover:bg-dc-red hover:text-white">{{ __('tags.delete') }}</button>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
