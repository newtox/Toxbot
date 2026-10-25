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
