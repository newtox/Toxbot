<?php

namespace Tests\Feature;

use App\Livewire\DeleteAccount;
use App\Livewire\ServerData;
use App\Models\BotUser;
use App\Models\CreditTransaction;
use App\Models\DashboardUser;
use App\Models\Guild;
use App\Models\MemberLevel;
use App\Models\ModCase;
use App\Models\PremiumServer;
use App\Models\StreamSource;
use App\Models\StreamSubscription;
use App\Models\Tag;
use App\Models\TagGuildBlock;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\TicketType;
use App\Models\UnbanAppeal;
use App\Models\Verification;
use App\Models\WarnRule;
use App\Services\DataDeletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class DataDeletionTest extends TestCase
{
    use RefreshDatabase;

    private const GUILD = '111111111111111111';

    private const OTHER = '222222222222222222';

    private const BOT = '999999999999999999';

    private const USER = '123456789012345678';

    private bool $owner = true;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.discord.bot_token' => 'bot-token', 'services.discord.client_id' => 'client']);

        Http::fake(function (Request $request) {
            $url = $request->url();
            $isBot = str_starts_with($request->header('Authorization')[0] ?? '', 'Bot ');

            return match (true) {
                ! $isBot && str_contains($url, '/users/@me/guilds') => Http::response([
                    ['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon' => null, 'owner' => $this->owner, 'permissions' => (string) 0x20],
                ]),
                $isBot && $request->method() === 'DELETE' && str_contains($url, '/users/@me/guilds/') => Http::response(null, 204),
                $isBot && str_contains($url, '/users/@me/guilds') => Http::response([['id' => self::GUILD]]),
                $isBot && str_ends_with($url, '/users/@me') => Http::response(['id' => self::BOT, 'username' => 'Toxbot', 'avatar' => null]),
                $request->method() === 'DELETE' && str_contains($url, '/messages/') => Http::response(null, 204),
                str_contains($url, '/roles') || str_contains($url, '/channels') => Http::response([]),
                str_contains($url, '/members/') => Http::response(['roles' => []]),
                str_contains($url, '/guilds/'.self::GUILD) => Http::response(['id' => self::GUILD, 'name' => 'Toxic Lounge', 'icon' => null]),
                default => Http::response([], 404),
            };
        });
    }

    private function login(): DashboardUser
    {
        $user = DashboardUser::create(['id' => self::USER, 'username' => 'newtox', 'global_name' => 'Newtox']);
        $user->forceFill(['discord_access_token' => 'user-token', 'discord_token_expires_at' => now()->addDays(7)])->save();
        $this->actingAs($user);

        return $user;
    }

    private function serverData()
    {
        return Livewire::test(ServerData::class, ['guildId' => self::GUILD, 'guildName' => 'Toxic Lounge']);
    }

    private function ticket(string $guild, string $userId = '555', int $number = 1): Ticket
    {
        $ticket = new Ticket;
        $ticket->forceFill(['guild' => $guild, 'number' => $number, 'user_id' => $userId, 'user_name' => 'kira', 'status' => 'closed'])->save();
        $message = new TicketMessage;
        $message->forceFill(['ticket_id' => $ticket->id, 'author_id' => $userId, 'author_name' => 'kira', 'author_avatar' => 'https://cdn/x.png', 'content' => 'Hallo', 'sent_at' => now()])->save();

        return $ticket;
    }

    private function modCase(string $guild, string $userId = '555', int $number = 1): void
    {
        $case = new ModCase;
        $case->forceFill(['guild' => $guild, 'number' => $number, 'type' => 'warn', 'user_id' => $userId, 'moderator_id' => '1', 'active' => true])->save();
    }

    private function serverFixtures(): void
    {
        Guild::create(['id' => self::GUILD, 'modlog_channel' => '12', 'ticket_panel_channel' => '11', 'ticket_panel_message_id' => '700', 'verify_channel' => '13', 'verify_message_id' => '701', 'link_scam' => true]);
        TicketType::create(['guild' => self::GUILD, 'name' => 'Support']);
        WarnRule::create(['guild' => self::GUILD, 'warns' => 3, 'action' => 'kick']);
        $source = StreamSource::create(['platform' => 'twitch', 'external_id' => '1', 'login' => 'x', 'name' => 'X']);
        StreamSubscription::create(['guild' => self::GUILD, 'stream_source_id' => $source->id]);
        MemberLevel::create(['guild' => self::GUILD, 'user' => '555', 'xp' => 100]);
        MemberLevel::create(['guild' => self::OTHER, 'user' => '555', 'xp' => 100]);
        $this->modCase(self::GUILD);
        $this->modCase(self::OTHER);
        $this->ticket(self::GUILD);
        $this->ticket(self::OTHER);
        UnbanAppeal::create(['guild' => self::GUILD, 'user_id' => '555', 'reason' => 'Bitte entbannen.']);
        Verification::create(['guild' => self::GUILD, 'user_id' => '555']);
        PremiumServer::create(['user_id' => self::USER, 'guild_id' => self::GUILD]);
    }

    public function test_reset_turns_settings_off_and_keeps_history(): void
    {
        $this->login();
        $this->serverFixtures();

        $this->serverData()->call('resetSettings')->assertRedirect(route('servers.show', self::GUILD));

        $this->assertNull(Guild::find(self::GUILD));
        $this->assertSame(0, TicketType::count());
        $this->assertSame(0, WarnRule::count());
        $this->assertSame(0, StreamSubscription::count());
        $this->assertSame(2, MemberLevel::count());
        $this->assertSame(2, ModCase::count());
        $this->assertSame(2, Ticket::count());
        $this->assertSame(1, UnbanAppeal::count());
        $this->assertSame(1, PremiumServer::count());
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/channels/11/messages/700'));
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/channels/13/messages/701'));
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/users/@me/guilds/'.self::GUILD));
    }

    public function test_owner_deletes_everything_and_toxbot_leaves(): void
    {
        $this->login();
        $this->serverFixtures();

        $this->serverData()->set('confirmName', 'toxic lounge')->call('deleteServer')->assertHasErrors('confirmName');
        $this->assertSame(1, TicketType::count());

        $this->serverData()->set('confirmName', 'Toxic Lounge')->call('deleteServer')->assertRedirect(route('servers.index'));

        $this->assertNull(Guild::find(self::GUILD));
        $this->assertSame(['222222222222222222'], MemberLevel::pluck('guild')->all());
        $this->assertSame(['222222222222222222'], ModCase::pluck('guild')->all());
        $this->assertSame(['222222222222222222'], Ticket::pluck('guild')->all());
        $this->assertSame(1, TicketMessage::count());
        $this->assertSame(0, UnbanAppeal::count());
        $this->assertSame(0, Verification::count());
        $this->assertSame(0, PremiumServer::count());
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/users/@me/guilds/'.self::GUILD));
    }

    public function test_only_the_owner_can_delete_the_server(): void
    {
        $this->owner = false;
        $this->login();
        $this->serverFixtures();

        $this->serverData()->assertSee(__('data.owner_only'))->set('confirmName', 'Toxic Lounge')->call('deleteServer')->assertForbidden();
        $this->assertNotNull(Guild::find(self::GUILD));

        $this->get('/servers/'.self::GUILD.'/data')->assertOk()->assertSee(__('data.delete_title'));
        $this->get('/servers/'.self::GUILD)->assertOk()->assertDontSee(__('data.delete_title'));
    }

    public function test_user_deletes_their_data(): void
    {
        $user = $this->login();
        $this->serverFixtures();
        BotUser::create(['id' => self::USER, 'language' => 'de_de', 'color' => '#ffffff']);
        MemberLevel::create(['guild' => self::GUILD, 'user' => self::USER, 'xp' => 50]);
        CreditTransaction::create(['user' => self::USER, 'amount' => 100, 'reason' => 'daily']);
        $tag = Tag::create(['name' => 'hallo', 'owner' => self::USER, 'content' => 'Hi']);
        TagGuildBlock::create(['guild' => self::GUILD, 'tag_id' => $tag->id]);
        $ticket = $this->ticket(self::GUILD, self::USER, 2);
        UnbanAppeal::create(['guild' => self::GUILD, 'user_id' => self::USER, 'reason' => 'Bitte.']);
        $this->modCase(self::GUILD, self::USER, 2);

        Livewire::test(DeleteAccount::class)->set('confirmName', 'Newtox')->call('delete')->assertHasErrors('confirmName');
        $this->assertNotNull(DashboardUser::find(self::USER));

        Livewire::test(DeleteAccount::class)->set('confirmName', 'newtox')->call('delete')->assertRedirect(route('home'));

        $this->assertNull(DashboardUser::find(self::USER));
        $this->assertNull(BotUser::find(self::USER));
        $this->assertSame(0, MemberLevel::where('user', self::USER)->count());
        $this->assertSame(0, CreditTransaction::count());
        $this->assertSame(0, Tag::count());
        $this->assertSame(0, TagGuildBlock::count());
        $this->assertSame(0, PremiumServer::count());
        $this->assertSame(1, UnbanAppeal::count());
        $this->assertSame(3, ModCase::count());
        $this->assertGuest();

        $ticket->refresh();
        $this->assertSame('0', $ticket->user_id);
        $this->assertSame(DataDeletion::DELETED_USER, $ticket->user_name);
        $message = TicketMessage::where('ticket_id', $ticket->id)->sole();
        $this->assertSame('0', $message->author_id);
        $this->assertSame(DataDeletion::DELETED_USER, $message->author_name);
        $this->assertNull($message->author_avatar);
        $this->assertSame('Hallo', $message->content);
        $this->assertSame(2, MemberLevel::where('user', '555')->count());
    }

    public function test_profile_shows_the_delete_option(): void
    {
        $this->login();

        $this->get('/profile/data')->assertOk()->assertSee(__('data.account_title'));
        $this->get('/profile')->assertOk()->assertDontSee(__('data.account_title'));
    }
}
