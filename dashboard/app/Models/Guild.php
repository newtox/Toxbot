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
    ];

    public static function forDiscordId(string $id): self
    {
        return static::firstOrNew(['id' => $id]);
    }
}
