<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModCase extends Model
{
    public const TYPES = ['warn', 'timeout', 'untimeout', 'kick', 'ban', 'unban'];

    public const COLORS = [
        'warn' => '#fee75c',
        'timeout' => '#f57c00',
        'untimeout' => '#57f287',
        'kick' => '#f47b67',
        'ban' => '#ed4245',
        'unban' => '#57f287',
    ];

    protected $fillable = ['reason', 'active'];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'duration' => 'integer',
            'active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function scopeForGuild($query, string $guildId)
    {
        return $query->where('guild', $guildId);
    }
}
