<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StreamSource extends Model
{
    public const TWITCH = 'twitch';

    public const YOUTUBE = 'youtube';

    protected $fillable = [
        'platform', 'external_id', 'login', 'name', 'avatar_url',
        'twitch_online_sub', 'twitch_offline_sub', 'websub_expires_at', 'live_item', 'live_since',
    ];

    protected function casts(): array
    {
        return ['websub_expires_at' => 'datetime', 'live_since' => 'datetime'];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(StreamSubscription::class);
    }

    public function url(): string
    {
        return $this->platform === self::TWITCH
            ? 'https://www.twitch.tv/'.$this->login
            : 'https://www.youtube.com/channel/'.$this->external_id;
    }
}
