<div class="space-y-4">
    @if ($tags->isEmpty())
        <p class="text-sm text-dc-faint">{{ __('guild.server_tags_empty') }}</p>
    @else
        <ul class="divide-y divide-white/5 rounded-lg bg-dc-900">
            @foreach ($tags as $tag)
                <li wire:key="server-tag-{{ $tag->id }}" class="flex flex-wrap items-center gap-x-3 gap-y-2 px-3 py-2.5">
                    <div class="min-w-0 flex-1 basis-56">
                        <div class="truncate font-mono text-sm font-medium text-dc-header">{{ $tag->name }}</div>
                        <div class="truncate text-xs text-dc-muted">{{ \Illuminate\Support\Str::limit($tag->content, 120) }} · {{ __('tags.uses', ['count' => number_format($tag->uses, 0, '', ' ')]) }}</div>
                    </div>
                    <div class="ml-auto flex shrink-0 gap-2">
                        <button type="button" wire:click="edit({{ $tag->id }})" class="btn-secondary px-3 py-1.5">{{ __('tags.edit') }}</button>
                        <button type="button" wire:click="remove({{ $tag->id }})" data-confirm="{{ __('guild.server_tag_confirm_remove', ['name' => $tag->name]) }}"
                                class="btn px-3 py-1.5 text-dc-red hover:bg-dc-red hover:text-white">{{ __('tags.delete') }}</button>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
    <p class="text-xs text-dc-faint tabular-nums">
        {{ __('tags.count', ['count' => $tags->count(), 'limit' => $limit]) }}@unless ($premium) · {{ __('guild.server_tags_premium', ['max' => $premiumLimit]) }}@endunless
    </p>

    <form novalidate wire:submit="save" class="space-y-4 rounded-lg bg-dc-900 p-4">
        <h3 class="text-sm font-semibold text-dc-header">{{ $editingId ? __('guild.server_tag_edit') : __('guild.server_tag_new') }}</h3>
        <div>
            <label class="label" for="server-tag-name">{{ __('tags.name') }}</label>
            <input id="server-tag-name" type="text" wire:model="name" maxlength="32" autocomplete="off" spellcheck="false"
                   placeholder="rules" class="input max-w-sm font-mono lowercase">
            <p class="mt-2 text-xs text-dc-faint">{{ __('tags.name_hint') }}</p>
            @error('name') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
        </div>
        <div>
            <div class="mb-2 flex items-end justify-between">
                <label class="label mb-0" for="server-tag-content">{{ __('tags.content') }}</label>
                <span class="text-xs tabular-nums"
                      :class="($wire.content ?? '').length > {{ $maxLength }} ? 'text-dc-red' : 'text-dc-faint'"
                      x-text="($wire.content ?? '').length + ' / {{ $maxLength }}'"></span>
            </div>
            <textarea id="server-tag-content" x-ref="content" wire:model="content" rows="6" class="input resize-y font-mono text-sm leading-relaxed"></textarea>
            @error('content') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach (['&user', '&server', '&membercount'] as $token)
                    <button type="button" x-on:click="toxbot.insertAtCursor($refs.content, '{{ $token }}')"
                            class="rounded-full bg-dc-600 px-3 py-1 font-mono text-xs text-dc-text transition hover:bg-accent hover:text-on-accent">{{ $token }}</button>
                @endforeach
            </div>
            <p class="mt-2 text-xs text-dc-faint">{{ __('guild.server_tags_placeholders') }}</p>
        </div>
        <div class="flex justify-end gap-2">
            @if ($editingId)
                <button type="button" wire:click="cancel" class="btn-ghost">{{ __('tags.cancel') }}</button>
            @endif
            <button type="submit" class="btn-primary">{{ $editingId ? __('admin.save') : __('streams.add') }}</button>
        </div>
    </form>
</div>
