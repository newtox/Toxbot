<div class="space-y-4">
    @if ($rules->isEmpty())
        <p class="text-sm text-dc-faint">{{ __('moderation.rules_empty') }}</p>
    @else
        <ul class="divide-y divide-white/5 rounded-lg bg-dc-900">
            @foreach ($rules as $rule)
                <li wire:key="rule-{{ $rule->id }}" class="flex items-center gap-3 px-3 py-2.5 text-sm">
                    <span class="flex-1">
                        {!! __('moderation.rule_line', [
                            'warns' => '<strong class="text-dc-header">'.$rule->warns.'</strong>',
                            'action' => '<strong class="text-dc-header">'.e(__('moderation.action_'.$rule->action)).'</strong>',
                        ]) !!}
                        @if ($rule->duration)
                            <span class="text-dc-muted">· {{ \App\Support\Duration::format($rule->duration) }}</span>
                        @endif
                    </span>
                    <button type="button" wire:click="remove({{ $rule->id }})" class="btn px-3 py-1 text-dc-red hover:bg-dc-red hover:text-white">{{ __('streams.remove') }}</button>
                </li>
            @endforeach
        </ul>
    @endif

    <form novalidate wire:submit="add" class="grid gap-3 sm:grid-cols-[auto_1fr_1fr_auto] sm:items-end">
        <div>
            <label class="label" for="rule-warns">{{ __('moderation.rule_warns') }}</label>
            <input id="rule-warns" type="number" min="1" max="50" wire:model="warns" class="input w-24">
        </div>
        <div>
            <label class="label" for="rule-action">{{ __('moderation.rule_action') }}</label>
            <select id="rule-action" wire:model.live="action" class="input">
                @foreach (\App\Models\WarnRule::ACTIONS as $option)
                    <option value="{{ $option }}">{{ __('moderation.action_'.$option) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label" for="rule-duration">{{ __('moderation.rule_duration') }}</label>
            <input id="rule-duration" type="text" wire:model="duration" @disabled($action === 'kick')
                   placeholder="{{ $action === 'ban' ? __('moderation.permanent') : '1h' }}" class="input disabled:opacity-50">
        </div>
        <button type="submit" class="btn-primary">{{ __('streams.add') }}</button>
    </form>
    @error('warns') <p class="text-sm text-dc-red">{{ $message }}</p> @enderror
    @error('action') <p class="text-sm text-dc-red">{{ $message }}</p> @enderror
    @error('duration') <p class="text-sm text-dc-red">{{ $message }}</p> @enderror
</div>
