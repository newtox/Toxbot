<?php

namespace App\Livewire;

use App\Models\BotUser;
use App\Models\MemberLevel;
use App\Models\ModAction;
use App\Models\ModCase;
use App\Services\Discord;
use App\Services\GuildAccess;
use App\Support\DiscordMember;
use App\Support\Duration;
use App\Support\Level;
use Illuminate\Http\Client\RequestException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class MemberProfile extends Component
{
    #[Locked]
    public string $guildId;

    #[Locked]
    public string $userId;

    public string $actionType = 'warn';

    public string $reason = '';

    public string $duration = '';

    public int $deleteDays = 0;

    #[Locked]
    public ?int $actionId = null;

    public function mount(string $guildId, string $userId): void
    {
        $this->guildId = $guildId;
        $this->userId = $userId;
    }

    public function submit(): void
    {
        $this->authorizeGuild();

        $this->validate([
            'actionType' => ['required', Rule::in(ModAction::TYPES)],
            'reason' => ['nullable', 'string', 'max:500'],
            'duration' => ['nullable', 'string', 'max:20'],
            'deleteDays' => ['integer', 'min:0', 'max:7'],
        ]);

        $seconds = null;
        if (in_array($this->actionType, ['timeout', 'ban'], true) && trim($this->duration) !== '') {
            $seconds = Duration::parse($this->duration);
            if ($seconds === null) {
                $this->addError('duration', __('moderation.duration_invalid'));

                return;
            }
        }

        if ($this->actionType === 'timeout' && ($seconds === null || $seconds > 28 * 86400)) {
            $this->addError('duration', __('moderation.timeout_max'));

            return;
        }

        $user = auth()->user();
        $action = ModAction::create([
            'guild' => $this->guildId,
            'type' => $this->actionType,
            'user_id' => $this->userId,
            'moderator_id' => (string) $user->id,
            'moderator_name' => $user->display_name,
            'reason' => trim($this->reason) !== '' ? trim($this->reason) : null,
            'duration' => $seconds,
            'delete_days' => $this->actionType === 'ban' ? $this->deleteDays : 0,
        ]);

        $this->actionId = $action->id;
        $this->reset('reason', 'duration', 'deleteDays');
    }

    public function dismiss(): void
    {
        $this->actionId = null;
    }

    public function revokeWarning(int $caseId): void
    {
        $this->authorizeGuild();

        ModCase::query()->forGuild($this->guildId)
            ->where('user_id', $this->userId)
            ->where('type', 'warn')
            ->whereKey($caseId)
            ->update(['active' => false]);
    }

    private function authorizeGuild(): void
    {
        abort_if(app(GuildAccess::class)->find(auth()->user(), $this->guildId) === null, 403);
    }

    public function render(Discord $discord)
    {
        $action = $this->actionId ? ModAction::query()->with('modCase')->find($this->actionId) : null;
        if ($action?->finished()) {
            $discord->forgetGuildCache($this->guildId);
        }

        try {
            $member = $discord->guildMember($this->guildId, $this->userId);
            $user = $member['user'] ?? rescue(fn () => $discord->user($this->userId), null, report: false);
            $ban = $member ? null : $discord->guildBan($this->guildId, $this->userId);
            $roles = collect($discord->guildRoles($this->guildId))
                ->reject(fn ($r) => $r['id'] === $this->guildId)
                ->mapWithKeys(fn ($r) => [$r['id'] => [
                    'name' => $r['name'],
                    'color' => $r['color'] ? sprintf('#%06x', $r['color']) : null,
                    'position' => $r['position'],
                ]])
                ->all();
        } catch (RequestException $e) {
            report($e);
            $member = $ban = null;
            $user = null;
            $roles = [];
        }

        abort_if($user === null, 404);

        $summary = $member
            ? DiscordMember::summary($member, $roles, $this->guildId)
            : [
                'id' => $user['id'],
                'name' => $user['global_name'] ?? $user['username'],
                'username' => $user['username'],
                'avatar' => DiscordMember::avatar($user),
                'bot' => (bool) ($user['bot'] ?? false),
                'joined_at' => null,
                'roles' => [],
                'timeout_until' => null,
            ];

        $types = match (true) {
            $member !== null => array_values(array_filter(
                ['warn', 'timeout', 'untimeout', 'kick', 'ban'],
                fn ($type) => $type !== 'untimeout' || $summary['timeout_until'] !== null,
            )),
            $ban !== null => ['unban'],
            default => ['ban'],
        };
        if (! in_array($this->actionType, $types, true)) {
            $this->actionType = $types[0];
        }

        $serverXp = (int) MemberLevel::query()->where('guild', $this->guildId)->where('user', $this->userId)->value('xp');
        $globalXp = (int) BotUser::query()->whereKey($this->userId)->value('xp');

        return view('livewire.member-profile', [
            'member' => $summary,
            'onServer' => $member !== null,
            'ban' => $ban,
            'createdAt' => DiscordMember::createdAt($this->userId),
            'serverLevel' => Level::fromXp($serverXp),
            'globalLevel' => Level::fromXp($globalXp),
            'serverRank' => $serverXp > 0
                ? MemberLevel::query()->where('guild', $this->guildId)->where('xp', '>', $serverXp)->count() + 1
                : null,
            'cases' => ModCase::query()->forGuild($this->guildId)->where('user_id', $this->userId)->orderByDesc('number')->get(),
            'action' => $action,
            'types' => $types,
        ]);
    }
}
