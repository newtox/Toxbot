<section class="card space-y-4">
    <div>
        <h2 class="text-lg font-semibold text-dc-header">{{ __('admin.premium_title') }}</h2>
        <p class="text-sm text-dc-muted">{{ __('admin.premium_description') }}</p>
    </div>

    @if ($notice)
        <p class="rounded-lg border-l-4 border-accent bg-dc-900 px-4 py-3 text-sm" role="status">{{ $notice }}</p>
    @endif

    <form novalidate wire:submit="grant" class="grid gap-3 rounded-lg bg-dc-900 p-4 sm:grid-cols-[1fr_11rem]">
        <div class="min-w-0">
            <label class="label" for="premium-target">{{ __('admin.premium_server_id') }}</label>
            <input id="premium-target" type="text" inputmode="numeric" wire:model="target" placeholder="123456789012345678" class="input font-mono">
            @error('target') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
        </div>
        <div class="min-w-0">
            <label class="label" for="premium-until">{{ __('admin.premium_until') }}</label>
            <input id="premium-until" type="date" wire:model="until" min="{{ now(\App\Support\Timezone::current())->toDateString() }}" class="input">
            @error('until') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
        </div>
        <div class="min-w-0 sm:col-span-2">
            <label class="label" for="premium-note">{{ __('admin.premium_note') }}</label>
            <input id="premium-note" type="text" wire:model="note" maxlength="200" class="input">
        </div>
        <div class="flex flex-wrap items-center justify-end gap-3 sm:col-span-2">
            <p class="mr-auto text-sm text-dc-faint">{{ __('admin.premium_hint') }}</p>
            <button type="submit" class="btn-primary max-sm:w-full">{{ __('admin.premium_grant') }}</button>
        </div>
    </form>

    @if ($grants->isEmpty())
        <p class="text-sm text-dc-faint">{{ __('admin.premium_empty') }}</p>
    @else
        <ul class="divide-y divide-white/5 rounded-lg bg-dc-900">
            @foreach ($grants as $grant)
                <li wire:key="grant-{{ $grant->id }}" @class(['flex flex-wrap items-center gap-x-3 gap-y-1 px-3 py-2.5', 'opacity-60' => ! $grant->active()])>
                    <span class="font-mono text-sm text-dc-header">{{ $grant->target_id }}</span>
                    @if ($grant->note)<span class="min-w-0 truncate text-sm text-dc-muted">{{ $grant->note }}</span>@endif
                    <span @class([
                        'ml-auto rounded-full px-2 py-0.5 text-xs font-semibold',
                        'bg-accent/20 text-accent-fg' => $grant->active(),
                        'bg-dc-600 text-dc-muted' => ! $grant->active(),
                    ])>
                        @if ($grant->ends_at === null)
                            {{ __('admin.premium_forever') }}
                        @elseif ($grant->active())
                            {{ __('admin.premium_ends', ['date' => \App\Support\Timezone::format($grant->ends_at, 'll')]) }}
                        @else
                            {{ __('admin.premium_expired', ['date' => \App\Support\Timezone::format($grant->ends_at, 'll')]) }}
                        @endif
                    </span>
                    <button type="button" wire:click="revoke({{ $grant->id }})" data-confirm="{{ __('admin.premium_revoke_confirm') }}"
                            class="btn px-3 py-1.5 text-dc-red hover:bg-dc-red hover:text-white">{{ __('admin.premium_revoke') }}</button>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($configGuilds !== [])
        <div class="space-y-2 text-sm">
            <p class="text-dc-faint">{{ __('admin.premium_config') }}</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($configGuilds as $id)
                    <span class="rounded-full bg-dc-900 px-2.5 py-1 font-mono text-xs text-dc-muted">{{ $id }}</span>
                @endforeach
            </div>
        </div>
    @endif
</section>
