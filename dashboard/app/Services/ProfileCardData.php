<?php

namespace App\Services;

use App\Models\BotUser;
use App\Models\MemberLevel;
use App\Models\ShopItem;
use App\Support\Level;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ProfileCardData
{
    public const AVATAR_DIR = 'avatars';

    public function __construct(
        private readonly Discord $discord,
        private readonly Premium $premium,
    ) {}

    public function build(string $userId, ?string $guildId = null, array $previews = []): array
    {
        $user = BotUser::forDiscordId($userId);
        $premium = $this->premium->isPremium($userId);

        $items = [];
        foreach (ShopItem::TYPES as $type) {
            $item = $previews[$type] ?? null;
            if ($item === null) {
                $id = $user->equippedId($type);
                $item = $id ? ShopItem::query()->ofType($type)->find($id) : null;
                if ($item && (! $item->active || ($item->premium_only && ! $premium))) {
                    $item = null;
                }
            }
            $items[$type] = $item && $item->type === $type ? ['path' => $item->path(), 'animated' => $item->animated] : null;
        }

        $stats = ['Global Rank' => '#'.$this->number($user->globalRank())];

        if ($guildId) {
            $member = MemberLevel::query()->where('guild', $guildId)->where('user', $userId)->first();
            $stats['Server Level'] = $this->number(Level::fromXp((int) $member?->xp)['level']);
        }

        $stats['Credits'] = $this->number((int) $user->credits);
        $stats['Commands'] = $this->number((int) $user->commands_used);

        return [
            'background' => $items[ShopItem::BACKGROUND],
            'frame' => $items[ShopItem::FRAME],
            'bar' => $items[ShopItem::BAR],
            'avatar' => $this->avatar($userId),
            'avatar_animated' => $this->animatedAvatar($userId),
            'accent' => $user->color ?: BotUser::DEFAULT_COLOR,
            'level' => $user->level(),
            'stats' => $stats,
            'premium' => $premium,
        ];
    }

    private function avatar(string $userId): ?string
    {
        $cached = Cache::remember("card:avatar:{$userId}", 3600, function () use ($userId) {
            try {
                $profile = $this->discord->user($userId);
                $url = isset($profile['avatar'])
                    ? "https://cdn.discordapp.com/avatars/{$userId}/{$profile['avatar']}.png?size=256"
                    : 'https://cdn.discordapp.com/embed/avatars/'.(((int) $userId >> 22) % 6).'.png';

                $response = Http::timeout(5)->get($url);

                return $response->successful() ? base64_encode($response->body()) : '';
            } catch (\Throwable $e) {
                report($e);

                return '';
            }
        });

        return $cached !== '' ? base64_decode($cached) : null;
    }

    private function animatedAvatar(string $userId): ?array
    {
        $hash = rescue(fn () => $this->discord->user($userId)['avatar'] ?? null, null, report: false);
        if (! is_string($hash) || ! str_starts_with($hash, 'a_') || ! preg_match('/^a_[0-9a-f]+$/', $hash)) {
            return null;
        }

        $disk = Storage::disk('local');
        $file = self::AVATAR_DIR."/{$userId}-{$hash}.gif";

        if (! $disk->exists($file)) {
            try {
                $response = Http::timeout(8)->get("https://cdn.discordapp.com/avatars/{$userId}/{$hash}.gif?size=256");
            } catch (\Throwable) {
                return null;
            }
            if (! $response->successful() || ! str_starts_with($response->body(), 'GIF')) {
                return null;
            }
            $disk->put($file, $response->body());
        }

        return ['path' => $disk->path($file), 'animated' => true];
    }

    private function number(int $value): string
    {
        return number_format($value, 0, '', ' ');
    }
}
