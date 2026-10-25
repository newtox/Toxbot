<div class="space-y-6" @if ($action && ! $action->finished()) wire:poll.2s @endif>
    <a href="{{ route('servers.members', $guildId) }}" class="inline-flex items-center gap-1 text-sm text-dc-muted hover:text-dc-header">← {{ __('moderation.members_title') }}</a>

    <section class="card flex flex-wrap items-center gap-5">
        <img src="{{ $member['avatar'] }}" alt="" class="size-20 rounded-full ring-4 ring-dc-700">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="truncate text-2xl font-bold text-dc-header">{{ $member['name'] }}</h1>
                @if ($member['bot'])
                    <span class="rounded bg-blurple px-1.5 text-xs font-bold text-white uppercase">Bot</span>
                @endif
                @if ($ban)
                    <span class="rounded-full bg-dc-red/20 px-2 py-0.5 text-xs font-semibold text-dc-red">{{ __('moderation.banned') }}</span>
                @elseif (! $onServer)
                    <span class="rounded-full bg-dc-600 px-2 py-0.5 text-xs font-semibold text-dc-muted">{{ __('moderation.not_on_server') }}</span>
                @endif
                @if ($member['timeout_until'])
                    <span class="rounded-full bg-[#f57c00]/20 px-2 py-0.5 text-xs font-semibold text-[#ffb366]">
                        {{ __('moderation.timed_out_until', ['date' => \App\Support\Timezone::format($member['timeout_until'])]) }}
                    </span>
                @endif
            </div>
            <p class="text-sm text-dc-muted">{{ $member['username'] }} · <span class="font-mono text-xs">{{ $member['id'] }}</span></p>
            @if ($ban && ! empty($ban['reason']))
                <p class="mt-1 text-sm text-dc-muted">{{ __('moderation.ban_reason') }}: {{ $ban['reason'] }}</p>
            @endif
        </div>
    </section>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="tile">
            <div class="text-xs text-dc-muted">{{ __('moderation.account_created') }}</div>
            <div class="mt-1 font-semibold text-dc-header">{{ \App\Support\Timezone::format($createdAt, 'll') }}</div>
        </div>
        <div class="tile">
            <div class="text-xs text-dc-muted">{{ __('moderation.member_since') }}</div>
            <div class="mt-1 font-semibold text-dc-header">{{ \App\Support\Timezone::format($member['joined_at'], 'll') ?? '–' }}</div>
        </div>
        <div class="tile">
            <div class="text-xs text-dc-muted">{{ __('moderation.level_server') }}</div>
            <div class="mt-1 font-semibold text-dc-header">
                {{ __('levels.level') }} {{ $serverLevel['level'] }}
                @if ($serverRank) <span class="text-sm font-normal text-dc-muted">· #{{ $serverRank }}</span> @endif
            </div>
        </div>
        <div class="tile">
            <div class="text-xs text-dc-muted">{{ __('moderation.level_global') }}</div>
            <div class="mt-1 font-semibold text-dc-header">{{ __('levels.level') }} {{ $globalLevel['level'] }}</div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
        <section class="card space-y-4 self-start">
            <h2 class="text-lg font-semibold text-dc-header">{{ __('moderation.cases_title') }}</h2>
            @if ($cases->isEmpty())
                <p class="text-sm text-dc-faint">{{ __('moderation.no_cases') }}</p>
            @else
                <ol class="space-y-3">
                    @foreach ($cases as $case)
                        <li wire:key="case-{{ $case->id }}" class="rounded-lg border-l-4 bg-dc-900 p-3" style="border-color: {{ \App\Models\ModCase::COLORS[$case->type] ?? '#5865f2' }}">
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="font-semibold text-dc-header">#{{ $case->number }} · {{ __('moderation.type_'.$case->type) }}</span>
                                @if ($case->duration)
                                    <span class="text-dc-muted">· {{ \App\Support\Duration::format($case->duration) }}</span>
                                @endif
                                @if ($case->type === 'warn' && ! $case->active)
                                    <span class="rounded-full bg-dc-600 px-2 text-xs text-dc-muted">{{ __('moderation.revoked') }}</span>
                                @endif
                                <span class="ml-auto text-xs text-dc-faint">{{ \App\Support\Timezone::format($case->created_at) }}</span>
                            </div>
                            <p class="mt-1 text-sm text-dc-text">{{ $case->reason ?? __('moderation.no_reason') }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-dc-muted">
                                <span>{{ __('moderation.by', ['name' => $case->moderator_name ?? '–']) }} · {{ __('moderation.source_'.$case->source) }}</span>
                                @if ($case->type === 'warn' && $case->active)
                                    <button type="button" wire:click="revokeWarning({{ $case->id }})" data-confirm="{{ __('moderation.revoke_confirm') }}"
                                            class="ml-auto rounded-md px-2 py-1 text-dc-muted hover:bg-dc-600 hover:text-dc-header">{{ __('moderation.revoke') }}</button>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        <div class="space-y-6">
            <section class="card space-y-4">
                <h2 class="text-lg font-semibold text-dc-header">{{ __('moderation.action_title') }}</h2>

                @if ($action)
                    <div @class([
                        'flex items-start gap-3 rounded-lg p-3 text-sm',
                        'bg-dc-900 text-dc-muted' => ! $action->finished(),
                        'bg-accent/15 text-dc-header' => $action->status === 'done',
                        'bg-dc-red/15 text-dc-header' => $action->status === 'failed',
                    ]) role="status">
                        <span class="flex-1">
                            @if (! $action->finished())
                                {{ __('moderation.pending') }}
                            @elseif ($action->status === 'done')
                                {{ __('moderation.done', ['number' => $action->modCase?->number ?? '?']) }}
                            @else
                                {{ __('moderation.'.($action->error ?? 'mod_error_unknown')) }}
                            @endif
                        </span>
                        @if ($action->finished())
                            <button type="button" wire:click="dismiss" class="text-dc-muted hover:text-dc-header" aria-label="{{ __('menu.close_menu') }}">
                                <x-icon name="close" class="size-4" />
                            </button>
                        @endif
                    </div>
                @endif

                <form novalidate wire:submit="submit" class="space-y-4">
                    <div>
                        <label class="label" for="action-type">{{ __('moderation.action_type') }}</label>
                        <select id="action-type" wire:model.live="actionType" class="input">
                            @foreach ($types as $type)
                                <option value="{{ $type }}">{{ __('moderation.action_'.$type) }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if (in_array($actionType, ['timeout', 'ban'], true))
                        <div>
                            <label class="label" for="action-duration">{{ __('moderation.duration') }}</label>
                            <input id="action-duration" type="text" wire:model="duration" class="input"
                                   placeholder="{{ $actionType === 'ban' ? __('moderation.permanent') : '10m, 2h, 7d' }}">
                            @error('duration') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if ($actionType === 'ban')
                        <div>
                            <label class="label" for="action-delete">{{ __('moderation.delete_messages') }}</label>
                            <select id="action-delete" wire:model="deleteDays" class="input">
                                @foreach (range(0, 7) as $days)
                                    <option value="{{ $days }}">{{ $days === 0 ? __('moderation.delete_none') : trans_choice('moderation.delete_days', $days) }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label class="label" for="action-reason">{{ __('moderation.reason') }}</label>
                        <textarea id="action-reason" wire:model="reason" rows="3" maxlength="500" class="input resize-y"
                                  placeholder="{{ __('moderation.reason_placeholder') }}"></textarea>
                        @error('reason') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" wire:loading.attr="disabled" @disabled($action && ! $action->finished())
                            @class(['w-full', 'btn-primary' => ! in_array($actionType, ['kick', 'ban'], true), 'btn bg-dc-red text-white hover:brightness-110' => in_array($actionType, ['kick', 'ban'], true)])
                            @if (in_array($actionType, ['kick', 'ban'], true)) data-confirm="{{ __('moderation.confirm_'.$actionType, ['name' => $member['name']]) }}" @endif>
                        {{ __('moderation.action_'.$actionType) }}
                    </button>
                </form>
            </section>

            @if ($member['roles'])
                <section class="card">
                    <h2 class="mb-3 text-sm font-semibold text-dc-header">{{ __('moderation.roles') }}</h2>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($member['roles'] as $role)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-dc-900 px-2.5 py-1 text-xs text-dc-text">
                                <span class="size-2 rounded-full" style="background: {{ $role['color'] ?? '#99aab5' }}"></span>{{ $role['name'] }}
                            </span>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>
