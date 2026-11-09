<?php

namespace App\Livewire;

use App\Models\DashboardUser;
use App\Models\Guild;
use App\Models\MemberLevel;
use App\Services\Discord;
use App\Services\GuildAccess;
use App\Services\GuildMembers;
use App\Support\Level;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Leaderboard extends Component
{
    public const SIZE = 25;

    #[Locked]
    public string $guildId;

    #[Locked]
    public bool $manage = false;

    public function mount(string $guildId): void
    {
        $guild = app(GuildAccess::class)->findMember(Auth::user(), $guildId);
        abort_if($guild === null, 403);

        $this->guildId = $guildId;
        $this->manage = (bool) $guild['manage'];
    }

    public function render(Discord $discord)
    {
        $userId = (string) Auth::id();
        $enabled = (bool) Guild::query()->whereKey($this->guildId)->value('levels_enabled');
        $entries = collect();
        $ownRank = null;
        $own = null;

        if ($enabled) {
            $query = MemberLevel::query()->where('guild', $this->guildId)->where('xp', '>', 0);
            $rows = (clone $query)->orderByDesc('xp')->orderBy('user')->limit(self::SIZE)->get(['user', 'xp']);
            $own = (clone $query)->where('user', $userId)->value('xp');
            $ownRank = $own === null ? null : (clone $query)->where('xp', '>', $own)->count() + 1;

            $members = collect(rescue(fn () => $discord->guildMembers($this->guildId), [], report: false))
                ->keyBy(fn ($member) => (string) $member['user']['id']);
            $dashboardUsers = DashboardUser::query()->whereIn('id', $rows->pluck('user'))->get()->keyBy(fn ($user) => (string) $user->id);

            $entries = $rows->values()->map(function ($row, $index) use ($members, $dashboardUsers, $discord, $userId) {
                $id = (string) $row->user;
                $card = match (true) {
                    $members->has($id) => GuildMembers::card($this->guildId, $members[$id]),
                    $dashboardUsers->has($id) => GuildMembers::userCard([
                        'id' => $id,
                        'username' => $dashboardUsers[$id]->username,
                        'global_name' => $dashboardUsers[$id]->global_name,
                        'avatar' => $dashboardUsers[$id]->avatar,
                    ]),
                    default => rescue(fn () => GuildMembers::userCard($discord->user($id)), null, report: false)
                        ?? [...GuildMembers::userCard(['id' => $id, 'username' => $id]), 'username' => null],
                };

                return [
                    ...$card,
                    'rank' => $index + 1,
                    'xp' => (int) $row->xp,
                    'level' => Level::fromXp((int) $row->xp)['level'],
                    'self' => $id === $userId,
                ];
            });
        }

        return view('livewire.leaderboard', [
            'enabled' => $enabled,
            'entries' => $entries,
            'ownRank' => $ownRank,
            'ownXp' => $own === null ? null : (int) $own,
        ]);
    }
}
