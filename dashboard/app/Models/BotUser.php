<?php

namespace App\Models;

use App\Support\Level;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BotUser extends Model
{
    public const DEFAULT_LANGUAGE = 'en_us';

    public const DEFAULT_COLOR = '#7289da';

    public const COLOR_REGEX = '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';

    protected $table = 'users';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['id', 'language', 'color'];

    protected $attributes = [
        'language' => self::DEFAULT_LANGUAGE,
        'color' => self::DEFAULT_COLOR,
    ];

    protected function casts(): array
    {
        return [
            'xp' => 'integer',
            'background_id' => 'integer',
            'credits' => 'integer',
            'commands_used' => 'integer',
            'daily_streak' => 'integer',
            'xp_at' => 'datetime',
            'daily_at' => 'datetime',
            'xp_boost_until' => 'datetime',
        ];
    }

    public static function languages(): array
    {
        return collect(config('app.locales'))->mapWithKeys(fn ($l) => [$l['bot'] => $l['name']])->all();
    }

    public static function forDiscordId(string $id): self
    {
        return static::firstOrNew(['id' => $id]);
    }

    public function background(): BelongsTo
    {
        return $this->belongsTo(Background::class);
    }

    public function backgrounds(): BelongsToMany
    {
        return $this->belongsToMany(Background::class, 'user_backgrounds', 'user', 'background_id')
            ->withPivot('created_at');
    }

    public function level(): array
    {
        return Level::fromXp((int) $this->xp);
    }

    public function globalRank(): int
    {
        return static::query()->where('xp', '>', (int) $this->xp)->count() + 1;
    }

    public function owns(Background $background): bool
    {
        return $background->price === 0 && ! $background->premium_only
            || $this->backgrounds()->whereKey($background->id)->exists();
    }
}
