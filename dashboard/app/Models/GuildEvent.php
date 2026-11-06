<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuildEvent extends Model
{
    protected $fillable = [
        'guild', 'name', 'role_id', 'team_role_id', 'category_id',
        'info_channel_id', 'text_channel_id', 'voice_channel_id', 'created_by',
    ];

    public function scopeForGuild($query, string $guildId)
    {
        return $query->where('guild', $guildId);
    }
}
