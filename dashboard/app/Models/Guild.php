<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guild extends Model
{
    public const PLACEHOLDERS = [
        '&mention' => 'guild.placeholder_mention',
        '&user' => 'guild.placeholder_user',
        '&server' => 'guild.placeholder_server',
    ];

    public const MAX_MESSAGE_LENGTH = 2000;

    protected $table = 'guilds';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id', 'welcome_channel', 'bye_channel', 'welcome_msg', 'bye_msg', 'autorole',
        'tags_enabled', 'levelup_enabled', 'levelup_channel',
        'notify', 'stream', 'stream_msg', 'stream_video_msg', 'live_role', 'live_role_required',
        'modlog_channel', 'mod_dm',
    ];

    public const STREAM_PLACEHOLDERS = [
        '&channel' => 'streams.placeholder_channel',
        '&title' => 'streams.placeholder_title',
        '&game' => 'streams.placeholder_game',
        '&link' => 'streams.placeholder_link',
        '&role' => 'streams.placeholder_role',
    ];

    public const DEFAULT_STREAM_MESSAGE = '&role **&channel** is live! &link';

    public const DEFAULT_VIDEO_MESSAGE = '&role **&channel** uploaded a new video! &link';

    protected $attributes = [
        'tags_enabled' => true,
        'levelup_enabled' => false,
        'mod_dm' => true,
    ];

    protected function casts(): array
    {
        return ['tags_enabled' => 'boolean', 'levelup_enabled' => 'boolean', 'mod_dm' => 'boolean'];
    }

    public static function forDiscordId(string $id): self
    {
        return static::firstOrNew(['id' => $id]);
    }
}
