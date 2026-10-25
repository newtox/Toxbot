<?php

namespace App\Support;

use App\Models\BotUser;
use App\Models\StarboardPost;
use App\Models\Tag;
use App\Services\Discord;
use Illuminate\Support\Facades\Cache;

class BotStats
{
    public static function summary(Discord $discord): array
    {
        return Cache::remember('home:stats', 600, fn () => [
            'servers' => rescue(fn () => count($discord->botGuildIds()), null, report: false),
            'users' => rescue(fn () => BotUser::query()->where('xp', '>', 0)->count(), null, report: false),
            'tags' => rescue(fn () => Tag::query()->count(), null, report: false),
            'starboard' => rescue(fn () => StarboardPost::query()->count(), null, report: false),
        ]);
    }
}
