<?php

namespace App\Livewire;

use App\Http\Middleware\EnsureBotOwner;
use App\Models\BotUser;
use App\Models\DashboardUser;
use App\Services\Discord;
use App\Services\GuildMembers;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AdminActiveUsers extends Component
{
    public const SIZE = 50;

    public function mount(): void
    {
        abort_unless(EnsureBotOwner::check(Auth::id()), 403);
    }

    public function render(Discord $discord)
    {
        abort_unless(EnsureBotOwner::check(Auth::id()), 403);

        $rows = BotUser::query()->where('commands_used', '>', 0)
            ->orderByDesc('commands_used')->orderBy('id')
            ->limit(self::SIZE)
            ->get(['id', 'commands_used', 'xp', 'credits']);
        $dashboardUsers = DashboardUser::query()->whereIn('id', $rows->pluck('id'))->get()->keyBy(fn ($user) => (string) $user->id);

        $entries = $rows->values()->map(function (BotUser $row, int $index) use ($dashboardUsers, $discord) {
            $id = (string) $row->id;
            $card = $dashboardUsers->has($id)
                ? GuildMembers::userCard([
                    'id' => $id,
                    'username' => $dashboardUsers[$id]->username,
                    'global_name' => $dashboardUsers[$id]->global_name,
                    'avatar' => $dashboardUsers[$id]->avatar,
                ])
                : rescue(fn () => GuildMembers::userCard($discord->user($id)), null, report: false)
                    ?? [...GuildMembers::userCard(['id' => $id, 'username' => $id]), 'username' => null];

            return [
                ...$card,
                'rank' => $index + 1,
                'commands' => (int) $row->commands_used,
                'level' => $row->level()['level'],
                'credits' => (int) $row->credits,
            ];
        });

        return view('livewire.admin-active-users', [
            'entries' => $entries,
            'total' => (int) BotUser::query()->sum('commands_used'),
            'users' => BotUser::query()->where('commands_used', '>', 0)->count(),
        ]);
    }
}
