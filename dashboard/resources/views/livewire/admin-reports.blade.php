<section class="card space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-dc-header">{{ __('admin.reports_title') }}</h2>
            <p class="text-sm text-dc-muted">{{ __('admin.reports_description') }}</p>
        </div>
        <label class="flex items-center gap-2 text-sm text-dc-muted">
            <input type="checkbox" wire:model.live="showResolved" class="accent-[var(--color-accent)]">
            {{ __('admin.show_resolved') }}
        </label>
    </div>

    @if ($reports->isEmpty())
        <p class="text-sm text-dc-faint">{{ __('admin.no_reports') }}</p>
    @else
        <ul class="space-y-3">
            @foreach ($reports as $report)
                <li wire:key="report-{{ $report->id }}" @class(['rounded-lg bg-dc-900 p-4', 'opacity-60' => $report->resolved])>
                    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                        <span class="font-mono font-semibold text-dc-header">{{ $report->tag->name }}</span>
                        <span class="text-xs text-dc-faint">{{ __('admin.owner') }}: {{ $report->tag->owner }}</span>
                        <span class="text-xs text-dc-faint">{{ __('admin.reporter') }}: {{ $report->reporter }}</span>
                        <span class="ml-auto text-xs text-dc-faint">{{ $report->created_at?->diffForHumans() }}</span>
                    </div>
                    <p class="mt-2 text-sm"><span class="text-dc-muted">{{ __('admin.reason') }}:</span> {{ $report->reason }}</p>
                    <pre class="mt-2 max-h-40 overflow-auto rounded bg-dc-950 p-3 text-xs whitespace-pre-wrap text-dc-text">{{ $report->tag->content }}</pre>
                    @unless ($report->resolved)
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button" wire:click="dismiss({{ $report->id }})" class="btn-secondary px-3 py-1.5">{{ __('admin.dismiss') }}</button>
                            <button type="button" wire:click="deleteTag({{ $report->id }})" wire:confirm="{{ __('admin.confirm_delete') }}"
                                    class="btn px-3 py-1.5 text-dc-red hover:bg-dc-red hover:text-white">{{ __('admin.delete_tag') }}</button>
                            <button type="button" wire:click="deleteTag({{ $report->id }}, true)" wire:confirm="{{ __('admin.confirm_blacklist') }}"
                                    class="btn bg-dc-red px-3 py-1.5 text-white hover:brightness-110">{{ __('admin.delete_and_blacklist') }}</button>
                        </div>
                    @endunless
                </li>
            @endforeach
        </ul>
    @endif
</section>
