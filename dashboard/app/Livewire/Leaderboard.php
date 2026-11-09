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
use Livewire\Attributes\Url;
use Livewire\Component;

class Leaderboard extends Component
{
    public const SIZE = 25;

    #[Url(as: 'server', except: '')]
    public string $scope = '';

    public function render(Discord $discord)
    {
        $userId = (string) Auth::id();
        $enabled = Guild::query()->where('levels_enabled', true)->pluck('id')->map(fn ($id) => (string) $id)->all();
        $guilds = collect(rescue(fn () => app(GuildAccess::class)->dashboardGuilds(Auth::user()), [], report: false))
            ->filter(fn ($guild) => in_array($guild['id'], $enabled, true))
            ->values()
            ->all();
        $guild = collect($guilds)->firstWhere('id', $this->scope) ?? ($guilds[0] ?? null);
        $this->scope = $guild['id'] ?? '';

        $entries = collect();
        $ownRank = null;
        $own = null;

        if ($guild) {
            $query = MemberLevel::query()->where('guild', $guild['id'])->where('xp', '>', 0);
            $rows = (clone $query)->orderByDesc('xp')->orderBy('user')->limit(self::SIZE)->get(['user', 'xp']);
            $own = (clone $query)->where('user', $userId)->value('xp');
            $ownRank = $own === null ? null : (clone $query)->where('xp', '>', $own)->count() + 1;

            $members = collect(rescue(fn () => $discord->guildMembers($guild['id']), [], report: false))
                ->keyBy(fn ($member) => (string) $member['user']['id']);
            $dashboardUsers = DashboardUser::query()->whereIn('id', $rows->pluck('user'))->get()->keyBy(fn ($user) => (string) $user->id);

            $entries = $rows->values()->map(function ($row, $index) use ($guild, $members, $dashboardUsers, $discord, $userId) {
                $id = (string) $row->user;
                $card = match (true) {
                    $members->has($id) => GuildMembers::card($guild['id'], $members[$id]),
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
            'guilds' => $guilds,
            'guild' => $guild,
            'entries' => $entries,
            'ownRank' => $ownRank,
            'ownXp' => $own === null ? null : (int) $own,
        ]);
    }
}
