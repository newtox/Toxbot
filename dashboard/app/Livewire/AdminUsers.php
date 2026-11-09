<?php

namespace App\Livewire;

use App\Http\Middleware\EnsureBotOwner;
use App\Models\BotUser;
use App\Models\DashboardUser;
use App\Services\Discord;
use App\Services\GuildMembers;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AdminUsers extends Component
{
    public const SIZE = 50;

    public string $search = '';

    public function mount(): void
    {
        abort_unless(EnsureBotOwner::check(Auth::id()), 403);
    }

    public function open(): void
    {
        abort_unless(EnsureBotOwner::check(Auth::id()), 403);
        $search = trim($this->search);
        if (preg_match('/^\d{15,22}$/', $search)) {
            $this->redirectRoute('admin.user', ['user' => $search], navigate: false);

            return;
        }
        $this->addError('search', __('admin.users_search_invalid'));
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

        return view('livewire.admin-users', [
            'entries' => $entries,
            'total' => (int) BotUser::query()->sum('commands_used'),
            'users' => BotUser::query()->where('commands_used', '>', 0)->count(),
            'matches' => $this->matches(),
        ]);
    }

    private function matches(): array
    {
        $search = trim($this->search);
        if (mb_strlen($search) < 2 || preg_match('/^\d+$/', $search)) {
            return [];
        }
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';

        return DashboardUser::query()
            ->where(fn ($query) => $query->where('username', 'like', $like)->orWhere('global_name', 'like', $like))
            ->orderBy('username')
            ->limit(10)
            ->get()
            ->map(fn (DashboardUser $user) => GuildMembers::userCard([
                'id' => (string) $user->id,
                'username' => $user->username,
                'global_name' => $user->global_name,
                'avatar' => $user->avatar,
            ]))
            ->all();
    }
}
