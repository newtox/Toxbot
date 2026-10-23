<?php

namespace App\Services;

use App\Models\BotUser;
use App\Models\CreditTransaction;
use App\Models\DashboardUser;
use App\Models\Guild;
use App\Models\MemberLevel;
use App\Models\ModAction;
use App\Models\ModCase;
use App\Models\PremiumServer;
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
        });

        $this->discord->forgetGuildCache($guildId);
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
            PremiumServer::query()->where('guild_id', $guildId)->delete();
        });

        $this->discord->leaveGuild($guildId);
    }

    public function deleteUser(DashboardUser $user): void
    {
        $userId = (string) $user->id;

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
            PremiumServer::query()->where('user_id', $userId)->delete();
            Verification::query()->where('user_id', $userId)->delete();
            UnbanAppeal::query()->where('user_id', $userId)->delete();
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
