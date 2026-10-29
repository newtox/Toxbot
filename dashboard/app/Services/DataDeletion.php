<?php

namespace App\Services;

use App\Models\BotUser;
use App\Models\CreditTransaction;
use App\Models\DashboardUser;
use App\Models\Guild;
use App\Models\MemberLevel;
use App\Models\ModAction;
use App\Models\ModCase;
use App\Models\ServerTag;
use App\Models\StarboardPost;
use App\Models\StreamSubscription;
use App\Models\Tag;
use App\Models\TagGuildBlock;
use App\Models\TagReport;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketType;
use App\Models\UnbanAppeal;
use App\Models\Verification;
use App\Models\WarnRule;
use Illuminate\Support\Facades\DB;

class DataDeletion
{
    public const DELETED_USER = 'Deleted user';

    public const SECTION_COLUMNS = [
        'welcome' => ['welcome_channel', 'welcome_msg'],
        'goodbye' => ['bye_channel', 'bye_msg'],
        'autorole' => ['autorole'],
        'language' => ['language'],
        'levels' => ['levelup_enabled', 'levelup_channel', 'levelup_dm', 'slots_enabled'],
        'streams' => ['stream', 'notify', 'stream_msg', 'stream_video_msg', 'stream_end_action'],
        'liverole' => ['live_role', 'live_role_required'],
        'tags' => ['tags_enabled'],
        'moderation' => ['modlog_channel', 'mod_dm'],
        'verification' => ['verify_enabled', 'verify_role', 'verify_channel', 'verify_message_id'],
        'links' => ['link_invites', 'link_scam', 'link_allowlist_enabled', 'link_allowlist', 'link_blocklist', 'link_action', 'link_exempt_roles'],
        'raid' => ['raid_enabled', 'raid_joins', 'raid_seconds', 'raid_minutes', 'raid_kick', 'raid_level', 'raid_until', 'min_account_age'],
        'tickets' => ['ticket_category', 'ticket_log_channel', 'ticket_limit', 'ticket_panel_channel', 'ticket_panel_message_id'],
        'starboard' => ['starboard_channel', 'starboard_emoji', 'starboard_threshold'],
    ];

    public function __construct(private Discord $discord) {}

    public function resetGuild(string $guildId): void
    {
        $row = Guild::query()->find($guildId);
        if ($row) {
            foreach ([[$row->ticket_panel_channel, $row->ticket_panel_message_id], [$row->verify_channel, $row->verify_message_id]] as [$channel, $message]) {
                if ($channel && $message) {
                    rescue(fn () => $this->discord->deleteMessage($channel, $message), report: false);
                }
            }
        }

        DB::transaction(function () use ($guildId) {
            Guild::query()->whereKey($guildId)->delete();
            TicketType::query()->where('guild', $guildId)->delete();
            WarnRule::query()->where('guild', $guildId)->delete();
            StreamSubscription::query()->where('guild', $guildId)->get()->each->delete();
            TagGuildBlock::query()->where('guild', $guildId)->delete();
            ServerTag::query()->where('guild', $guildId)->delete();
        });

        $this->discord->forgetGuildCache($guildId);
    }

    public function resetSection(string $guildId, string $section): void
    {
        $row = Guild::query()->find($guildId);
        if ($row) {
            $message = match ($section) {
                'verification' => [$row->verify_channel, $row->verify_message_id],
                'tickets' => [$row->ticket_panel_channel, $row->ticket_panel_message_id],
                default => [null, null],
            };
            if ($message[0] && $message[1]) {
                rescue(fn () => $this->discord->deleteMessage($message[0], $message[1]), report: false);
            }

            $defaults = (new Guild)->getAttributes();
            foreach (self::SECTION_COLUMNS[$section] as $column) {
                $row->{$column} = $defaults[$column] ?? null;
            }
            $row->save();
        }

        match ($section) {
            'streams' => StreamSubscription::query()->where('guild', $guildId)->get()->each->delete(),
            'tags' => [
                TagGuildBlock::query()->where('guild', $guildId)->delete(),
                ServerTag::query()->where('guild', $guildId)->delete(),
            ],
            'moderation' => WarnRule::query()->where('guild', $guildId)->delete(),
            'tickets' => TicketType::query()->where('guild', $guildId)->delete(),
            default => null,
        };
    }

    public function deleteGuild(string $guildId): void
    {
        $this->resetGuild($guildId);

        DB::transaction(function () use ($guildId) {
            TicketMessage::query()->whereIn('ticket_id', Ticket::query()->where('guild', $guildId)->select('id'))->delete();
            Ticket::query()->where('guild', $guildId)->delete();
            UnbanAppeal::query()->where('guild', $guildId)->delete();
            ModCase::query()->where('guild', $guildId)->delete();
            ModAction::query()->where('guild', $guildId)->delete();
            Verification::query()->where('guild', $guildId)->delete();
            MemberLevel::query()->where('guild', $guildId)->delete();
            StarboardPost::query()->where('guild', $guildId)->delete();
        });

        $this->discord->leaveGuild($guildId);
    }

    public function deleteUser(DashboardUser $user): void
    {
        $userId = (string) $user->id;

        StarboardPost::query()->where('author_id', $userId)->whereNotNull('starboard_message_id')->get()
            ->each(fn (StarboardPost $post) => rescue(fn () => $this->discord->deleteMessage($post->starboard_channel_id, $post->starboard_message_id), report: false));

        DB::transaction(function () use ($userId, $user) {
            BotUser::query()->whereKey($userId)->delete();
            MemberLevel::query()->where('user', $userId)->delete();
            CreditTransaction::query()->where('user', $userId)->delete();
            DB::table('user_backgrounds')->where('user', $userId)->delete();
            $tagIds = Tag::query()->where('owner', $userId)->pluck('id');
            TagReport::query()->whereIn('tag_id', $tagIds)->delete();
            TagGuildBlock::query()->whereIn('tag_id', $tagIds)->delete();
            Tag::query()->whereIn('id', $tagIds)->delete();
            TagReport::query()->where('reporter', $userId)->delete();
            Verification::query()->where('user_id', $userId)->delete();
            UnbanAppeal::query()->where('user_id', $userId)->delete();
            StarboardPost::query()->where('author_id', $userId)->delete();
            TicketMessage::query()->where('author_id', $userId)->update([
                'author_id' => '0',
                'author_name' => self::DELETED_USER,
                'author_avatar' => null,
            ]);
            Ticket::query()->where('user_id', $userId)->update(['user_id' => '0', 'user_name' => self::DELETED_USER]);
            Ticket::query()->where('closed_by', $userId)->update(['closed_by' => '0', 'closed_by_name' => self::DELETED_USER]);
            DB::table('sessions')->where('user_id', $userId)->delete();
            $user->delete();
        });
    }
}
