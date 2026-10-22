<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StreamSubscription extends Model
{
    public const END_ACTIONS = ['keep', 'edit', 'delete'];

    protected $fillable = [
        'guild', 'stream_source_id', 'message', 'video_message', 'role', 'filter_games', 'filter_title',
        'end_action', 'notify_videos', 'notify_live',
    ];

    protected $attributes = [
        'end_action' => 'keep',
        'notify_videos' => true,
        'notify_live' => true,
    ];

    protected function casts(): array
    {
        return ['notify_videos' => 'boolean', 'notify_live' => 'boolean'];
    }

    /**
     * The subscriptions of a server without Server Premium that still get announced.
     */
    public static function freeIds(string $guildId): array
    {
        return static::query()->where('guild', $guildId)->orderBy('id')
            ->limit((int) config('toxbot.streams.free_limit'))->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(StreamSource::class, 'stream_source_id');
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(StreamAnnouncement::class);
    }

    public function matches(?string $game, ?string $title): bool
    {
        $games = array_filter(array_map(fn ($g) => mb_strtolower(trim($g)), explode(',', (string) $this->filter_games)));
        if ($games !== [] && ! in_array(mb_strtolower(trim((string) $game)), $games, true)) {
            return false;
        }

        $keyword = mb_strtolower(trim((string) $this->filter_title));

        return $keyword === '' || str_contains(mb_strtolower((string) $title), $keyword);
    }
}
