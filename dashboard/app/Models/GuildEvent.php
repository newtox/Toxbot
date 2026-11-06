<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuildEvent extends Model
{
    protected $fillable = [
        'guild', 'name', 'role_id', 'team_role_id', 'category_id',
        'info_channel_id', 'text_channel_id', 'voice_channel_id', 'extra_voice_channel_ids', 'created_by',
    ];

    public function voiceChannelIds(): array
    {
        return array_values(array_filter([
            $this->voice_channel_id,
            ...explode(',', (string) $this->extra_voice_channel_ids),
        ]));
    }

    public function scopeForGuild($query, string $guildId)
    {
        return $query->where('guild', $guildId);
    }
}
