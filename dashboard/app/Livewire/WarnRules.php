<?php

namespace App\Livewire;

use App\Models\WarnRule;
use App\Services\GuildAccess;
use App\Support\Duration;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class WarnRules extends Component
{
    public const MAX_RULES = 10;

    #[Locked]
    public string $guildId;

    public int $warns = 3;

    public string $action = 'timeout';

    public string $duration = '1h';

    public function mount(string $guildId): void
    {
        $this->guildId = $guildId;
    }

    public function add(): void
    {
        $this->authorizeGuild();

        $this->validate([
            'warns' => [
                'required', 'integer', 'min:1', 'max:50',
                Rule::unique('warn_rules', 'warns')->where('guild', $this->guildId),
            ],
            'action' => ['required', Rule::in(WarnRule::ACTIONS)],
            'duration' => ['nullable', 'string', 'max:20'],
        ], [
            'warns.unique' => __('moderation.rule_exists'),
        ]);

        $seconds = null;
        if ($this->action !== 'kick' && trim($this->duration) !== '') {
            $seconds = Duration::parse($this->duration);
            if ($seconds === null) {
                $this->addError('duration', __('moderation.duration_invalid'));

                return;
            }
        }

        if ($this->action === 'timeout' && ($seconds === null || $seconds > 28 * 86400)) {
            $this->addError('duration', __('moderation.timeout_max'));

            return;
        }

        if (WarnRule::query()->where('guild', $this->guildId)->count() >= self::MAX_RULES) {
            $this->addError('warns', __('moderation.rules_limit', ['max' => self::MAX_RULES]));

            return;
        }

        WarnRule::create([
            'guild' => $this->guildId,
            'warns' => $this->warns,
            'action' => $this->action,
            'duration' => $seconds,
        ]);

        $this->reset('warns', 'action', 'duration');
        $this->dispatch('saved');
    }

    public function remove(int $id): void
    {
        $this->authorizeGuild();
        WarnRule::query()->where('guild', $this->guildId)->whereKey($id)->delete();
    }

    private function authorizeGuild(): void
    {
        abort_if(app(GuildAccess::class)->find(auth()->user(), $this->guildId) === null, 403);
    }

    public function render()
    {
        return view('livewire.warn-rules', [
            'rules' => WarnRule::query()->where('guild', $this->guildId)->orderBy('warns')->get(),
        ]);
    }
}
