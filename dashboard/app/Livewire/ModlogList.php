<?php

namespace App\Livewire;

use App\Models\ModCase;
use App\Services\GuildAccess;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class ModlogList extends Component
{
    public const PER_PAGE = 25;

    #[Locked]
    public string $guildId;

    #[Url(except: '')]
    public string $type = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'page', except: 1)]
    public int $pageNumber = 1;

    public function mount(string $guildId): void
    {
        abort_if(app(GuildAccess::class)->findWith(auth()->user(), $guildId, 'moderate') === null, 403);

        $this->guildId = $guildId;
    }

    public function updated(string $property): void
    {
        if ($property !== 'pageNumber') {
            $this->pageNumber = 1;
        }
    }

    public function previousPage(): void
    {
        $this->pageNumber = max(1, $this->pageNumber - 1);
    }

    public function nextPage(): void
    {
        $this->pageNumber++;
    }

    public function render()
    {
        if (! in_array($this->type, ModCase::TYPES, true)) {
            $this->type = '';
        }

        $query = ModCase::query()->forGuild($this->guildId);

        if ($this->type !== '') {
            $query->where('type', $this->type);
        }

        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('user_id', $search)
                    ->orWhere('moderator_id', $search)
                    ->orWhere('user_name', 'like', '%'.addcslashes($search, '%_').'%')
                    ->orWhere('moderator_name', 'like', '%'.addcslashes($search, '%_').'%');
                if (ctype_digit(ltrim($search, '#'))) {
                    $q->orWhere('number', (int) ltrim($search, '#'));
                }
            });
        }

        $total = (clone $query)->count();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $this->pageNumber = min(max(1, $this->pageNumber), $pages);

        return view('livewire.modlog-list', [
            'cases' => $query->orderByDesc('number')->forPage($this->pageNumber, self::PER_PAGE)->get(),
            'total' => $total,
            'pages' => $pages,
        ]);
    }
}
