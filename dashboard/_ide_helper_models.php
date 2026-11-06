<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string|null $user
 * @property string|null $guild
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlacklistEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlacklistEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlacklistEntry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlacklistEntry whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlacklistEntry whereGuild($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlacklistEntry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlacklistEntry whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlacklistEntry whereUser($value)
 */
	class BlacklistEntry extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $heartbeat_at
 * @property string|null $commit
 * @property string|null $build_date
 * @property string|null $dart_version
 * @property int $guilds
 * @property int|null $latency_ms
 * @property int|null $memory_bytes
 * @property int|null $memory_peak_bytes
 * @property string|null $user_id
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotStatus query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotStatus whereBuildDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotStatus whereCommit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotStatus whereDartVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotStatus whereGuilds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotStatus whereHeartbeatAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotStatus whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotStatus whereLatencyMs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotStatus whereMemoryBytes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotStatus whereMemoryPeakBytes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotStatus whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotStatus whereUserId($value)
 */
	class BotStatus extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $language
 * @property string $color
 * @property int $xp
 * @property int $credits
 * @property int $commands_used
 * @property \Illuminate\Support\Carbon|null $xp_at
 * @property \Illuminate\Support\Carbon|null $daily_at
 * @property int $daily_streak
 * @property int|null $background_id
 * @property int|null $frame_id
 * @property int|null $bar_id
 * @property int $tag_slots
 * @property-read \App\Models\ShopItem|null $background
 * @property-read \App\Models\ShopItem|null $bar
 * @property-read \App\Models\ShopItem|null $frame
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ShopItem> $items
 * @property-read int|null $items_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotUser whereBackgroundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotUser whereBarId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotUser whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotUser whereCommandsUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotUser whereCredits($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotUser whereDailyAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotUser whereDailyStreak($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotUser whereFrameId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotUser whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotUser whereTagSlots($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotUser whereXp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BotUser whereXpAt($value)
 */
	class BotUser extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $user
 * @property int $amount
 * @property string $reason
 * @property string|null $reference
 * @property \Illuminate\Support\Carbon|null $created_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditTransaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditTransaction whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditTransaction whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditTransaction whereUser($value)
 */
	class CreditTransaction extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $username
 * @property string|null $global_name
 * @property string|null $avatar
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $discord_access_token
 * @property string|null $discord_refresh_token
 * @property \Illuminate\Support\Carbon|null $discord_token_expires_at
 * @property-read mixed $avatar_url
 * @property-read mixed $display_name
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DashboardUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DashboardUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DashboardUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DashboardUser whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DashboardUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DashboardUser whereDiscordAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DashboardUser whereDiscordRefreshToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DashboardUser whereDiscordTokenExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DashboardUser whereGlobalName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DashboardUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DashboardUser whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DashboardUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DashboardUser whereUsername($value)
 */
	class DashboardUser extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $sku_id
 * @property string|null $user_id
 * @property string|null $guild_id
 * @property int $type
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property bool $deleted
 * @property bool $consumed
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Entitlement active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Entitlement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Entitlement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Entitlement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Entitlement whereConsumed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Entitlement whereDeleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Entitlement whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Entitlement whereGuildId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Entitlement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Entitlement whereSkuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Entitlement whereStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Entitlement whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Entitlement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Entitlement whereUserId($value)
 */
	class Entitlement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string|null $welcome_channel
 * @property string|null $bye_channel
 * @property string|null $welcome_msg
 * @property string|null $bye_msg
 * @property string|null $autorole
 * @property string|null $notify
 * @property string|null $stream
 * @property bool $tags_enabled
 * @property bool $levelup_enabled
 * @property string|null $levelup_channel
 * @property string|null $stream_msg
 * @property string|null $live_role
 * @property string|null $live_role_required
 * @property string|null $stream_video_msg
 * @property string|null $modlog_channel
 * @property bool $mod_dm
 * @property bool $verify_enabled
 * @property string|null $verify_role
 * @property string|null $verify_channel
 * @property string|null $verify_message_id
 * @property bool $link_invites
 * @property bool $link_scam
 * @property bool $link_allowlist_enabled
 * @property string|null $link_allowlist
 * @property string|null $link_blocklist
 * @property string $link_action
 * @property bool $raid_enabled
 * @property int $raid_joins
 * @property int $raid_seconds
 * @property int $raid_minutes
 * @property bool $raid_kick
 * @property bool $raid_level
 * @property \Illuminate\Support\Carbon|null $raid_until
 * @property int|null $raid_previous_level
 * @property int $min_account_age
 * @property string|null $link_exempt_roles
 * @property string|null $ticket_category
 * @property string|null $ticket_log_channel
 * @property int $ticket_limit
 * @property string|null $ticket_panel_channel
 * @property string|null $ticket_panel_message_id
 * @property bool $unban_enabled
 * @property string|null $language
 * @property string $stream_end_action
 * @property bool $levelup_dm
 * @property string|null $starboard_channel
 * @property string|null $starboard_emoji
 * @property int $starboard_threshold
 * @property bool $slots_enabled
 * @property string|null $automod_words
 * @property int|null $automod_mentions
 * @property string|null $automod_exempt_roles
 * @property string|null $automod_rule_domains
 * @property string|null $automod_rule_mentions
 * @property string|null $automod_rule_words
 * @property int $automod_profile_enabled
 * @property string|null $automod_profile_words
 * @property int $automod_spam_enabled
 * @property int $automod_flagged_enabled
 * @property int|null $automod_words_timeout
 * @property int|null $automod_mentions_timeout
 * @property string|null $automod_rule_profile
 * @property string|null $automod_rule_spam
 * @property string|null $automod_rule_flagged
 * @property string|null $automod_words_allow
 * @property bool $automod_mention_raid
 * @property bool $automod_spam
 * @property string|null $automod_presets
 * @property string|null $automod_presets_allow
 * @property string|null $automod_message
 * @property string|null $automod_exempt_channels
 * @property string|null $automod_rule_presets
 * @property string|null $bot_bio
 * @property bool $bot_profile
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodExemptChannels($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodExemptRoles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodFlaggedEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodMentionRaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodMentions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodMentionsTimeout($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodPresets($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodPresetsAllow($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodProfileEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodProfileWords($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodRuleDomains($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodRuleFlagged($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodRuleMentions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodRulePresets($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodRuleProfile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodRuleSpam($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodRuleWords($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodSpam($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodSpamEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodWords($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodWordsAllow($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutomodWordsTimeout($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereAutorole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereBotBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereBotProfile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereByeChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereByeMsg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereLevelupChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereLevelupDm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereLevelupEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereLinkAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereLinkAllowlist($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereLinkAllowlistEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereLinkBlocklist($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereLinkExemptRoles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereLinkInvites($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereLinkScam($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereLiveRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereLiveRoleRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereMinAccountAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereModDm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereModlogChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereNotify($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereRaidEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereRaidJoins($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereRaidKick($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereRaidLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereRaidMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereRaidPreviousLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereRaidSeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereRaidUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereSlotsEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereStarboardChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereStarboardEmoji($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereStarboardThreshold($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereStream($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereStreamEndAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereStreamMsg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereStreamVideoMsg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereTagsEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereTicketCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereTicketLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereTicketLogChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereTicketPanelChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereTicketPanelMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereUnbanEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereVerifyChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereVerifyEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereVerifyMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereVerifyRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereWelcomeChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereWelcomeMsg($value)
 */
	class Guild extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $guild
 * @property string $name
 * @property string|null $role_id
 * @property string|null $team_role_id
 * @property string|null $category_id
 * @property string|null $info_channel_id
 * @property string|null $text_channel_id
 * @property string|null $voice_channel_id
 * @property string|null $extra_voice_channel_ids
 * @property string $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuildEvent forGuild(string $guildId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuildEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuildEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuildEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuildEvent whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuildEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuildEvent whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuildEvent whereExtraVoiceChannelIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuildEvent whereGuild($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuildEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuildEvent whereInfoChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuildEvent whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuildEvent whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuildEvent whereTeamRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuildEvent whereTextChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuildEvent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GuildEvent whereVoiceChannelId($value)
 */
	class GuildEvent extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $guild
 * @property string $user
 * @property int $xp
 * @property \Illuminate\Support\Carbon|null $xp_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemberLevel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemberLevel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemberLevel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemberLevel whereGuild($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemberLevel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemberLevel whereUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemberLevel whereXp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MemberLevel whereXpAt($value)
 */
	class MemberLevel extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $guild
 * @property string $type
 * @property string $user_id
 * @property string $moderator_id
 * @property string|null $moderator_name
 * @property string|null $reason
 * @property int|null $duration
 * @property int $delete_days
 * @property string $status
 * @property string|null $error
 * @property int|null $case_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ModCase|null $modCase
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModAction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModAction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModAction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModAction whereCaseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModAction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModAction whereDeleteDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModAction whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModAction whereError($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModAction whereGuild($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModAction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModAction whereModeratorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModAction whereModeratorName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModAction whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModAction whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModAction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModAction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModAction whereUserId($value)
 */
	class ModAction extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $guild
 * @property int $number
 * @property string $type
 * @property string $user_id
 * @property string|null $user_name
 * @property string|null $moderator_id
 * @property string|null $moderator_name
 * @property string|null $reason
 * @property int|null $duration
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property bool $active
 * @property string $source
 * @property string|null $log_message_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModCase forGuild(string $guildId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModCase newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModCase newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModCase query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModCase whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModCase whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModCase whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModCase whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModCase whereGuild($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModCase whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModCase whereLogMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModCase whereModeratorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModCase whereModeratorName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModCase whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModCase whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModCase whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModCase whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModCase whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModCase whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModCase whereUserName($value)
 */
	class ModCase extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $type
 * @property string $target_id
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property string|null $note
 * @property string|null $granted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PremiumGrant active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PremiumGrant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PremiumGrant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PremiumGrant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PremiumGrant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PremiumGrant whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PremiumGrant whereGrantedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PremiumGrant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PremiumGrant whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PremiumGrant whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PremiumGrant whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PremiumGrant whereUpdatedAt($value)
 */
	class PremiumGrant extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $guild
 * @property string $name
 * @property string $content
 * @property int $uses
 * @property string|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServerTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServerTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServerTag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServerTag whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServerTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServerTag whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServerTag whereGuild($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServerTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServerTag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServerTag whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ServerTag whereUses($value)
 */
	class ServerTag extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $type
 * @property string $file
 * @property bool $animated
 * @property int $price
 * @property bool $active
 * @property int $sort
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $sku_id
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem inShop()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem ofType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereAnimated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereSkuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShopItem whereUpdatedAt($value)
 */
	class ShopItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $guild
 * @property string $channel_id
 * @property string $message_id
 * @property string $author_id
 * @property string|null $author_name
 * @property string|null $author_avatar
 * @property string|null $content
 * @property string|null $image_url
 * @property int $stars
 * @property string|null $starboard_channel_id
 * @property string|null $starboard_message_id
 * @property \Illuminate\Support\Carbon|null $message_created_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StarboardPost forGuild(string $guildId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StarboardPost newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StarboardPost newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StarboardPost query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StarboardPost whereAuthorAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StarboardPost whereAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StarboardPost whereAuthorName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StarboardPost whereChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StarboardPost whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StarboardPost whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StarboardPost whereGuild($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StarboardPost whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StarboardPost whereImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StarboardPost whereMessageCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StarboardPost whereMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StarboardPost whereStarboardChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StarboardPost whereStarboardMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StarboardPost whereStars($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StarboardPost whereUpdatedAt($value)
 */
	class StarboardPost extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $stream_subscription_id
 * @property string $item_id
 * @property string $kind
 * @property string|null $channel_id
 * @property string|null $message_id
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\StreamSubscription $subscription
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamAnnouncement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamAnnouncement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamAnnouncement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamAnnouncement whereChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamAnnouncement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamAnnouncement whereEndedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamAnnouncement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamAnnouncement whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamAnnouncement whereKind($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamAnnouncement whereMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamAnnouncement whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamAnnouncement whereStreamSubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamAnnouncement whereUpdatedAt($value)
 */
	class StreamAnnouncement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $platform
 * @property string $external_id
 * @property string|null $login
 * @property string $name
 * @property string|null $avatar_url
 * @property string|null $twitch_online_sub
 * @property string|null $twitch_offline_sub
 * @property \Illuminate\Support\Carbon|null $websub_expires_at
 * @property string|null $live_item
 * @property \Illuminate\Support\Carbon|null $live_since
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StreamSubscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSource newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSource newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSource query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSource whereAvatarUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSource whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSource whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSource whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSource whereLiveItem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSource whereLiveSince($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSource whereLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSource whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSource wherePlatform($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSource whereTwitchOfflineSub($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSource whereTwitchOnlineSub($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSource whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSource whereWebsubExpiresAt($value)
 */
	class StreamSource extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $guild
 * @property int $stream_source_id
 * @property string|null $message
 * @property string|null $role
 * @property string|null $filter_games
 * @property string|null $filter_title
 * @property string $end_action
 * @property bool $notify_videos
 * @property bool $notify_live
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $video_message
 * @property string|null $channel
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StreamAnnouncement> $announcements
 * @property-read int|null $announcements_count
 * @property-read \App\Models\StreamSource $source
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSubscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSubscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSubscription query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSubscription whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSubscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSubscription whereEndAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSubscription whereFilterGames($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSubscription whereFilterTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSubscription whereGuild($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSubscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSubscription whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSubscription whereNotifyLive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSubscription whereNotifyVideos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSubscription whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSubscription whereStreamSourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSubscription whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StreamSubscription whereVideoMessage($value)
 */
	class StreamSubscription extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $owner
 * @property string $content
 * @property bool $embed
 * @property string|null $embed_color
 * @property string|null $embed_image
 * @property int $uses
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TagReport> $reports
 * @property-read int|null $reports_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereEmbed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereEmbedColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereEmbedImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereOwner($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereUses($value)
 */
	class Tag extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $guild
 * @property int $tag_id
 * @property-read \App\Models\Tag $tag
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagGuildBlock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagGuildBlock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagGuildBlock query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagGuildBlock whereGuild($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagGuildBlock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagGuildBlock whereTagId($value)
 */
	class TagGuildBlock extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $tag_id
 * @property string $reporter
 * @property string|null $guild
 * @property string $reason
 * @property bool $resolved
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Tag $tag
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagReport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagReport whereGuild($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagReport whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagReport whereReporter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagReport whereResolved($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagReport whereTagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TagReport whereUpdatedAt($value)
 */
	class TagReport extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $guild
 * @property int $number
 * @property int|null $type_id
 * @property string|null $type_name
 * @property string $user_id
 * @property string|null $user_name
 * @property string|null $channel_id
 * @property string $status
 * @property string|null $closed_by
 * @property string|null $closed_by_name
 * @property string|null $close_reason
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TicketMessage> $messages
 * @property-read int|null $messages_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket forGuild(string $guildId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereCloseReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereClosedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereClosedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereClosedByName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereGuild($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereTypeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereUserName($value)
 */
	class Ticket extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $ticket_id
 * @property string $author_id
 * @property string $author_name
 * @property string|null $author_avatar
 * @property bool $author_bot
 * @property string|null $content
 * @property array<array-key, mixed>|null $attachments
 * @property \Illuminate\Support\Carbon $sent_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketMessage whereAttachments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketMessage whereAuthorAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketMessage whereAuthorBot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketMessage whereAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketMessage whereAuthorName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketMessage whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketMessage whereSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketMessage whereTicketId($value)
 */
	class TicketMessage extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $guild
 * @property string $name
 * @property string|null $emoji
 * @property string|null $description
 * @property string|null $roles
 * @property int $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereEmoji($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereGuild($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereRoles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereUpdatedAt($value)
 */
	class TicketType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $guild
 * @property string $user_id
 * @property string|null $user_name
 * @property string $reason
 * @property string $status
 * @property string|null $handled_by
 * @property string|null $handled_by_name
 * @property string|null $response
 * @property \Illuminate\Support\Carbon|null $handled_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnbanAppeal forGuild(string $guildId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnbanAppeal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnbanAppeal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnbanAppeal query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnbanAppeal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnbanAppeal whereGuild($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnbanAppeal whereHandledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnbanAppeal whereHandledBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnbanAppeal whereHandledByName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnbanAppeal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnbanAppeal whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnbanAppeal whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnbanAppeal whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnbanAppeal whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnbanAppeal whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UnbanAppeal whereUserName($value)
 */
	class UnbanAppeal extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $guild
 * @property string $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Verification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Verification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Verification query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Verification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Verification whereGuild($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Verification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Verification whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Verification whereVerifiedAt($value)
 */
	class Verification extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $guild
 * @property int $warns
 * @property string $action
 * @property int|null $duration
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarnRule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarnRule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarnRule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarnRule whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarnRule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarnRule whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarnRule whereGuild($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarnRule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarnRule whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarnRule whereWarns($value)
 */
	class WarnRule extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookDelivery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookDelivery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookDelivery query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookDelivery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WebhookDelivery whereId($value)
 */
	class WebhookDelivery extends \Eloquent {}
}

