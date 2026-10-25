<?php

namespace App\Livewire;

use App\Models\Guild;
use App\Models\StarboardPost;
use App\Services\Discord;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class StarboardList extends Component
{
    public const PER_PAGE = 12;

    public const SORTS = ['newest', 'top'];

    #[Locked]
    public string $guildId;

    #[Url(except: 'newest')]
    public string $sort = 'newest';

    #[Url(as: 'page', except: 1)]
    public int $pageNumber = 1;

    public function mount(string $guildId): void
    {
        $this->guildId = $guildId;
    }

    public function updatedSort(): void
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
        if (! in_array($this->sort, self::SORTS, true)) {
            $this->sort = 'newest';
        }

        $query = StarboardPost::query()->forGuild($this->guildId);
        $total = (clone $query)->count();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $this->pageNumber = min(max(1, $this->pageNumber), $pages);

        $query = $this->sort === 'top'
            ? $query->orderByDesc('stars')->orderByDesc('message_created_at')
            : $query->orderByDesc('message_created_at')->orderByDesc('id');

        $channels = rescue(
            fn () => collect($discord->guildChannels($this->guildId))->pluck('name', 'id')->all(),
            [],
            report: false,
        );

        $row = Guild::query()->find($this->guildId);

        $posts = $query->forPage($this->pageNumber, self::PER_PAGE)->get();
        $this->refreshExpiredImages($posts, $discord);

        return view('livewire.starboard-list', [
            'posts' => $posts,
            'total' => $total,
            'pages' => $pages,
            'channels' => $channels,
            'emoji' => ($row ?? new Guild)->starboardEmojis()[0],
        ]);
    }

    private function refreshExpiredImages(Collection $posts, Discord $discord): void
    {
        $expired = $posts->filter(fn (StarboardPost $post) => self::expired($post->image_url));
        if ($expired->isEmpty()) {
            return;
        }

        $refreshed = rescue(fn () => $discord->refreshAttachmentUrls($expired->pluck('image_url')->unique()->all()), [], report: false);

        foreach ($expired as $post) {
            if (isset($refreshed[$post->image_url])) {
                $post->forceFill(['image_url' => $refreshed[$post->image_url]])->save();
            }
        }
    }

    private static function expired(?string $url): bool
    {
        if ($url === null || ! preg_match('#^https://(cdn|media)\.discordapp\.(com|net)/attachments/#', $url)) {
            return false;
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return ! isset($query['ex']) || hexdec($query['ex']) < now()->addHour()->getTimestamp();
    }
}
