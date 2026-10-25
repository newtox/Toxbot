<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StarboardPost extends Model
{
    protected $fillable = [
        'guild', 'channel_id', 'message_id', 'author_id', 'author_name', 'author_avatar',
        'content', 'image_url', 'stars', 'starboard_channel_id', 'starboard_message_id', 'message_created_at',
    ];

    protected function casts(): array
    {
        return [
            'stars' => 'integer',
            'message_created_at' => 'datetime',
        ];
    }

    public function scopeForGuild($query, string $guildId)
    {
        return $query->where('guild', $guildId);
    }

    public function jumpUrl(): string
    {
        return "https://discord.com/channels/{$this->guild}/{$this->channel_id}/{$this->message_id}";
    }
}
