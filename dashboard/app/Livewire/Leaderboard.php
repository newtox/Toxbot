<?php

namespace App\Livewire;

use App\Models\BotUser;
use App\Models\DashboardUser;
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

    public function render(Discord $discord, GuildMembers $members)
    {
        $userId = (string) Auth::id();
        $guilds = rescue(fn () => app(GuildAccess::class)->dashboardGuilds(Auth::user()), [], report: false);
        $guild = collect($guilds)->firstWhere('id', $this->scope);
        if ($this->scope !== '' && ! $guild) {
            $this->scope = '';
        }

        $query = $guild
            ? MemberLevel::query()->where('guild', $guild['id'])->where('xp', '>', 0)->select(['user as id', 'xp'])
            : BotUser::query()->where('xp', '>', 0)->select(['id', 'xp']);
        $rows = (clone $query)->orderByDesc('xp')->orderBy($guild ? 'user' : 'id')->limit(self::SIZE)->get();

        $own = (clone $query)->where($guild ? 'user' : 'id', $userId)->value('xp');
        $ownRank = $own === null ? null : (clone $query)->where('xp', '>', $own)->count() + 1;

        $guildMembers = $guild ? collect(rescue(fn () => $discord->guildMembers($guild['id']), [], report: false))
            ->keyBy(fn ($member) => (string) $member['user']['id']) : collect();
        $dashboardUsers = DashboardUser::query()->whereIn('id', $rows->pluck('id'))->get()->keyBy(fn ($user) => (string) $user->id);

        $entries = $rows->values()->map(function ($row, $index) use ($guild, $guildMembers, $dashboardUsers, $discord, $userId) {
            $id = (string) $row->id;
            $card = match (true) {
                $guildMembers->has($id) => GuildMembers::card($guild['id'], $guildMembers[$id]),
                $dashboardUsers->has($id) => GuildMembers::userCard([
                    'id' => $id,
                    'username' => $dashboardUsers[$id]->username,
                    'global_name' => $dashboardUsers[$id]->global_name,
                    'avatar' => $dashboardUsers[$id]->avatar,
                ]),
                default => rescue(fn () => GuildMembers::userCard($discord->user($id)), null, report: false)
                    ?? ['id' => $id, 'name' => $id, 'username' => null, 'avatar' => GuildMembers::userCard(['id' => $id, 'username' => ''])['avatar']],
            };

            return [
                ...$card,
                'rank' => $index + 1,
                'xp' => (int) $row->xp,
                'level' => Level::fromXp((int) $row->xp)['level'],
                'self' => $id === $userId,
            ];
        });

        return view('livewire.leaderboard', [
            'guilds' => $guilds,
            'guild' => $guild,
            'entries' => $entries,
            'ownRank' => $ownRank,
            'ownXp' => $own === null ? null : (int) $own,
        ]);
    }
}
