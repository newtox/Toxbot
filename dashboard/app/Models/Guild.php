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
        'verify_enabled', 'verify_role', 'verify_channel', 'verify_message_id',
        'link_invites', 'link_scam', 'link_allowlist_enabled', 'link_allowlist', 'link_blocklist', 'link_action', 'link_exempt_roles',
        'raid_enabled', 'raid_joins', 'raid_seconds', 'raid_minutes', 'raid_kick', 'raid_level', 'raid_until',
        'min_account_age',
        'ticket_category', 'ticket_log_channel', 'ticket_limit', 'ticket_panel_channel', 'ticket_panel_message_id',
        'unban_enabled', 'language', 'stream_end_action', 'levelup_dm', 'slots_enabled',
        'starboard_channel', 'starboard_emoji', 'starboard_threshold',
    ];

    public const LINK_ACTIONS = ['delete', 'warn', 'timeout'];

    public const END_ACTIONS = ['keep', 'edit', 'delete'];

    public const DEFAULT_STARBOARD_EMOJI = '⭐';

    public const MAX_STARBOARD_EMOJIS = 10;

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
        'levelup_dm' => false,
        'slots_enabled' => true,
        'stream_end_action' => 'keep',
        'mod_dm' => true,
        'verify_enabled' => false,
        'link_invites' => false,
        'link_scam' => false,
        'link_allowlist_enabled' => false,
        'link_action' => 'delete',
        'raid_enabled' => false,
        'raid_joins' => 10,
        'raid_seconds' => 10,
        'raid_minutes' => 10,
        'raid_kick' => true,
        'raid_level' => true,
        'min_account_age' => 0,
        'ticket_limit' => 1,
        'unban_enabled' => false,
        'starboard_threshold' => 3,
    ];

    protected function casts(): array
    {
        return [
            'tags_enabled' => 'boolean',
            'levelup_enabled' => 'boolean',
            'levelup_dm' => 'boolean',
            'slots_enabled' => 'boolean',
            'mod_dm' => 'boolean',
            'verify_enabled' => 'boolean',
            'link_invites' => 'boolean',
            'link_scam' => 'boolean',
            'link_allowlist_enabled' => 'boolean',
            'raid_enabled' => 'boolean',
            'raid_joins' => 'integer',
            'raid_seconds' => 'integer',
            'raid_minutes' => 'integer',
            'raid_kick' => 'boolean',
            'raid_level' => 'boolean',
            'raid_until' => 'datetime',
            'min_account_age' => 'integer',
            'ticket_limit' => 'integer',
            'unban_enabled' => 'boolean',
            'starboard_threshold' => 'integer',
        ];
    }

    public static function serializeStarboardEmojis(array $emojis): ?string
    {
        $emojis = array_values(array_unique(array_filter(array_map('trim', $emojis))));

        return $emojis === [] || $emojis === [self::DEFAULT_STARBOARD_EMOJI] ? null : implode(' ', $emojis);
    }

    public function starboardEmojis(): array
    {
        $emojis = array_values(array_filter(preg_split('/\s+/', trim((string) $this->starboard_emoji))));

        return $emojis === [] ? [self::DEFAULT_STARBOARD_EMOJI] : $emojis;
    }

    public static function forDiscordId(string $id): self
    {
        return static::firstOrNew(['id' => $id]);
    }

    public function messageLocale(): string
    {
        $locale = collect(config('app.locales'))->search(fn (array $l) => $l['bot'] === $this->language);

        return $locale === false ? app()->getLocale() : $locale;
    }

    public function rememberLanguage(): void
    {
        if (! $this->language) {
            $this->language = config('app.locales.'.app()->getLocale().'.bot');
        }
    }

    public function raidActive(): bool
    {
        return $this->raid_until !== null && $this->raid_until->isFuture();
    }
}
