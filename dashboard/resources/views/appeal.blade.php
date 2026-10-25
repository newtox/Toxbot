<x-layouts.app :title="__('tickets.appeal_page_title')">
    <div class="mx-auto max-w-lg py-6">
        <section class="card space-y-5">
            <div class="flex flex-col items-center gap-3 text-center">
                @if ($guildIcon)
                    <img src="{{ $guildIcon }}" alt="" class="size-20 rounded-3xl">
                @else
                    <span class="grid size-20 place-items-center rounded-3xl bg-dc-600 text-2xl font-bold text-dc-header">{{ mb_substr($guildName ?? '?', 0, 1) }}</span>
                @endif
                <div>
                    <p class="text-sm text-dc-muted">{{ __('tickets.appeal_page_title') }}</p>
                    <h1 class="text-2xl font-bold text-dc-header">{{ $guildName ?? __('protection.verify_unknown_server') }}</h1>
                </div>
            </div>

            @switch($status)
                @case('form')
                    <p class="text-sm text-dc-muted">{{ __('tickets.appeal_intro') }}</p>
                    @if (! empty($banReason))
                        <p class="rounded-lg bg-dc-900 px-3 py-2 text-sm"><span class="text-dc-muted">{{ __('moderation.ban_reason') }}:</span> {{ $banReason }}</p>
                    @endif
                    <form novalidate method="POST" action="{{ route('appeals.submit', $guildId) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="label" for="appeal-reason">{{ __('tickets.appeal_reason') }}</label>
                            <div x-data="{ length: {{ mb_strlen((string) old('reason')) }} }">
                                <textarea id="appeal-reason" name="reason" rows="7" maxlength="2000" x-on:input="length = $el.value.length" class="input resize-y">{{ old('reason') }}</textarea>
                                <p class="mt-1 text-right text-xs tabular-nums" :class="length < 20 ? 'text-dc-faint' : 'text-dc-muted'">
                                    <span x-text="length"></span> / 2000 · {{ __('tickets.appeal_min_length', ['min' => 20]) }}
                                </p>
                            </div>
                            @error('reason') <p class="mt-2 text-sm text-dc-red">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="btn-primary w-full">{{ __('tickets.appeal_send') }}</button>
                    </form>
                    @break
                @case('sent')
                    <p class="text-center text-dc-header">{{ __('tickets.appeal_sent') }}</p>
                    @break
                @case('pending')
                    <p class="text-center text-dc-header">{{ __('tickets.appeal_pending', ['date' => \App\Support\Timezone::format($appeal->created_at, 'll')]) }}</p>
                    @break
                @case('rejected')
                    <p class="text-center text-dc-header">{{ __('tickets.appeal_rejected', ['date' => \App\Support\Timezone::format($retryAt, 'll')]) }}</p>
                    @if ($appeal->response)
                        <p class="rounded-lg bg-dc-900 px-3 py-2 text-sm"><span class="text-dc-muted">{{ __('tickets.appeal_response') }}:</span> {{ $appeal->response }}</p>
                    @endif
                    @break
                @case('not_banned')
                    <p class="text-center text-dc-muted">{{ __('tickets.appeal_not_banned', ['server' => $guildName]) }}</p>
                    @break
                @default
                    <p class="text-center text-dc-muted">{{ __('tickets.appeal_disabled') }}</p>
            @endswitch
        </section>
    </div>
</x-layouts.app>
