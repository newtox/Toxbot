<?php

namespace App\Models;

use App\Http\Middleware\EnsureBotOwner;
use Illuminate\Database\Eloquent\Model;

class BlacklistEntry extends Model
{
    protected $table = 'blacklist';

    public $timestamps = false;

    protected $fillable = ['user', 'guild', 'reason', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public static function userBlocked(int|string $userId): bool
    {
        return ! EnsureBotOwner::check($userId)
            && static::query()->where('user', (string) $userId)->exists();
    }

    public static function blockedGuildIds(): array
    {
        return static::query()->whereNotNull('guild')->pluck('guild')->map(fn ($id) => (string) $id)->all();
    }
}
