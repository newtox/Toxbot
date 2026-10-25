<?php

namespace App\Livewire;

use App\Models\ModCase;
use App\Services\Discord;
use App\Services\GuildAccess;
use App\Support\DiscordMember;
use Illuminate\Http\Client\RequestException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class MemberList extends Component
{
    public const PER_PAGE = 50;

    public const FILTERS = ['all', 'cases', 'timeout', 'bots'];

    public const MEMBER_FILTERS = ['all', 'bots'];

    #[Locked]
    public string $guildId;

    #[Locked]
    public bool $moderator = false;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $filter = 'all';

    #[Url(as: 'page', except: 1)]
    public int $pageNumber = 1;

    public function mount(string $guildId): void
    {
        $this->guildId = $guildId;
        $this->moderator = app(GuildAccess::class)->find(auth()->user(), $guildId) !== null;
    }

    public function updatedSearch(): void
    {
        $this->pageNumber = 1;
    }

    public function updatedFilter(): void
    {
        $this->pageNumber = 1;
    }

    public function previousPage(): void
    {
        $this->pageNumber = max(1, $this->pageNumber - 1);
    }

    public function nextPage(): void
    {
        $this->pageNumber++;
    }

    public function render(Discord $discord)
    {
        $error = null;
        $truncated = false;

        try {
            $roles = collect($discord->guildRoles($this->guildId))
                ->reject(fn ($r) => $r['id'] === $this->guildId)
                ->mapWithKeys(fn ($r) => [$r['id'] => [
                    'name' => $r['name'],
                    'color' => $r['color'] ? sprintf('#%06x', $r['color']) : null,
                    'position' => $r['position'],
                ]])
                ->all();

            $query = trim($this->search);
            if (preg_match('/^\d{15,22}$/', $query)) {
                $member = $discord->guildMember($this->guildId, $query);
                $raw = $member ? [$member] : [];
            } elseif ($query !== '') {
                $raw = $discord->searchGuildMembers($this->guildId, $query);
            } else {
                $raw = $discord->guildMembers($this->guildId);
                $truncated = count($raw) >= Discord::MEMBER_LIMIT;
            }
        } catch (RequestException $e) {
            report($e);
            $raw = $roles = [];
            $error = __('moderation.members_error');
        }

        if (! in_array($this->filter, $this->moderator ? self::FILTERS : self::MEMBER_FILTERS, true)) {
            $this->filter = 'all';
        }

        $members = collect($raw)
            ->filter(fn ($m) => isset($m['user']))
            ->map(fn ($m) => DiscordMember::summary($m, $roles, $this->guildId));

        $caseCounts = $this->moderator
            ? ModCase::query()->forGuild($this->guildId)
                ->whereIn('user_id', $members->pluck('id'))
                ->selectRaw('user_id, count(*) as total')
                ->groupBy('user_id')
                ->pluck('total', 'user_id')
            : collect();

        if (! $this->moderator) {
            $members = $members->map(fn ($m) => array_merge($m, ['timeout_until' => null]));
        }

        $members = $members
            ->map(fn ($m) => $m + ['cases' => (int) ($caseCounts[$m['id']] ?? 0)])
            ->filter(fn ($m) => match ($this->filter) {
                'timeout' => $m['timeout_until'] !== null,
                'cases' => $m['cases'] > 0,
                'bots' => $m['bot'],
                default => true,
            })
            ->sortByDesc(fn ($m) => $m['joined_at']?->getTimestamp() ?? 0)
            ->values();

        $pages = max(1, (int) ceil($members->count() / self::PER_PAGE));
        $this->pageNumber = min($this->pageNumber, $pages);

        return view('livewire.member-list', [
            'members' => $members->forPage($this->pageNumber, self::PER_PAGE),
            'total' => $members->count(),
            'pages' => $pages,
            'error' => $error,
            'truncated' => $truncated,
            'filters' => $this->moderator ? self::FILTERS : self::MEMBER_FILTERS,
        ]);
    }
}
